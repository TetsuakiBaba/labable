<?php
/**
 * 在室者一覧を返す。日付を跨いで入りっぱなしのユーザは自動で退出扱いにする。
 */
declare(strict_types=1);
require __DIR__ . '/common.php';

$data = labable_read_request();
$webhook = labable_require_webhook($data);
$token = labable_str($data, 'posturl', LABABLE_TOKEN_MAX_LEN);

$usersFile = labable_data_path($webhook, '.json', $token);
$users = labable_read_json($usersFile);

if ($users === null) {
    labable_respond(['users' => json_encode('')]);
}

$today = getdate()['mday'];
$users = array_values(array_filter($users, function ($user) use ($today) {
    return getdate((int)($user['timestamp'] ?? 0))['mday'] === $today;
}));
labable_write_json($usersFile, $users);

labable_respond(['users' => json_encode($users, JSON_UNESCAPED_UNICODE)]);
