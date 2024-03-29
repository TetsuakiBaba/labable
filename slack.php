<?php
require "sslkey.php";

$raw = file_get_contents('php://input'); // POSTされた生のデータを受け取る
$data = json_decode($raw); // json形式をphp変数に変換

$posturl = openssl_decrypt(hex2bin($data->posturl), 'AES-128-ECB', $sslkey, OPENSSL_RAW_DATA);

function getScore($name)
{
    global $data;
    $scoreFile = $data->posturl . "_score.json";
    if (file_exists($scoreFile)) {
        $scores = json_decode(file_get_contents($scoreFile), true);
        foreach ($scores as $score) {
            if ($score['name'] == $name) {
                return $score['score'];
            }
        }
        $scores[] = ['name' => $name, 'score' => 0];
        file_put_contents($scoreFile, json_encode($scores));
    } else {
        $scores = [['name' => $name, 'score' => 0]];
        file_put_contents($scoreFile, json_encode($scores));
    }
    return 0;
}

function addScore($name)
{
    global $data;
    $scoreFile = $data->posturl . "_score.json";
    $scores = json_decode(file_get_contents($scoreFile), true);
    foreach ($scores as &$score) {
        if ($score['name'] == $name) {
            $score['score']++;
            file_put_contents($scoreFile, json_encode($scores));
            return $score['score'];
        }
    }
}

if ($data->inout == "in") {
    $score = getScore($data->name);
    $score++;
    $message = [
        "channel" => $data->channel,
        "username" => $data->name,
        "text" => "[IN] " . $data->text . " (入室回数: $score)",
        "icon_emoji" => $data->icon_emoji
    ];
} else {
    $score = getScore($data->name);
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
    $json_string_encoded = mb_convert_encoding($json_string, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $users = json_decode($json_string_encoded, true);

    if ($data->inout == 'in') {
        $score = addScore($data->name);
        array_push($users, ['name' => $data->name, 'timestamp' => $data->timestamp, 'text' => $data->text, 'score' => $score]);
    } else {
        $index = 0;
        foreach ($users as $user) {
            if ($data->name == $user['name']) {
                unset($users[$index]);
            }
            $index++;
        }
        $users = array_values($users);
    }

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
    if ($data->inout == 'in') {
        $obj = [['name' => $data->name, 'timestamp' => $data->timestamp, 'message' => $data->message]];
        $obj_json_string = $obj;

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
