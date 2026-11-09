<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\VisitStat;

/** Thống kê lượt truy cập: bảng theo ngày + thiết bị + biểu đồ 14 ngày. */
final class VisitStatController extends Controller
{
    private const PERIODS = [
        7   => '7 ngày',
        30  => '30 ngày',
        90  => '90 ngày',
        180 => '6 tháng',
        366 => '1 năm',
    ];

    public function index(): void
    {
        $days = (int) ($_GET['days'] ?? 30);
        if (!isset(self::PERIODS[$days])) {
            $days = 30;
        }

        $this->view('admin/visits/index', [
            'rows'    => VisitStat::byRange($days),
            'summary' => VisitStat::summary($days),
            'quick'   => VisitStat::quick(),
            'chart'   => VisitStat::chart(min($days, 14)),
            'days'    => $days,
            'periods' => self::PERIODS,
            'page'    => ['title' => 'Thống kê truy cập | ' . settings('site_name'), 'nav' => 'visits'],
        ], 'layouts/admin');
    }
}
