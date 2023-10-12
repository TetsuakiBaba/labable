<?php
require "sslkey.php";

$raw = file_get_contents('php://input'); // POSTされた生のデータを受け取る
$data = json_decode($raw); // json形式をphp変数に変換

$result = "";

if ($json_string = @file_get_contents($data->posturl . ".json")) {

    // 成功（ファイルがある場合）
    $json_string_encoded = mb_convert_encoding($json_string, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $users = json_decode($json_string_encoded, true);

    // $usersにあるデータで現在のタイムスタンプよりも1日ズレていればそのユーザは自動退出処理にする
    $index = 0;
    foreach ($users as $user) {
        if (getdate($user['timestamp'])['mday'] != getdate()['mday']) {
            unset($users[$index]);
        }
        //$user['phptime'] = getdate()['mday'];
        $index++;
    }
    $users = array_values($users);
    $result = json_encode($users);
    $json = fopen($data->posturl . '.json', 'w+b');
    fwrite($json, json_encode($users));
    fclose($json);
} else {
    // 失敗（ファイルが内場合）
    $result = json_encode("");
}

$res = [
    "users" => $result,
];
header("Content-type: application/json; charset=UTF-8");
echo json_encode($res);
