<?php
/**
 * getStart.html から呼ばれ、Slack Incoming Webhook URL を暗号化トークンにして返す。
 * hooks.slack.com の Webhook URL 以外は暗号化しない（任意 URL のトークン発行 = SSRF を防ぐ）。
 */
declare(strict_types=1);
require __DIR__ . '/common.php';

$data = labable_read_request();
$url = trim(labable_str($data, 'token', 512));

if (!labable_is_webhook_url($url)) {
    labable_fail('Slack の Incoming Webhook URL（https://hooks.slack.com/services/... ）を入力してください。', 400);
}

labable_respond(['token' => labable_encode_token($url)]);
