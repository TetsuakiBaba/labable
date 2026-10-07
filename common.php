<?php
/**
 * labable 共通処理
 *  - Webhook URL の暗号化トークン発行／復号（AES-256-GCM、旧 AES-128-ECB トークンは復号のみ対応）
 *  - リクエストの検証
 *  - 入退室データファイルのパス解決（data/ 配下、ハッシュ名）
 */
declare(strict_types=1);

require __DIR__ . '/sslkey.php';

const LABABLE_DATA_DIR = __DIR__ . '/data';
const LABABLE_WEBHOOK_PATTERN = '#\Ahttps://hooks\.slack\.com/services/[A-Za-z0-9_/\-]+\z#';
const LABABLE_TOKEN_MAX_LEN = 1024;   // hex 文字数の上限
const LABABLE_BODY_MAX_LEN = 65536;   // リクエストボディの上限（bytes）

/** JSON を返して終了する */
function labable_respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/** エラーを返して終了する */
function labable_fail(string $message, int $status = 400): void
{
    labable_respond(['error' => $message, 'message' => $message], $status);
}

/**
 * JSON ボディを持つ POST リクエストを読み込む。
 * フォーム送信（CSRF）を防ぐため Content-Type: application/json を必須にする。
 */
function labable_read_request(): object
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        labable_fail('POST only', 405);
    }
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== 0) {
        labable_fail('Content-Type must be application/json', 415);
    }
    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > LABABLE_BODY_MAX_LEN) {
        labable_fail('request body too large', 413);
    }
    $data = json_decode($raw);
    if (!is_object($data)) {
        labable_fail('invalid JSON');
    }
    return $data;
}

/** リクエストから文字列フィールドを取り出す（未指定・非スカラーは空文字、長さ制限あり） */
function labable_str(object $data, string $key, int $maxLen = 1000): string
{
    $value = $data->$key ?? '';
    if (!is_scalar($value)) {
        return '';
    }
    return mb_substr((string)$value, 0, $maxLen, 'UTF-8');
}

/** Slack Incoming Webhook の URL かどうか */
function labable_is_webhook_url(string $url): bool
{
    return preg_match(LABABLE_WEBHOOK_PATTERN, $url) === 1;
}

function labable_gcm_key(): string
{
    global $sslkey;
    return hash('sha256', (string)$sslkey, true);
}

/**
 * Webhook URL を暗号化してトークン（hex 文字列）にする。
 * 形式: hex( iv(12byte) || tag(16byte) || ciphertext )  AES-256-GCM
 */
function labable_encode_token(string $url): string
{
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($url, 'aes-256-gcm', labable_gcm_key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($cipher === false) {
        throw new RuntimeException('encryption failed');
    }
    return bin2hex($iv . $tag . $cipher);
}

/**
 * トークンを復号して Webhook URL を返す。
 * 復号できない、または hooks.slack.com 以外の URL なら null。
 */
function labable_decode_token(string $token): ?string
{
    $len = strlen($token);
    if ($len === 0 || $len > LABABLE_TOKEN_MAX_LEN || $len % 2 !== 0 || !ctype_xdigit($token)) {
        return null;
    }
    $bin = hex2bin($token);
    if ($bin === false) {
        return null;
    }

    // 現行形式: AES-256-GCM（改ざん検知付き）
    if (strlen($bin) > 28) {
        $url = openssl_decrypt(
            substr($bin, 28),
            'aes-256-gcm',
            labable_gcm_key(),
            OPENSSL_RAW_DATA,
            substr($bin, 0, 12),
            substr($bin, 12, 16)
        );
        if (is_string($url) && labable_is_webhook_url($url)) {
            return $url;
        }
    }

    // 旧形式: AES-128-ECB（配布済みリンクとの互換用。復号後の URL も必ず検証する）
    global $sslkey;
    $url = openssl_decrypt($bin, 'AES-128-ECB', (string)$sslkey, OPENSSL_RAW_DATA);
    if (is_string($url) && labable_is_webhook_url($url)) {
        return $url;
    }

    return null;
}

/**
 * リクエストの posturl を検証・復号して Webhook URL を返す。不正なら 400 で終了。
 */
function labable_require_webhook(object $data): string
{
    $token = labable_str($data, 'posturl', LABABLE_TOKEN_MAX_LEN);
    $url = labable_decode_token($token);
    if ($url === null) {
        labable_fail('invalid link: 無効なリンクです。アクセスリンク作成ページから作り直してください。', 400);
    }
    return $url;
}

/**
 * 入退室データファイルのパス。data/<sha256(webhook)><suffix>
 * 旧配置（プロジェクト直下の <token><suffix>）が残っていれば data/ へ移動する。
 */
function labable_data_path(string $webhook, string $suffix, string $token = ''): string
{
    if (!is_dir(LABABLE_DATA_DIR)) {
        @mkdir(LABABLE_DATA_DIR, 0700, true);
    }
    $path = LABABLE_DATA_DIR . '/' . hash('sha256', $webhook) . $suffix;

    if (!file_exists($path) && $token !== '' && ctype_xdigit($token)) {
        $legacy = __DIR__ . '/' . $token . $suffix;
        if (is_file($legacy)) {
            @rename($legacy, $path);
        }
    }
    return $path;
}

/** JSON ファイルを配列として読む。無ければ null */
function labable_read_json(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }
    $raw = mb_convert_encoding($raw, 'UTF-8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function labable_write_json(string $path, array $value): void
{
    file_put_contents($path, json_encode($value, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/** Slack Incoming Webhook へメッセージを送る。成功なら true */
function labable_post_to_slack(string $webhook, array $message): bool
{
    if (!labable_is_webhook_url($webhook)) {
        return false;
    }
    $ch = curl_init($webhook);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'payload' => json_encode($message, JSON_UNESCAPED_UNICODE),
        ]),
    ]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return $body !== false && $status >= 200 && $status < 300;
}
