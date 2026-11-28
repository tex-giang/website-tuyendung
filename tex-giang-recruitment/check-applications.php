<?php
// Diagnostic: what do the newest applications look like, and does the integration user
// see them? Read-only.
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Models\Application;
use App\Models\User;

$pdo = Database::connection();

echo "=== 5 hồ sơ mới nhất (toàn bộ DB) ===\n";
$rows = $pdo->query(
    'SELECT a.id, a.status, a.job_id, a.created_at, j.branch_id, j.title AS job_title
     FROM applications a LEFT JOIN jobs j ON j.id = a.job_id
     ORDER BY a.created_at DESC, a.id DESC LIMIT 5'
)->fetchAll(\PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    printf(
        "id=%-4s status=%-10s job=%-4s branch=%-4s created=%s %s\n",
        $row['id'],
        $row['status'],
        $row['job_id'] ?? '-',
        $row['branch_id'] ?? '-',
        $row['created_at'],
        $row['job_title'] ?? '(không rõ)'
    );
}

echo "\n=== Đếm theo status ===\n";
foreach ($pdo->query('SELECT status, COUNT(*) c FROM applications GROUP BY status') as $row) {
    printf("%-10s %s\n", $row['status'], $row['c']);
}

$userIds = array_filter(array_map('trim', explode(',', (string) env('APPLICATIONS_API_USER_ID', ''))));
echo "\n=== User tích hợp (APPLICATIONS_API_USER_ID = ", $userIds ? implode(',', $userIds) : '(trống)', ") ===\n";
foreach ($userIds as $id) {
    $user = User::find((int) $id);
    if ($user === null) {
        echo "user $id: KHÔNG TỒN TẠI\n";
        continue;
    }
    $canSee = \App\Core\Permissions::roleCan($user['role_name'], 'applications') ? 'có' : 'KHÔNG';
    echo "user {$id}: role={$user['role_name']} active={$user['is_active']} quyền applications={$canSee}\n";
    $scope = Application::paginate(['status' => 'new'], 1, 100, ['id' => (int) $user['id'], 'role' => $user['role_name']]);
    printf("  thấy %d hồ sơ status=new (ids: %s)\n", count($scope['items']), implode(',', array_column($scope['items'], 'id')));
    $all = Application::paginate([], 1, 100, ['id' => (int) $user['id'], 'role' => $user['role_name']]);
    printf("  thấy %d hồ sơ ở mọi status\n", count($all['items']));
}

echo "\n=== API nhìn thấy gì (không scope) ===\n";
$every = Application::paginate(['status' => 'new'], 1, 100);
printf("status=new tổng: %d (ids: %s)\n", count($every['items']), implode(',', array_column($every['items'], 'id')));
