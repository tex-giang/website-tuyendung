<?php
/**
 * Đóng gói các file cần upload lên production thành thư mục _deploy/,
 * giữ nguyên cấu trúc thư mục để copy thẳng vào hosting.
 *
 *   php tools/build-deploy.php
 */
declare(strict_types = 1);

require dirname(__DIR__) . '/app/bootstrap.php';

$files = [
    // --- Model
    'app/Models/Job.php',
    'app/Models/Category.php',
    // --- Controller
    'app/Controllers/Public/JobController.php',
    'app/Controllers/Admin/JobController.php',
    'app/Controllers/Admin/TaxonomyController.php',
    // --- Core (AJAX fragment)
    'app/Core/Controller.php',
    'app/Core/Response.php',
    // --- Service
    'app/Services/JobImport.php',
    // --- Views: public
    'app/Views/public/jobs/index.php',
    'app/Views/partials/public/jobs-filter-fields.php',
    'app/Views/partials/public/jobs-results.php',
    'app/Views/partials/public/jobs-results-data.php',
    'app/Views/partials/public/jobs-fragment.php',
    // --- Views: admin
    'app/Views/admin/jobs/form.php',
    'app/Views/admin/jobs/index.php',
    'app/Views/admin/categories/form.php',
    'app/Views/admin/categories/index.php',
    'app/Views/layouts/admin.php',
    // --- Assets
    'public/assets/js/jobs.js',
    'public/assets/js/admin-job-block.js',
    'public/assets/css/public.css',
    'public/assets/css/admin-app.css',
    // --- Migration
    'database/migrations/2026-11-19-khoi-tuyen-dung-PRODUCTION.sql',
];

$deployDir = BASE_PATH . '/_deploy';

// Dọn thư mục cũ.
if (is_dir($deployDir)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($deployDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($deployDir);
}

$copied = 0;
$missing = [];
$totalBytes = 0;

foreach ($files as $rel) {
    $src = BASE_PATH . '/' . $rel;
    if (!is_file($src)) {
        $missing[] = $rel;
        continue;
    }
    $dst = $deployDir . '/' . $rel;
    if (!is_dir(dirname($dst)) && !mkdir(dirname($dst), 0775, true) && !is_dir(dirname($dst))) {
        fwrite(STDERR, "Không tạo được thư mục: " . dirname($dst) . "\n");
        exit(1);
    }
    copy($src, $dst);
    $copied++;
    $totalBytes += filesize($src);
}

echo "Đã đóng gói {$copied} file vào _deploy/ (" . number_format($totalBytes / 1024, 1) . " KB)\n";
if ($missing !== []) {
    echo "THIẾU (" . count($missing) . "):\n";
    foreach ($missing as $m) {
        echo "  - $m\n";
    }
}
exit($missing === [] ? 0 : 1);
