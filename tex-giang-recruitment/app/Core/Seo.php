<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Branch;

/**
 * SEO: URL tuyệt đối, canonical và dữ liệu có cấu trúc schema.org.
 *
 * Vì sao cần:
 *  - <link rel="canonical"> gộp các URL trùng (?page=, utm_source=...) về một địa chỉ,
 *    tránh Google chia nhỏ tín hiệu trên nhiều bản sao của cùng một trang.
 *  - robots.txt + sitemap.xml cho Google biết đường vào và trang nào còn mới.
 *  - JobPosting JSON-LD là nguồn duy nhất để tin tuyển dụng lên Google Jobs;
 *    thiếu nó thì tin chỉ nằm trong kết quả web thường.
 *
 * URL trong JSON-LD và sitemap phải tuyệt đối, lấy theo APP_URL trong .env
 * chứ không theo $_SERVER['HTTP_HOST'] — Host do client gửi nên có thể bị giả.
 */
final class Seo
{
    private const SCHEMA = 'https://schema.org';
    private const LOGO_PATH = 'assets/images/favicons/icon-512.png';

    /** Gốc website (không có dấu / ở cuối). */
    public static function base(): string
    {
        $configured = rtrim((string) env('APP_URL', ''), '/');
        if ($configured !== '') {
            return $configured;
        }

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return ($https ? 'https' : 'http') . '://' . $host;
    }

    /** Đường dẫn nội bộ -> URL tuyệt đối. Bỏ query string (?v=mtime của asset()). */
    public static function absolute(string $path = ''): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $clean = (string) (parse_url('/' . ltrim($path, '/'), PHP_URL_PATH) ?: '/');
        $clean = '/' . trim($clean, '/');

        return self::base() . ($clean === '/' ? '/' : $clean);
    }

    /** Canonical của trang hiện tại: bỏ toàn bộ query (bộ lọc, utm, page). */
    public static function canonical(): string
    {
        return self::absolute(url(ltrim(current_path(), '/')));
    }

    /** Gộp nhiều node vào một khối @graph — ít thẻ <script> hơn. */
    public static function tag(array $nodes): string
    {
        $nodes = array_values(array_filter($nodes, static fn(mixed $n): bool => is_array($n) && $n !== []));
        if ($nodes === []) {
            return '';
        }

        $json = json_encode(
            ['@context' => self::SCHEMA, '@graph' => $nodes],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        if ($json === false) {
            return '';
        }

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    // -----------------------------------------------------------------------
    // Node dùng chung
    // -----------------------------------------------------------------------

    /** Tổ chức tuyển dụng. $branch = khu đang hoạt động (lấy địa chỉ trụ sở). */
    public static function organization(?array $branch = null): array
    {
        $node = [
            '@type' => 'Organization',
            '@id'   => self::base() . '/#organization',
            'name'  => settings('legal_name', settings('site_name', 'TEX-GIANG')),
            'url'   => settings('website', self::base()),
            'logo'  => [
                '@type'  => 'ImageObject',
                'url'    => self::absolute(self::LOGO_PATH),
                'width'  => 512,
                'height' => 512,
            ],
        ];

        $email = settings('email');
        if ($email !== '') {
            $node['email'] = $email;
        }

        if ($branch !== null) {
            $node['address'] = self::postalAddress(
                (string) ($branch['address'] ?? ''),
                (string) ($branch['name'] ?? '')
            );
            $phone = trim((string) ($branch['phone'] ?? ''));
            if ($phone !== '') {
                $node['telephone'] = $phone;
            }
        }

        $facebook = settings('facebook');
        if ($facebook !== '' && $facebook !== 'https://www.facebook.com/') {
            $node['sameAs'] = [$facebook];
        }

        return $node;
    }

    public static function website(): array
    {
        return [
            '@type'      => 'WebSite',
            '@id'        => self::base() . '/#website',
            'url'        => self::base() . '/',
            'name'       => settings('site_name', 'Tuyển dụng TEX-GIANG'),
            'inLanguage' => 'vi-VN',
            'publisher'  => ['@id' => self::base() . '/#organization'],
        ];
    }

    /**
     * BreadcrumbList. Mỗi phần tử: [tên, đường dẫn|'' cho trang chủ|null cho trang hiện tại].
     * @param array<int, array{0:string, 1?:string|null}> $items
     */
    public static function breadcrumbs(array $items): array
    {
        $list = [];
        $position = 1;

        foreach ($items as $item) {
            $node = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => self::plain((string) $item[0]),
            ];

            $path = $item[1] ?? null;
            if ($path !== null) {
                $node['item'] = self::absolute((string) $path);
            }

            $list[] = $node;
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $list];
    }

    /**
     * ItemList cho trang danh sách.
     * @param array<int, array{url:string, name:string}> $entries
     */
    public static function itemList(array $entries, string $name): array
    {
        $list = [];
        $position = 1;

        foreach ($entries as $entry) {
            if (($entry['url'] ?? '') === '') {
                continue;
            }
            $list[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'url'      => self::absolute((string) $entry['url']),
                'name'     => self::plain((string) ($entry['name'] ?? '')),
            ];
        }

        if ($list === []) {
            return [];
        }

        return [
            '@type'           => 'ItemList',
            'name'            => self::plain($name),
            'numberOfItems'   => count($list),
            'itemListElement' => $list,
        ];
    }

    // -----------------------------------------------------------------------
    // JobPosting — nguồn của Google Jobs
    // -----------------------------------------------------------------------

    public static function jobPosting(array $job): array
    {
        $url = self::absolute(url('jobs/' . $job['slug']));
        // Tiêu đề trong DB có tiền tố số lượng ("300 Công nhân..."), bỏ khi đưa vào schema.
        $title = self::plain((string) preg_replace('/^\d+\s+/', '', (string) $job['title']));

        $node = [
            '@type'              => 'JobPosting',
            '@id'                => $url . '#jobposting',
            'title'              => $title,
            'description'        => self::descriptionHtml((string) ($job['description'] ?? '')),
            'datePosted'         => self::isoDateTime($job['posted_at'] ?: $job['created_at'] ?? null),
            'hiringOrganization' => [
                '@type'  => 'Organization',
                'name'   => settings('legal_name', settings('site_name', 'TEX-GIANG')),
                'sameAs' => settings('website', self::base()),
                'logo'   => self::absolute(self::LOGO_PATH),
            ],
            'jobLocation'        => self::jobLocation($job),
            'employmentType'     => self::employmentType(
                (string) ($job['employment_type'] ?? ''),
                $title
            ),
            'identifier'         => [
                '@type' => 'PropertyValue',
                'name'  => settings('legal_name', 'TEX-GIANG'),
                'value' => (string) ($job['code'] ?? '') !== '' ? (string) $job['code'] : (string) $job['id'],
            ],
            'directApply'        => true,
            'url'                => $url,
        ];

        $quantity = (int) ($job['quantity'] ?? 0);
        if ($quantity > 0) {
            $node['totalJobOpenings'] = $quantity;
        }

        if (!empty($job['deadline'])) {
            $ts = strtotime((string) $job['deadline'] . ' 23:59:59');
            if ($ts !== false) {
                $node['validThrough'] = date('c', $ts);
            }
        }

        $salary = self::baseSalary($job);
        if ($salary !== []) {
            $node['baseSalary'] = $salary;
        }

        $experience = trim(self::plain((string) ($job['experience'] ?? '')));
        if ($experience !== '') {
            $node['experienceRequirements'] = $experience;
        }

        $benefits = self::descriptionHtml((string) ($job['benefits'] ?? ''));
        if ($benefits !== '') {
            $node['jobBenefits'] = $benefits;
        }

        $requirements = self::descriptionHtml((string) ($job['requirements'] ?? ''));
        if ($requirements !== '') {
            $node['qualifications'] = $requirements;
        }

        return $node;
    }

    /** @return array<string, mixed> */
    private static function jobLocation(array $job): array
    {
        $street = trim((string) ($job['branch_address'] ?? ''));
        if ($street === '') {
            $street = trim((string) ($job['location'] ?? ''));
        }

        return [
            '@type'   => 'Place',
            'address' => self::postalAddress($street, (string) ($job['branch_name'] ?? '')),
        ];
    }

    /** @return array<string, mixed> */
    private static function postalAddress(string $street, string $locality): array
    {
        $node = ['@type' => 'PostalAddress', 'addressCountry' => 'VN'];

        if ($street !== '') {
            $node['streetAddress'] = $street;
        }
        if ($locality !== '') {
            $node['addressLocality'] = $locality;
        }

        // Tỉnh/thành nằm ở đoạn cuối địa chỉ: "..., Xã Bình Phú, Tỉnh Đồng Tháp".
        if ($street !== '') {
            $parts = array_values(array_filter(array_map('trim', explode(',', $street)), static fn(string $p): bool => $p !== ''));
            $last = $parts === [] ? '' : (string) end($parts);
            if ($last !== '' && preg_match('/^(Tỉnh|Thành phố|TP\.?)\s/iu', $last) === 1) {
                $node['addressRegion'] = $last;
            }
        }

        return $node;
    }

    /** @return array<string, mixed> */
    private static function baseSalary(array $job): array
    {
        $min = (int) ($job['salary_min'] ?? 0);
        $max = (int) ($job['salary_max'] ?? 0);

        if ($min <= 0 && $max <= 0) {
            return [];
        }

        $value = ['@type' => 'QuantitativeValue', 'unitText' => 'MONTH'];

        if ($min > 0 && $max > 0 && $min !== $max) {
            $value['minValue'] = $min;
            $value['maxValue'] = $max;
        } else {
            $value['value'] = $min > 0 ? $min : $max;
        }

        return [
            '@type'    => 'MonetaryAmount',
            'currency' => 'VND',
            'value'    => $value,
        ];
    }

    /** Nhãn tiếng Việt trong DB -> employmentType của schema.org. */
    private static function employmentType(string $label, string $title = ''): string
    {
        $hay = mb_strtolower($label . ' ' . $title, 'UTF-8');

        return match (true) {
            str_contains($hay, 'thực tập'),
            str_contains($hay, 'học nghề'),
            str_contains($hay, 'học việc') => 'INTERN',
            str_contains($hay, 'bán thời gian'),
            str_contains($hay, 'part-time') => 'PART_TIME',
            str_contains($hay, 'thời vụ'),
            str_contains($hay, 'tạm thời') => 'TEMPORARY',
            str_contains($hay, 'cộng tác'),
            str_contains($hay, 'freelance') => 'CONTRACTOR',
            default => 'FULL_TIME',
        };
    }

    // -----------------------------------------------------------------------
    // NewsArticle — bài tin tức
    // -----------------------------------------------------------------------

    public static function article(array $post): array
    {
        $url = self::absolute(url('news/' . $post['slug']));

        $node = [
            '@type'            => 'NewsArticle',
            '@id'              => $url . '#article',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'headline'         => self::plain((string) $post['title']),
            'datePublished'    => self::isoDateTime($post['published_at'] ?: $post['created_at'] ?? null),
            'dateModified'     => self::isoDateTime($post['updated_at'] ?? null),
            'inLanguage'       => 'vi-VN',
            'url'              => $url,
            'author'           => [
                '@type' => 'Organization',
                'name'  => settings('legal_name', settings('site_name', 'TEX-GIANG')),
                'url'   => self::base() . '/',
            ],
            'publisher'        => ['@id' => self::base() . '/#organization'],
        ];

        $excerpt = self::plain((string) ($post['excerpt'] ?? ''));
        if ($excerpt !== '') {
            $node['description'] = $excerpt;
        }

        $image = trim((string) ($post['image_path'] ?? ''));
        if ($image !== '') {
            $node['image'] = [self::absolute(asset(ltrim($image, '/')))];
        }

        $section = self::plain((string) ($post['category_name'] ?? ''));
        if ($section !== '') {
            $node['articleSection'] = $section;
        }

        return $node;
    }

    // -----------------------------------------------------------------------
    // Tiện ích
    // -----------------------------------------------------------------------

    /** Bỏ thẻ HTML, gộp khoảng trắng — dùng cho các trường chỉ nhận text. */
    public static function plain(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Mô tả dạng HTML cho JobPosting: giữ HTML nếu nội dung là HTML (CKEditor),
     * còn text thuần thì tách đoạn thành <p>.
     */
    private static function descriptionHtml(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if ($text !== strip_tags($text)) {
            return sanitize_html($text);
        }

        $html = '';
        foreach (preg_split('/\R{2,}/u', $text) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }
            $html .= '<p>' . nl2br(htmlspecialchars($paragraph, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
        }

        return $html;
    }

    private static function isoDateTime(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return date('c');
        }

        $ts = strtotime($value);

        return $ts === false ? date('c') : date('c', $ts);
    }
}
