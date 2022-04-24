<?php 
require "sslkey.php";

$raw = file_get_contents('php://input'); // POSTされた生のデータを受け取る
$data = json_decode($raw); // json形式をphp変数に変換

$posturl = openssl_decrypt(hex2bin($data->posturl), 'AES-128-ECB', $sslkey, OPENSSL_RAW_DATA);


$message = [
    "channel" => $data->channel,
    "username" => $data->name,
    "text" => $data->text,
    "icon_emoji" => $data->icon_emoji
];

$ch = curl_init();
$options = [
    CURLOPT_URL => $posturl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'payload' => json_encode($message)
    ])
];
curl_setopt_array($ch, $options);
$ret_exec = curl_exec($ch);
curl_close($ch);

$res = [
    "message" => "#".$data->channel." に送信が完了しました。"
];

echo json_encode($res);
?>

