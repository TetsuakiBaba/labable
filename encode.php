<?php
require "sslkey.php";

$raw = file_get_contents('php://input'); // POSTされた生のデータを受け取る
$data = json_decode($raw); // json形式をphp変数に変換


$encoded_text = bin2hex(openssl_encrypt($data->token, 'AES-128-ECB', $sslkey, OPENSSL_RAW_DATA));
$res = [
    "token" => $encoded_text
];

echo json_encode($res);
?>