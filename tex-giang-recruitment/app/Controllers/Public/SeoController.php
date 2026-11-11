<?php
declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Seo;
use App\Models\Job;
use App\Models\Post;

/**
 * robots.txt + sitemap.xml.
 *
 * Sinh động từ database nên tin tuyển dụng / bài viết mới tự vào sitemap,
 * không phải sửa file tay mỗi lần đăng bài.
 */
final class SeoController extends Controller
{
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');

        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Khu quản trị: không có nội dung cho người tìm việc, chặn để đỡ tốn crawl.',
            'Disallow: /admin',
            '',
            '# Trang kết thúc luồng ứng tuyển: mở trực tiếp cũng không có nghĩa.',
            'Disallow: /apply/success',
            'Disallow: /apply/error',
            '',
            '# Ảnh dùng cho giao diện, không phải ảnh nội dung.',
            'Disallow: /assets/vendor/',
            '',
            'Sitemap: ' . Seo::absolute(url('sitemap.xml')),
        ];

        echo implode("\n", $lines) . "\n";
    }

    public function sitemap(): void
    {
        Job::syncExpired();

        /** @var array<string, array{lastmod:string, changefreq:string, priority:string}> $urls */
        $urls = [];

        $add = static function (string $path, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.6') use (&$urls): void {
            $loc = Seo::absolute(url(ltrim($path, '/')));
            $stamp = '';

            if ($lastmod !== null && trim($lastmod) !== '') {
                $ts = strtotime($lastmod);
                if ($ts !== false) {
                    $stamp = date('Y-m-d', $ts);
                }
            }

            $urls[$loc] = [
                'lastmod'    => $stamp,
                'changefreq' => $changefreq,
                'priority'   => $priority,
            ];
        };

        // --- Trang tĩnh ---------------------------------------------------
        $add('', null, 'daily', '1.0');
        $add('jobs', null, 'daily', '0.9');
        $add('news', null, 'daily', '0.8');
        $add('about', null, 'monthly', '0.6');
        $add('why-tex-giang', null, 'monthly', '0.6');
        $add('contact', null, 'monthly', '0.6');

        // --- Tin tuyển dụng đang đăng -------------------------------------
        try {
            foreach (Job::paginate(['sort' => 'newest'], 1, 500)['items'] as $job) {
                $add(
                    'jobs/' . $job['slug'],
                    (string) ($job['updated_at'] ?? '') !== '' ? (string) $job['updated_at'] : (string) ($job['posted_at'] ?? ''),
                    'weekly',
                    '0.9'
                );
            }
        } catch (\Throwable $e) {
            error_log('[TEX-GIANG] Sitemap: không đọc được jobs — ' . $e->getMessage());
        }

        // --- Bài viết đã đăng ---------------------------------------------
        try {
            foreach (Post::paginate(1, 500)['items'] as $post) {
                $add(
                    'news/' . $post['slug'],
                    (string) ($post['updated_at'] ?? '') !== '' ? (string) $post['updated_at'] : (string) ($post['published_at'] ?? ''),
                    'monthly',
                    '0.7'
                );
            }
        } catch (\Throwable $e) {
            error_log('[TEX-GIANG] Sitemap: không đọc được posts — ' . $e->getMessage());
        }

        header('Content-Type: application/xml; charset=utf-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $loc => $meta) {
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>' . "\n";
            if ($meta['lastmod'] !== '') {
                echo '    <lastmod>' . $meta['lastmod'] . '</lastmod>' . "\n";
            }
            echo '    <changefreq>' . $meta['changefreq'] . '</changefreq>' . "\n";
            echo '    <priority>' . $meta['priority'] . '</priority>' . "\n";
            echo '  </url>' . "\n";
        }

        echo '</urlset>' . "\n";
    }
}
