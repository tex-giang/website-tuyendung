<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Đọc/ghi XLSX tối giản (chỉ dùng extension zip + SimpleXML, không cần Composer).
 *
 * - write(): tạo file .xlsx (nhiều sheet, header in đậm, freeze dòng tiêu đề,
 *   độ rộng cột, data validation dạng list).
 * - read(): đọc sheet đầu tiên thành mảng 2 chiều (giá trị dạng chuỗi),
 *   hỗ trợ file do Excel/LibreOffice lưu (sharedStrings, inlineStr, số).
 */
final class Xlsx
{
    /**
     * @param list<array{
     *   name: string,
     *   rows: list<list<string|int|float|null>>,
     *   widths?: list<float>,
     *   header?: bool,
     *   freeze_row?: int|null,
     *   validations?: list<array{range: string, options: list<string>}>
     * }> $sheets
     */
    public static function write(array $sheets): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP thiếu extension zip — không tạo được file Excel.');
        }

        $files = [
            '[Content_Types].xml' => self::contentTypesXml(count($sheets)),
            '_rels/.rels' => self::rootRelsXml(),
            'xl/workbook.xml' => self::workbookXml($sheets),
            'xl/_rels/workbook.xml.rels' => self::workbookRelsXml(count($sheets)),
            'xl/styles.xml' => self::stylesXml(),
        ];
        foreach ($sheets as $index => $sheet) {
            $files['xl/worksheets/sheet' . ($index + 1) . '.xml'] = self::sheetXml($sheet);
        }

        $temp = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($temp === false) {
            throw new RuntimeException('Không tạo được file tạm để ghi Excel.');
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($temp, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
                throw new RuntimeException('Không mở được file Excel để ghi.');
            }
            foreach ($files as $name => $content) {
                $zip->addFromString($name, $content);
            }
            $zip->close();

            $bytes = file_get_contents($temp);
            if ($bytes === false) {
                throw new RuntimeException('Không đọc lại được file Excel vừa tạo.');
            }

            return $bytes;
        } finally {
            if (is_file($temp)) {
                @unlink($temp);
            }
        }
    }

    /**
     * Đọc sheet đầu tiên của file .xlsx.
     *
     * @return list<list<string>> mỗi phần tử là 1 dòng, đã chuẩn hoá thành chuỗi
     */
    public static function read(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP thiếu extension zip — không đọc được file Excel.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel bị hỏng hoặc không đúng định dạng.');
        }

        try {
            $sheetName = 'xl/worksheets/sheet1.xml';
            if ($zip->locateName($sheetName) === false) {
                throw new RuntimeException('File Excel không có sheet dữ liệu.');
            }
            $sheetXml = $zip->getFromName($sheetName);
            // Sheet đầu tiên trong danh sách sheet của workbook (Excel có thể đặt tên khác sheet1).
            $workbook = $zip->getFromName('xl/workbook.xml');
            if (is_string($workbook) && preg_match('#<sheet[^>]*r:id="(rId\d+)"#', $workbook, $m) === 1) {
                $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
                if (is_string($rels)) {
                    $pattern = '#<Relationship[^>]*Id="' . preg_quote($m[1], '#') . '"[^>]*Target="([^"]+)"#';
                    if (preg_match($pattern, $rels, $target) === 1) {
                        $candidate = 'xl/' . ltrim(str_replace(['../', '\\'], ['', '/'], $target[1]), '/');
                        if ($zip->locateName($candidate) !== false) {
                            $sheetXml = $zip->getFromName($candidate);
                        }
                    }
                }
            }
            if (!is_string($sheetXml) || $sheetXml === '') {
                throw new RuntimeException('Không đọc được dữ liệu sheet trong file Excel.');
            }

            $shared = self::sharedStrings($zip);
        } finally {
            $zip->close();
        }

        return self::parseSheetXml($sheetXml, $shared);
    }

    /** @return list<string> */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (!is_string($xml) || $xml === '') {
            return [];
        }

        $dom = self::loadXml($xml);
        if ($dom === null) {
            return [];
        }

        $result = [];
        $xpath = new \DOMXPath($dom);
        /** @var \DOMNodeList $items */
        $items = $xpath->query('/*[local-name()="sst"]/*[local-name()="si"]');
        foreach ($items as $item) {
            $result[] = self::nodeText($item);
        }

        return $result;
    }

    /**
     * @param list<string> $shared
     * @return list<list<string>>
     */
    private static function parseSheetXml(string $xml, array $shared): array
    {
        $dom = self::loadXml($xml);
        if ($dom === null) {
            throw new RuntimeException('File Excel không đúng định dạng XML.');
        }

        $xpath = new \DOMXPath($dom);
        $rows = [];
        /** @var \DOMNodeList $rowNodes */
        $rowNodes = $xpath->query('/*[local-name()="worksheet"]/*[local-name()="sheetData"]/*[local-name()="row"]');
        foreach ($rowNodes as $rowNode) {
            $cells = [];
            $maxIndex = -1;
            /** @var \DOMNodeList $cellNodes */
            $cellNodes = $xpath->query('./*[local-name()="c"]', $rowNode);
            foreach ($cellNodes as $cell) {
                /** @var \DOMElement $cell */
                $reference = $cell->getAttribute('r');
                $columnIndex = $reference !== '' ? self::columnIndex($reference) : $maxIndex + 1;
                $maxIndex = max($maxIndex, $columnIndex);

                $type = $cell->getAttribute('t');
                $value = '';
                if ($type === 'inlineStr') {
                    /** @var \DOMNodeList $inlineTexts */
                    $inlineTexts = $xpath->query('.//*[local-name()="is"]', $cell);
                    $value = $inlineTexts->length > 0 ? self::nodeText($inlineTexts->item(0)) : '';
                } else {
                    /** @var \DOMNodeList $values */
                    $values = $xpath->query('./*[local-name()="v"]', $cell);
                    $raw = $values->length > 0 ? (string) $values->item(0)->textContent : '';
                    if ($type === 's') {
                        $index = (int) $raw;
                        $value = $shared[$index] ?? '';
                    } else {
                        $value = $raw;
                    }
                }

                $cells[$columnIndex] = trim($value);
            }

            if ($cells === []) {
                $rows[] = [];
                continue;
            }
            $width = max(array_keys($cells)) + 1;
            $line = array_fill(0, $width, '');
            foreach ($cells as $index => $value) {
                $line[$index] = $value;
            }
            $rows[] = $line;
        }

        return $rows;
    }

    private static function loadXml(string $xml): ?\DOMDocument
    {
        if (!class_exists(\DOMDocument::class)) {
            throw new RuntimeException('PHP thiếu extension dom — không đọc được file Excel.');
        }
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $dom : null;
    }

    /** Ghép toàn bộ text bên trong một node (hỗ trợ rich text nhiều thẻ <t>). */
    private static function nodeText(\DOMNode $node): string
    {
        $text = '';
        foreach ($node->getElementsByTagName('t') as $textNode) {
            $text .= $textNode->textContent;
        }
        // File có thể dùng <t> không thuộc namespace mặc định — quét cả theo local name.
        if ($text === '' && $node->ownerDocument !== null) {
            $xpath = new \DOMXPath($node->ownerDocument);
            $found = $xpath->query('.//*[local-name()="t"]', $node);
            if ($found !== false) {
                foreach ($found as $textNode) {
                    $text .= $textNode->textContent;
                }
            }
        }

        return $text;
    }

    /** "B12" -> 1 (0-based). */
    private static function columnIndex(string $reference): int
    {
        $letters = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $reference));
        $index = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    /** @param list<array{range: string, options: list<string>}> $validations */
    private static function sheetXml(array $sheet): string
    {
        $rows = $sheet['rows'] ?? [];
        $widths = $sheet['widths'] ?? [];
        $header = (bool) ($sheet['header'] ?? false);
        $freezeRow = $sheet['freeze_row'] ?? null;
        $validations = $sheet['validations'] ?? [];

        $body = '';
        foreach ($rows as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $body .= '<row r="' . $rowNumber . '">';
            foreach ($cells as $columnIndex => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $reference = self::columnLetter($columnIndex) . $rowNumber;
                $isHeader = $header && $rowNumber === 1;
                $numeric = !$isHeader && (is_int($value) || is_float($value));
                $styleId = $isHeader ? 1 : 2;
                if ($numeric) {
                    $body .= '<c r="' . $reference . '" s="' . $styleId . '"><v>' . self::xml((string) $value) . '</v></c>';
                } else {
                    $body .= '<c r="' . $reference . '" s="' . $styleId . '" t="inlineStr"><is><t xml:space="preserve">'
                        . self::xml((string) $value) . '</t></is></c>';
                }
            }
            $body .= '</row>';
        }

        $dimension = $rows === [] ? 'A1' : ('A1:' . self::columnLetter(max(array_map('count', $rows)) - 1) . count($rows));

        $cols = '';
        if ($widths !== []) {
            $cols = '<cols>';
            foreach ($widths as $index => $width) {
                $cols .= '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . (float) $width . '" customWidth="1"/>';
            }
            $cols .= '</cols>';
        }

        $pane = '';
        if (is_int($freezeRow) && $freezeRow > 0) {
            $topLeft = 'A' . ($freezeRow + 1);
            $pane = '<pane ySplit="' . $freezeRow . '" topLeftCell="' . $topLeft . '" activePane="bottomLeft" state="frozen"/>';
        }
        $sheetViews = '<sheetViews><sheetView workbookViewId="0" tabSelected="' . ($sheet['active'] ?? false ? '1' : '0') . '">'
            . $pane . '</sheetView></sheetViews>';

        $validationXml = '';
        if ($validations !== []) {
            $validationXml = '<dataValidations count="' . count($validations) . '">';
            foreach ($validations as $validation) {
                $options = implode(',', array_map(static fn(string $option): string => str_replace(',', ' ', $option), $validation['options']));
                $validationXml .= '<dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="1" sqref="'
                    . self::xml($validation['range']) . '"><formula1>"' . self::xml($options) . '"</formula1></dataValidation>';
            }
            $validationXml .= '</dataValidations>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="' . $dimension . '"/>'
            . $sheetViews
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $cols
            . '<sheetData>' . $body . '</sheetData>'
            . $validationXml
            . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            . '</worksheet>';
    }

    private static function columnLetter(int $index): string
    {
        $index++;
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = intdiv($index - $mod, 26);
        }

        return $letters;
    }

    private static function contentTypesXml(int $sheetCount): string
    {
        $overrides = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides
            . '</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    /** @param list<array{name: string}> $sheets */
    private static function workbookXml(array $sheets): string
    {
        $sheetXml = '';
        foreach ($sheets as $index => $sheet) {
            $sheetXml .= '<sheet name="' . self::xml($sheet['name']) . '" sheetId="' . ($index + 1) . '" r:id="rId' . ($index + 1) . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetXml . '</sheets></workbook>';
    }

    private static function workbookRelsXml(int $sheetCount): string
    {
        $rels = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . ($sheetCount + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels . '</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF087A55"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD5E3DA"/></left><right style="thin"><color rgb="FFD5E3DA"/></right>'
            . '<top style="thin"><color rgb="FFD5E3DA"/></top><bottom style="thin"><color rgb="FFD5E3DA"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
