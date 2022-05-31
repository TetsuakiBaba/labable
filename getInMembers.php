<?php
require "sslkey.php";

$raw = file_get_contents('php://input'); // POSTされた生のデータを受け取る
$data = json_decode($raw); // json形式をphp変数に変換

$result = "";

if ($json_string = @file_get_contents($data->posturl . ".json")) {
    // 成功（ファイルがある場合）
     $json_string_encoded = mb_convert_encoding($json_string, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
     $users = json_decode($json_string_encoded, true);
     $result = json_encode($users);
} else {
    // 失敗（ファイルが内場合）
    $result = json_encode("");
}

$res = [
    "users" => $result ,
];
header("Content-type: application/json; charset=UTF-8");
echo json_encode($res);
?>