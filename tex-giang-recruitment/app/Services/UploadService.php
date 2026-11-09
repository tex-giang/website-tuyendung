<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Nhận tệp CV ứng viên: kiểm định dạng/kích thước, đổi tên ngẫu nhiên,
 * lưu NGOÀI public (storage/uploads/cv) và trả metadata để ghi DB.
 * File chỉ được tải xuống qua controller admin có kiểm tra đăng nhập.
 */
final class UploadService
{
    public const MAX_BYTES = 10 * 1024 * 1024; // 10 MB

    /** Định dạng cho phép: extension => danh sách MIME chấp nhận. */
    private const ALLOWED = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
    ];

    /**
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int}|null $file
     * @return array{ok:bool, data:array<string,mixed>, error:string}
     */
    public static function storeCv(?array $file): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'data' => [], 'error' => ''];
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            return self::fail(self::uploadErrorMessage($errorCode));
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return self::fail('Tệp đang trống. Vui lòng chọn tệp khác.');
        }
        if ($size > self::MAX_BYTES) {
            return self::fail('Tệp lớn hơn 10 MB. Vui lòng nén hoặc chọn tệp nhỏ hơn.');
        }

        $originalName = trim((string) ($file['name'] ?? ''));
        $originalName = basename(str_replace('\\', '/', $originalName));
        $originalName = mb_substr($originalName, 0, 255, 'UTF-8');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!isset(self::ALLOWED[$extension])) {
            return self::fail('Chỉ nhận tệp PDF, DOC, DOCX, JPG hoặc PNG.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return self::fail('Tệp tải lên không hợp lệ. Vui lòng thử lại.');
        }

        // Kiểm MIME thật bằng finfo, không tin Content-Type của trình duyệt.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);
        if (!in_array($mime, self::ALLOWED[$extension], true)) {
            return self::fail('Nội dung tệp không khớp định dạng. Vui lòng chọn tệp PDF/DOC/DOCX/JPG/PNG hợp lệ.');
        }

        $relativeDir = 'cv/' . date('Y-m');
        $absoluteDir = BASE_PATH . '/storage/uploads/' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            return self::fail('Không tạo được thư mục lưu tệp. Liên hệ quản trị viên.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $absoluteDir . '/' . $storedName;
        if (!move_uploaded_file($tmp, $target)) {
            return self::fail('Không lưu được tệp tải lên. Vui lòng thử lại.');
        }
        @chmod($target, 0644);

        return [
            'ok'    => true,
            'error' => '',
            'data'  => [
                'cv_original_name' => $originalName,
                'cv_stored_name'   => $relativeDir . '/' . $storedName,
                'cv_mime'          => $mime,
                'cv_size'          => $size,
            ],
        ];
    }

    public static function absolutePath(string $storedName): ?string
    {
        $storedName = str_replace('\\', '/', $storedName);
        if ($storedName === '' || str_contains($storedName, '..')) {
            return null;
        }
        $path = BASE_PATH . '/storage/uploads/' . ltrim($storedName, '/');
        return is_file($path) ? $path : null;
    }

    public static function delete(string $storedName): void
    {
        $path = self::absolutePath($storedName);
        if ($path !== null) {
            @unlink($path);
        }
    }

    private static function fail(string $message): array
    {
        return ['ok' => false, 'data' => [], 'error' => $message];
    }

    // ----------------------------------------------------------------- ẢNH CMS

    public const IMAGE_MAX_BYTES = 5 * 1024 * 1024;

    private const IMAGE_TYPES = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
    ];

    /**
     * Lưu ảnh cho banner/bài viết vào public/assets/uploads/{folder}/YYYY-MM/.
     * Trả về đường dẫn tương đối dạng "assets/uploads/..." để ghi DB
     * (helper asset() hiểu cả dạng có tiền tố assets/).
     *
     * @return array{ok:bool, path:string, error:string}
     */
    public static function storePublicImage(?array $file, string $folder, string $namePrefix = ''): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'path' => '', 'error' => ''];
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'path' => '', 'error' => self::uploadErrorMessage($errorCode)];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return ['ok' => false, 'path' => '', 'error' => 'Tệp ảnh đang trống.'];
        }
        if ($size > self::IMAGE_MAX_BYTES) {
            return ['ok' => false, 'path' => '', 'error' => 'Ảnh lớn hơn 5 MB. Vui lòng chọn ảnh nhỏ hơn.'];
        }

        $originalName = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!isset(self::IMAGE_TYPES[$extension])) {
            return ['ok' => false, 'path' => '', 'error' => 'Chỉ nhận ảnh JPG, PNG hoặc WebP.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'path' => '', 'error' => 'Ảnh tải lên không hợp lệ.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);
        if (!in_array($mime, self::IMAGE_TYPES[$extension], true)) {
            return ['ok' => false, 'path' => '', 'error' => 'Nội dung tệp không phải ảnh hợp lệ.'];
        }
        if (@getimagesize($tmp) === false) {
            return ['ok' => false, 'path' => '', 'error' => 'Không đọc được dữ liệu ảnh. Vui lòng chọn ảnh khác.'];
        }

        $folder = preg_replace('/[^a-z0-9_-]/', '', strtolower($folder)) ?: 'media';
        $relativeDir = 'assets/uploads/' . $folder . '/' . date('Y-m');
        $absoluteDir = BASE_PATH . '/public/' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            return ['ok' => false, 'path' => '', 'error' => 'Không tạo được thư mục lưu ảnh.'];
        }

        $prefix = preg_replace('/[^a-z0-9-]/', '', strtolower($namePrefix)) ?? '';
        $storedName = ($prefix !== ''
            ? $prefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4))
            : bin2hex(random_bytes(12))) . '.' . $extension;
        $target = $absoluteDir . '/' . $storedName;
        if (!move_uploaded_file($tmp, $target)) {
            return ['ok' => false, 'path' => '', 'error' => 'Không lưu được ảnh. Vui lòng thử lại.'];
        }
        @chmod($target, 0644);

        return ['ok' => true, 'path' => $relativeDir . '/' . $storedName, 'error' => ''];
    }

    /** Xoá ảnh CMS đã lưu (chỉ trong public/assets/uploads). */
    public static function deletePublicImage(?string $relativePath): void
    {
        $relativePath = str_replace('\\', '/', (string) $relativePath);
        if ($relativePath === '' || str_contains($relativePath, '..') || !str_starts_with($relativePath, 'assets/uploads/')) {
            return;
        }
        $path = BASE_PATH . '/public/' . $relativePath;
        $real = realpath($path);
        $root = realpath(BASE_PATH . '/public/assets/uploads');
        if ($real !== false && $root !== false && str_starts_with($real, $root) && is_file($real)) {
            @unlink($real);
        }
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Tệp lớn hơn giới hạn cho phép của máy chủ.',
            UPLOAD_ERR_PARTIAL   => 'Tệp chỉ tải lên được một phần. Vui lòng thử lại.',
            UPLOAD_ERR_NO_TMP_DIR => 'Máy chủ thiếu thư mục tạm. Liên hệ quản trị viên.',
            UPLOAD_ERR_CANT_WRITE => 'Máy chủ không ghi được tệp. Liên hệ quản trị viên.',
            UPLOAD_ERR_EXTENSION  => 'Tải tệp bị chặn bởi cấu hình máy chủ.',
            default               => 'Không tải được tệp. Vui lòng thử lại.',
        };
    }
}
