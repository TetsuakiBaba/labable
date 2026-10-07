<?php
/**
 * 入退室の通知を Slack に送り、在室者一覧と入室回数を更新する。
 */
declare(strict_types=1);
require __DIR__ . '/common.php';

$data = labable_read_request();
$webhook = labable_require_webhook($data);
$token = labable_str($data, 'posturl', LABABLE_TOKEN_MAX_LEN);

$name = labable_str($data, 'name', 200);
$channel = labable_str($data, 'channel', 200);
$text = labable_str($data, 'text', 2000);
$iconEmoji = labable_str($data, 'icon_emoji', 100);
$inout = labable_str($data, 'inout', 10) === 'in' ? 'in' : 'out';
$timestamp = (int)labable_str($data, 'timestamp', 20);

if ($name === '') {
    labable_fail('name is required');
}

$usersFile = labable_data_path($webhook, '.json', $token);
$scoreFile = labable_data_path($webhook, '_score.json', $token);

/** 入室回数を取得する（未登録なら 0 で登録） */
function getScore(string $scoreFile, string $name): int
{
    $scores = labable_read_json($scoreFile) ?? [];
    foreach ($scores as $score) {
        if (($score['name'] ?? null) === $name) {
            return (int)($score['score'] ?? 0);
        }
    }
    $scores[] = ['name' => $name, 'score' => 0];
    labable_write_json($scoreFile, $scores);
    return 0;
}

/** 入室回数を 1 増やして返す */
function addScore(string $scoreFile, string $name): int
{
    $scores = labable_read_json($scoreFile) ?? [];
    foreach ($scores as &$score) {
        if (($score['name'] ?? null) === $name) {
            $score['score'] = (int)($score['score'] ?? 0) + 1;
            labable_write_json($scoreFile, $scores);
            return $score['score'];
        }
    }
    unset($score);
    $scores[] = ['name' => $name, 'score' => 1];
    labable_write_json($scoreFile, $scores);
    return 1;
}

$score = getScore($scoreFile, $name);
if ($inout === 'in') {
    $message = [
        'channel' => $channel,
        'username' => $name,
        'text' => '[IN] ' . $text . ' (入室回数: ' . ($score + 1) . ')',
        'icon_emoji' => $iconEmoji,
    ];
} else {
    $message = [
        'channel' => $channel,
        'username' => $name,
        'text' => '[OUT] ' . $text,
        'icon_emoji' => $iconEmoji,
    ];
}

$sent = labable_post_to_slack($webhook, $message);

$users = labable_read_json($usersFile) ?? [];
if ($inout === 'in') {
    $score = addScore($scoreFile, $name);
    $users[] = ['name' => $name, 'timestamp' => $timestamp, 'text' => $text, 'score' => $score];
} else {
    $users = array_values(array_filter($users, function ($user) use ($name) {
        return ($user['name'] ?? null) !== $name;
    }));
}

// 重複を除去
$users = array_reduce($users, function ($carry, $item) {
    if (!in_array($item, $carry, true)) {
        $carry[] = $item;
    }
    return $carry;
}, []);

labable_write_json($usersFile, $users);

labable_respond([
    'message' => $sent
        ? '#' . $channel . ' に送信が完了しました。'
        : '#' . $channel . ' への送信に失敗しました。Webhook URL を確認してください。',
    'users' => json_encode($users, JSON_UNESCAPED_UNICODE),
]);
