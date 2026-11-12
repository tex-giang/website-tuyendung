<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Category;

/**
 * Đọc/ghi file CSV mẫu để nhập hàng loạt tin tuyển dụng.
 *
 * Mục đích: admin tải template về (Excel), điền các dòng tin, upload lại —
 * mỗi dòng hợp lệ được tạo thành 1 tin tuyển dụng.
 *
 * Định dạng: CSV UTF-8 (có BOM để Excel nhận đúng tiếng Việt), phân cách ";" hoặc ",".
 */
final class JobImport
{
    /** Giới hạn mỗi lần import để tránh timeout/upload quá lớn. */
    public const MAX_ROWS = 300;
    public const MAX_BYTES = 3 * 1024 * 1024; // 3 MB

    /**
     * Cột chuẩn của template: key => [nhãn hiển thị, các alias khi dò header].
     * Alias đã được chuẩn hoá bằng self::norm().
     *
     * @var array<string, array{0: string, 1: list<string>}>
     */
    public const COLUMNS = [
        'title'            => ['Tiêu đề (*)', ['tieu de', 'title', 'tieu de tin', 'ten tin']],
        'branch'           => ['Khu sản xuất (*)', ['khu san xuat', 'khu vuc', 'nha may', 'chi nhanh', 'branch', 'khu']],
        'category'         => ['Bộ phận', ['bo phan', 'danh muc', 'nhom', 'category']],
        'quantity'         => ['Số lượng', ['so luong', 'quantity', 'sl']],
        'location'         => ['Địa điểm làm việc', ['dia diem lam viec', 'dia diem', 'noi lam viec', 'location']],
        'employment_type'  => ['Loại hình', ['loai hinh', 'hinh thuc', 'loai hinh lam viec', 'employment type']],
        'experience'       => ['Kinh nghiệm', ['kinh nghiem', 'experience']],
        'salary_text'      => ['Lương (hiển thị)', ['luong hien thi', 'luong', 'muc luong', 'salary']],
        'salary_min'       => ['Lương từ (VNĐ)', ['luong tu', 'luong toi thieu', 'salary min', 'luong tu vnd']],
        'salary_max'       => ['Lương đến (VNĐ)', ['luong den', 'luong toi da', 'salary max', 'luong den vnd']],
        'salary_range'     => ['Khoảng lương', ['khoang luong', 'nhom luong', 'salary range']],
        'description'      => ['Mô tả công việc (*)', ['mo ta cong viec', 'mo ta', 'noi dung cong viec', 'description']],
        'requirements'     => ['Yêu cầu ứng viên', ['yeu cau ung vien', 'yeu cau', 'requirements']],
        'benefits'         => ['Phúc lợi', ['phuc loi', 'quyen loi', 'benefits']],
        'posted_at'        => ['Ngày đăng (dd/mm/yyyy)', ['ngay dang', 'posted at', 'ngay tao']],
        'deadline'         => ['Hạn nộp (dd/mm/yyyy)', ['han nop', 'deadline', 'han chot']],
        'status'           => ['Trạng thái (draft/published)', ['trang thai', 'status']],
        'is_urgent'        => ['Tin ưu tiên (1/0)', ['tin uu tien', 'uu tien', 'tuyen gap', 'is urgent']],
        'meta_title'       => ['Meta title', ['meta title', 'tieu de seo', 'seo title']],
        'meta_description' => ['Meta description', ['meta description', 'mo ta seo', 'seo description']],
    ];

    /**
     * Cột KHÔNG cần nhập trong template — tự sinh khi lưu:
     * - salary_text: "9.000.000 - 12.000.000 VNĐ" (từ Lương từ/đến)
     * - salary_range: under-8 / 8-12 / 12-18 / over-18 (từ Lương từ/đến)
     */
    public const DERIVED_COLUMNS = ['salary_text', 'salary_range'];

    /**
     * Cột hiển thị trong file template (đã bỏ các cột tự sinh).
     *
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function templateColumns(): array
    {
        return array_diff_key(self::COLUMNS, array_flip(self::DERIVED_COLUMNS));
    }

    /**
     * Tự sinh "Lương (hiển thị)" từ lương từ/đến (đơn vị đồng).
     * vd: 9000000, 12000000 -> "9.000.000 - 12.000.000 VNĐ"
     */
    public static function salaryText(string $min, string $max): string
    {
        $minAmount = self::salaryAmount($min);
        $maxAmount = self::salaryAmount($max);
        $format = static fn(int $amount): string => number_format($amount, 0, ',', '.');

        if ($minAmount !== null && $maxAmount !== null) {
            return $format($minAmount) . ' - ' . $format($maxAmount) . ' VNĐ';
        }
        if ($minAmount !== null) {
            return 'Từ ' . $format($minAmount) . ' VNĐ';
        }
        if ($maxAmount !== null) {
            return 'Đến ' . $format($maxAmount) . ' VNĐ';
        }

        return '';
    }

    /** Tự sinh "Khoảng lương" (enum DB) từ lương từ/đến. */
    public static function salaryRangeFromAmounts(string $min, string $max): string
    {
        $amount = self::salaryAmount($min) ?? self::salaryAmount($max);
        if ($amount === null) {
            return '';
        }
        $million = $amount / 1000000;
        if ($million < 8) {
            return 'under-8';
        }
        if ($million < 12) {
            return '8-12';
        }
        if ($million < 18) {
            return '12-18';
        }

        return 'over-18';
    }

    /** Đọc số tiền từ chuỗi người dùng nhập: "9000000", "9.000.000", "9 triệu". */
    private static function salaryAmount(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $digits = (string) preg_replace('/[^0-9]/', '', $value);
        if ($digits === '') {
            return null;
        }

        return (int) $digits;
    }

    /** Nội dung file template CSV (chưa có BOM; controller tự thêm). */
    public static function templateCsv(): string
    {
        $columns = self::templateColumns();
        $headers = [];
        foreach ($columns as [$label]) {
            $headers[] = $label;
        }

        $sample = self::sampleRow();

        $lines = [
            '# MẪU NHẬP TIN TUYỂN DỤNG — TEX-GIANG',
            '# Hướng dẫn:',
            '#  - Giữ nguyên dòng tiêu đề (dòng bắt đầu bằng "Tiêu đề (*)").',
            '#  - Mỗi dòng bên dưới là 1 tin tuyển dụng. Cột có (*) là bắt buộc.',
            '#  - Khu sản xuất: ghi tên (Tân Hương / Cai Lậy / Chợ Gạo) hoặc slug (tan-huong / cai-lay / cho-gao).',
            '#  - Trạng thái: draft (bản nháp) hoặc published (đăng ngay). Để trống = draft.',
            '#  - Ngày đăng: để trống = HÔM NAY. Hạn nộp: để trống = CHƯA XÁC ĐỊNH (không giới hạn).',
            '#    Định dạng ngày: dd/mm/yyyy (ví dụ 31/12/2026).',
            '#  - Lương từ/đến: số nguyên, đơn vị đồng (ví dụ 9000000).',
            '#    Phần "Lương (hiển thị)" và "Khoảng lương" được TỰ SINH từ 2 cột này.',
            '#  - Dòng ví dụ có tiêu đề bắt đầu bằng [MẪU] sẽ TỰ BỎ QUA khi import.',
            '#  - Có thể mở file này bằng Excel; lưu lại dạng CSV UTF-8 (dấu phân cách ;).',
            self::csvLine($headers),
            self::csvLine(array_map(static fn(string $key): string|int => $sample[$key] ?? '', array_keys($columns))),
        ];

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * File template Excel (.xlsx): sheet 1 "Tin tuyển dụng" (header + dòng mẫu)
     * và sheet 2 "Hướng dẫn".
     */
    public static function templateXlsx(): string
    {
        $columns = self::templateColumns();
        $headers = [];
        $widths = [];
        $index = 0;
        foreach ($columns as $key => [$label]) {
            $headers[] = $label;
            $widths[] = self::TEMPLATE_WIDTHS[$key] ?? 20;
            $index++;
        }

        $sample = self::sampleRow();
        $sampleValues = [];
        foreach (array_keys($columns) as $key) {
            $sampleValues[] = $sample[$key] ?? '';
        }

        $columnIndex = array_flip(array_keys($columns));
        $letterOf = static fn(string $key): string => chr(65 + (int) ($columnIndex[$key] ?? 0));
        $lastRow = self::MAX_ROWS + 1;

        $guide = [
            ['HƯỚNG DẪN NHẬP TIN TUYỂN DỤNG — TEX-GIANG'],
            [''],
            ['1. Điền dữ liệu vào sheet "Tin tuyển dụng": mỗi dòng là một tin tuyển dụng.'],
            ['2. Cột có (*) là bắt buộc: Tiêu đề, Khu sản xuất, Mô tả công việc.'],
            ['3. Khu sản xuất: chọn Tân Hương / Cai Lậy / Chợ Gạo từ danh sách.'],
            ['4. Trạng thái: draft (bản nháp) hoặc published (đăng ngay). Để trống = draft.'],
            ['5. Ngày đăng / Hạn nộp: định dạng dd/mm/yyyy (ví dụ 31/12/2026).'],
            ['   Ngày đăng để trống = hôm nay. Hạn nộp để trống = chưa xác định (không giới hạn).'],
            ['6. Lương từ / Lương đến: số nguyên, đơn vị đồng (ví dụ 9000000).'],
            ['   Phần "Lương (hiển thị)" và "Khoảng lương" được TỰ SINH từ 2 cột này — không cần nhập.'],
            ['7. Dòng có tiêu đề bắt đầu bằng [MẪU] sẽ tự bỏ qua khi import.'],
            ['8. Lưu file .xlsx, rồi vào Admin → Tin tuyển dụng → Import template để tải lên.'],
            [''],
            ['Lưu ý: không đổi tên dòng tiêu đề (dòng 1 của sheet dữ liệu).'],
        ];

        return Xlsx::write([
            [
                'name' => 'Tin tuyển dụng',
                'rows' => array_merge([$headers], [$sampleValues]),
                'widths' => $widths,
                'header' => true,
                'freeze_row' => 1,
                'validations' => [
                    ['range' => $letterOf('branch') . '2:' . $letterOf('branch') . $lastRow, 'options' => ['Tân Hương', 'Cai Lậy', 'Chợ Gạo']],
                    ['range' => $letterOf('status') . '2:' . $letterOf('status') . $lastRow, 'options' => ['draft', 'published']],
                    ['range' => $letterOf('is_urgent') . '2:' . $letterOf('is_urgent') . $lastRow, 'options' => ['0', '1']],
                ],
            ],
            [
                'name' => 'Hướng dẫn',
                'rows' => $guide,
                'widths' => [100],
                'header' => false,
            ],
        ]);
    }

    /** Độ rộng cột Excel cho từng cột template. */
    private const TEMPLATE_WIDTHS = [
        'title' => 42, 'branch' => 18, 'category' => 24, 'quantity' => 10,
        'location' => 30, 'employment_type' => 24, 'experience' => 20,
        'salary_min' => 16, 'salary_max' => 16, 'description' => 60,
        'requirements' => 55, 'benefits' => 55, 'posted_at' => 18, 'deadline' => 18,
        'status' => 20, 'is_urgent' => 14, 'meta_title' => 34, 'meta_description' => 44,
    ];

    /**
     * Dòng ví dụ trong template (key = cột chuẩn).
     *
     * @return array<string, string|int>
     */
    private static function sampleRow(): array
    {
        return [
            'title' => '[MẪU] Nhân viên vận hành máy may',
            'branch' => 'Tân Hương',
            'category' => 'Sản xuất may mặc',
            'quantity' => 10,
            'location' => 'Khu Tân Hương — Nhà máy 2',
            'employment_type' => 'Toàn thời gian, chính thức',
            'experience' => 'Không yêu cầu kinh nghiệm',
            'salary_min' => 9000000,
            'salary_max' => 12000000,
            'description' => 'Mô tả công việc mẫu: vận hành máy may công nghiệp, đảm bảo năng suất và chất lượng theo yêu cầu.',
            'requirements' => 'Sức khoẻ tốt, chăm chỉ, có thể làm theo ca.',
            'benefits' => 'BHXH, BHYT đầy đủ; cơm ca miễn phí; xe đưa rước.',
            'posted_at' => '',
            'deadline' => '',
            'status' => 'draft',
            'is_urgent' => 0,
            'meta_title' => '',
            'meta_description' => '',
        ];
    }

    /**
     * Đọc nội dung CSV thành các dòng đã map theo cột chuẩn.
     *
     * @return array{delimiter: string, headers: array<string, int>, rows: list<array<string, string>>, missing: list<string>}
     */
    public static function parse(string $content): array
    {
        // Bỏ BOM nếu có.
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        // File Excel lưu ANSI (Windows-1252/1258) — chuyển về UTF-8.
        if (!mb_check_encoding($content, 'UTF-8')) {
            $converted = @mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
            if (is_string($converted)) {
                $content = $converted;
            }
        }

        $delimiter = self::detectDelimiter($content);
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return ['delimiter' => $delimiter, 'headers' => [], 'rows' => [], 'missing' => ['title', 'branch', 'description']];
        }
        fwrite($stream, $content);
        rewind($stream);

        $rawRows = [];
        while (($cells = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $rawRows[] = $cells;
        }
        fclose($stream);

        return self::buildFromRawRows($rawRows, $delimiter);
    }

    /**
     * Đọc file Excel (.xlsx) — sheet đầu tiên — thành các dòng đã map theo cột chuẩn.
     *
     * @return array{delimiter: string, headers: array<string, int>, rows: list<array<string, string>>, missing: list<string>}
     */
    public static function parseXlsx(string $path): array
    {
        $rawRows = Xlsx::read($path);

        return self::buildFromRawRows($rawRows, 'xlsx');
    }

    /**
     * Dựng kết quả import từ mảng 2 chiều thô (CSV hoặc Excel).
     *
     * @param list<list<string>> $rawRows
     * @return array{delimiter: string, headers: array<string, int>, rows: list<array<string, string>>, missing: list<string>}
     */
    private static function buildFromRawRows(array $rawRows, string $delimiter): array
    {
        // Tìm dòng tiêu đề: dòng đầu tiên map được ít nhất 5 cột quen thuộc.
        $headers = [];
        $headerIndex = null;
        foreach ($rawRows as $index => $cells) {
            $map = self::mapHeader($cells);
            if (count($map) >= 5 && isset($map['title'], $map['branch'])) {
                $headers = $map;
                $headerIndex = $index;
                break;
            }
        }

        $missing = [];
        foreach (['title', 'branch', 'description'] as $required) {
            if (!isset($headers[$required])) {
                $missing[] = $required;
            }
        }
        if ($headerIndex === null || $missing !== []) {
            return ['delimiter' => $delimiter, 'headers' => $headers, 'rows' => [], 'missing' => $missing];
        }

        $rows = [];
        $lineNumber = $headerIndex + 1;
        foreach (array_slice($rawRows, $headerIndex + 1) as $cells) {
            $lineNumber++;
            $row = ['_line' => (string) $lineNumber];
            $hasValue = false;
            foreach ($headers as $key => $columnIndex) {
                $value = trim((string) ($cells[$columnIndex] ?? ''));
                // Excel lưu text bằng dấu nháy đơn ở đầu khi người dùng gõ "'..."
                if (str_starts_with($value, "'")) {
                    $value = ltrim($value, "'");
                }
                $row[$key] = $value;
                if ($value !== '') {
                    $hasValue = true;
                }
            }
            if (!$hasValue) {
                continue;
            }
            // Dòng ghi chú (#) hoặc dòng ví dụ [MẪU] — bỏ qua.
            $firstCell = trim((string) ($cells[0] ?? ''));
            if ($firstCell !== '' && str_starts_with($firstCell, '#')) {
                continue;
            }
            $title = (string) ($row['title'] ?? '');
            if (mb_stripos($title, '[MẪU]') === 0 || mb_stripos($title, '[MAU]') === 0) {
                continue;
            }
            $rows[] = $row;
        }

        return ['delimiter' => $delimiter, 'headers' => $headers, 'rows' => $rows, 'missing' => []];
    }

    /** Chuẩn hoá chuỗi để so khớp: bỏ dấu, bỏ khoảng trắng/ký tự đặc biệt, lowercase. */
    public static function norm(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ]);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim($value);
    }

    /** Dùng để dò tên khu sản xuất: bỏ tiền tố "khu vực", "nhà máy", "khu", "chi nhánh". */
    public static function branchKey(string $value): string
    {
        $key = self::norm($value);
        $key = preg_replace('/^(khu vuc|nha may|khu|chi nhanh)\s+/u', '', $key) ?? $key;

        return trim($key);
    }

    /**
     * Chuẩn hoá "khoảng lương" từ nhiều cách ghi về enum của DB.
     * Chấp nhận: "8-12", "8 – 12 triệu", "dưới 8 triệu", "trên 18 triệu"…
     */
    public static function salaryRange(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $allowed = ['under-8', '8-12', '12-18', 'over-18'];
        if (in_array($value, $allowed, true)) {
            return $value;
        }
        $normalized = self::norm($value);
        if (preg_match('/duoi\s*8|duoi\s*tam|hoc viec/', $normalized) === 1) {
            return 'under-8';
        }
        if (preg_match('/tren\s*18|tren\s*muoi\s*tam/', $normalized) === 1) {
            return 'over-18';
        }
        if (preg_match('/(\d+)\s*(?:-|den|toi)\s*(\d+)/', $normalized, $m) === 1) {
            $min = (int) $m[1];
            if ($min < 8) {
                return 'under-8';
            }
            if ($min < 12) {
                return '8-12';
            }
            if ($min < 18) {
                return '12-18';
            }
            return 'over-18';
        }

        return '';
    }

    /** Chuẩn hoá trạng thái: draft/published/…; mặc định draft. */
    public static function status(string $value): string
    {
        $value = self::norm($value);
        if ($value === '') {
            return 'draft';
        }

        return match (true) {
            str_contains($value, 'tam dung') || str_contains($value, 'pause') => 'paused',
            str_contains($value, 'dong') || str_contains($value, 'close') => 'closed',
            str_contains($value, 'published') || str_contains($value, 'publish') => 'published',
            str_contains($value, 'dang tuyen') || $value === 'dang' || str_contains($value, 'dang tin') => 'published',
            default => 'draft',
        };
    }

    /** Cờ 1/0, có/không, true/false, x… */
    public static function flag(string $value): int
    {
        $value = self::norm($value);

        return in_array($value, ['1', 'co', 'yes', 'true', 'x', 'uu tien', 'tuyen gap'], true) ? 1 : 0;
    }

    /** Ngày giờ: nhận dd/mm/yyyy (mặc định 08:00) hoặc yyyy-mm-dd, có/không kèm giờ. */
    public static function dateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $serial = self::excelSerial($value);
        if ($serial !== null) {
            $ts = (int) round(($serial - 25569) * 86400);

            return gmdate('Y-m-d', $ts) . ' 08:00';
        }
        $hasTime = preg_match('/\d{1,2}:\d{2}/', $value) === 1;
        $date = self::parseDate($value, $hasTime);
        if ($date === null) {
            return $value; // để validate báo lỗi
        }

        return $date->format($hasTime ? 'Y-m-d H:i' : 'Y-m-d') . ($hasTime ? '' : ' 08:00');
    }

    /** Ngày: nhận dd/mm/yyyy hoặc yyyy-mm-dd; trả về yyyy-mm-dd. */
    public static function date(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $serial = self::excelSerial($value);
        if ($serial !== null) {
            return gmdate('Y-m-d', (int) round(($serial - 25569) * 86400));
        }
        $date = self::parseDate($value, false);

        return $date === null ? $value : $date->format('Y-m-d');
    }

    /**
     * Phân tích ngày theo nhiều định dạng: dd/mm/yyyy (ưu tiên), dd-mm-yyyy, yyyy-mm-dd…
     * Trả null nếu không hợp lệ (để validate báo lỗi).
     */
    private static function parseDate(string $value, bool $hasTime): ?\DateTimeImmutable
    {
        $value = trim($value);
        $dateFormats = ['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'Y/m/d', 'j/n/Y', 'j-n-Y'];
        $timeFormats = ['H:i:s', 'H:i'];
        $formats = [];
        if ($hasTime) {
            foreach ($dateFormats as $dateFormat) {
                foreach ($timeFormats as $timeFormat) {
                    $formats[] = $dateFormat . ' ' . $timeFormat;
                }
            }
        }
        $formats = array_merge($formats, $dateFormats);

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date instanceof \DateTimeImmutable) {
                $errors = \DateTimeImmutable::getLastErrors();
                if ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) {
                    return $date;
                }
            }
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : (new \DateTimeImmutable())->setTimestamp($timestamp);
    }

    /**
     * Ô Excel định dạng ngày lưu thành số serial (1900 date system).
     * Chỉ nhận khoảng hợp lệ 20000–80000 (1954 → 2119) để tránh nhầm số lượng/lương.
     */
    private static function excelSerial(string $value): ?float
    {
        if (preg_match('/^\d+(?:[.,]\d+)?$/', $value) !== 1) {
            return null;
        }
        $number = (float) str_replace(',', '.', $value);
        if ($number < 20000 || $number > 80000) {
            return null;
        }

        return $number;
    }

    /** @param list<string|int|float|null> $cells */
    private static function csvLine(array $cells): string
    {
        $escaped = [];
        foreach ($cells as $cell) {
            $value = (string) $cell;
            if (strpbrk($value, ";,\"\r\n") !== false) {
                $escaped[] = '"' . str_replace('"', '""', $value) . '"';
            } else {
                $escaped[] = $value;
            }
        }

        return implode(';', $escaped);
    }

    /** @param list<string> $cells @return array<string, int> */
    private static function mapHeader(array $cells): array
    {
        $map = [];
        foreach ($cells as $index => $cell) {
            $raw = (string) $cell;
            // Bỏ phần gợi ý trong ngoặc: "Ngày đăng (dd/mm/yyyy)" -> "Ngày đăng".
            $withoutNote = preg_replace('/\([^)]*\)/u', ' ', $raw) ?? $raw;
            $candidates = array_unique(array_filter([
                self::norm($raw),
                self::norm($withoutNote),
                self::norm(str_replace('*', '', $withoutNote)),
            ]));
            if ($candidates === []) {
                continue;
            }
            foreach (self::COLUMNS as $key => [, $aliases]) {
                if (isset($map[$key])) {
                    continue;
                }
                foreach ($aliases as $alias) {
                    foreach ($candidates as $header) {
                        if ($header === $alias) {
                            $map[$key] = $index;
                            break 3;
                        }
                    }
                }
            }
        }

        return $map;
    }

    private static function detectDelimiter(string $content): string
    {
        $firstLine = '';
        foreach (preg_split('/\r\n|\n|\r/', $content) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '' && !str_starts_with($trimmed, '#')) {
                $firstLine = $trimmed;
                break;
            }
        }
        if ($firstLine === '') {
            return ',';
        }

        return substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
    }
}
