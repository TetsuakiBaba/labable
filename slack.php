<?php
require "sslkey.php";

$raw = file_get_contents('php://input'); // POSTされた生のデータを受け取る
$data = json_decode($raw); // json形式をphp変数に変換

$posturl = openssl_decrypt(hex2bin($data->posturl), 'AES-128-ECB', $sslkey, OPENSSL_RAW_DATA);


if ($data->inout == "in") {
    $message = [
        "channel" => $data->channel,
        "username" => $data->name,
        "text" => "[IN] " . $data->text,
        "icon_emoji" => $data->icon_emoji
    ];
} else {
    $message = [
        "channel" => $data->channel,
        "username" => $data->name,
        "text" => "[OUT] " . $data->text,
        "icon_emoji" => $data->icon_emoji
    ];
}

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


if ($json_string = @file_get_contents($data->posturl . ".json")) {
    // 成功（ファイルがある場合）
    // $result = "success";
    $json_string_encoded = mb_convert_encoding($json_string, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $users = json_decode($json_string_encoded, true);
    $result = json_encode($users);
    // 入室する時
    if ($data->inout == 'in') {
        //array_push($users, $data->name);
        array_push($users, ['name' => $data->name, 'timestamp' => $data->timestamp, 'text' => $data->text]);
    }
    // 退出する時
    else {
        // 退出するときは自分の名前を探して削除
        $index = 0;
        foreach ($users as $user) {
            if ($data->name == $user['name']) {
                unset($users[$index]);
            }
            $index++;
        }
        $users = array_values($users);
    }
    //$users = array_unique($users, SORT_REGULAR);
    $users = array_reduce($users, function ($carry, $item) {
        if (!in_array($item, $carry)) {
            $carry[] = $item;
        }
        return $carry;
    }, []);
    $result = json_encode($users);
    $json = fopen($data->posturl . '.json', 'w+b');
    fwrite($json, json_encode($users));
    fclose($json);
} else {
    // 失敗（ファイルがない場合）
    //echo "failed";
    if ($data->inout == 'in') {
        $obj = [['name' => $data->name, 'timestamp' => $data->timestamp, 'message' => $data->message]];
        $obj_json_string = $obj; //json_decode($obj, true);

        $json = fopen($data->posturl . '.json', 'w+b');
        fwrite($json, json_encode($obj_json_string));
        fclose($json);
        $result = json_encode([$data->name]);
    } else {
        $result = json_encode("");
    }
}


$res = [
    "message" => "#" . $data->channel . " に送信が完了しました。",
    "users" => $result
];

echo json_encode($res);
