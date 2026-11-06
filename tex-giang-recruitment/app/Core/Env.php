<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Đọc file .env đơn giản thay cho vlucas/phpdotenv.
 * Hỗ trợ: KEY=value, KEY="value có dấu cách", bỏ qua dòng # và dòng trống.
 */
final class Env
{
    public static function load(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $len = strlen($value);
            if ($len >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[$len - 1] === $value[0]) {
                $value = substr($value, 1, -1);
            }
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}
