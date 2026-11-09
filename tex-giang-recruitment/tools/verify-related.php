<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Models\Post;

$slugs = array_slice($argv, 1);
if ($slugs === []) {
    $slugs = ['tuyen-300-cong-nhan-tan-huong', 'qc-lam-gi-tren-chuyen-may', 'bat-dau-hoc-nghe-may'];
}

$failed = false;
foreach ($slugs as $slug) {
    $post = Post::findPublishedBySlug($slug);
    if ($post === null) {
        fwrite(STDERR, "MISSING post: {$slug}\n");
        $failed = true;
        continue;
    }

    $related = Post::related($post, 3);
    $ids = array_map(static fn (array $r): int => (int) $r['id'], $related);

    echo "CURRENT id={$post['id']} category_id={$post['category_id']} slug={$post['slug']}\n";
    echo 'RELATED count=' . count($related) . "\n";
    foreach ($related as $r) {
        echo "  - id={$r['id']} category_id={$r['category_id']} slug={$r['slug']}\n";
    }

    if (in_array((int) $post['id'], $ids, true)) {
        echo "FAIL: related contains current post ({$slug})\n";
        $failed = true;
    } else {
        echo "OK: related excludes current post ({$slug})\n";
    }
    echo str_repeat('-', 60) . "\n";
}

exit($failed ? 1 : 0);
