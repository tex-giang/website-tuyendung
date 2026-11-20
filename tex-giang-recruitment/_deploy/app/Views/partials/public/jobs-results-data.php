<?php
/**
 * Tính dữ liệu hiển thị dùng CHUNG cho trang /jobs và fragment thay tại chỗ.
 * View nhận: $filters, $result, $query, $branches, $blocks, $categories.
 *
 * Trả về mảng gồm: chips, page_url (closure), start, end, has_filter, has_query.
 * Không in gì ra output — chỉ tính toán.
 */
$query = $query ?? [];
$filters = $filters + [
    'q' => '', 'location' => '', 'block' => '', 'department' => '', 'sort' => 'newest',
];

$hasFilter = $filters['q'] !== '' || $filters['location'] !== ''
    || $filters['block'] !== '' || $filters['department'] !== '';

$chips = [];
if ($filters['q'] !== '') {
    $chips['q'] = 'Từ khoá: ' . $filters['q'];
}
if ($filters['location'] !== '' && isset($branches[$filters['location']])) {
    $chips['location'] = 'Khu ' . $branches[$filters['location']];
}
if ($filters['block'] !== '' && isset($blocks[$filters['block']])) {
    $chips['khoi'] = 'Khối ' . $blocks[$filters['block']];
}
if ($filters['department'] !== '' && isset($categories[$filters['department']])) {
    $chips['department'] = $categories[$filters['department']];
}

/** URL giữ nguyên bộ lọc hiện tại, chỉ đổi vài tham số. */
$pageUrl = static function (array $overrides) use ($query): string {
    $merged = array_merge($query, $overrides);
    $merged = array_filter($merged, static fn($v): bool => $v !== null && $v !== '');
    $qs = http_build_query($merged);
    return url('jobs') . ($qs === '' ? '' : '?' . $qs);
};

$start = $result['total'] === 0 ? 0 : (($result['page'] - 1) * $result['per_page']) + 1;
$end = min($result['page'] * $result['per_page'], $result['total']);

return [
    'has_filter' => $hasFilter,
    'chips'      => $chips,
    'page_url'   => $pageUrl,
    'start'      => $start,
    'end'        => $end,
];
