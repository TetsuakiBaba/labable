# labable
研究室入退室をSlackを利用して簡単に管理するためのツール。slackのwebhook URLを利用して、phpとjsで任意のslackチャンネルに入退室を通知します。コロナ禍なのでその日の体温を申告します。適当なphpが動くサーバにデプロイして、そのページをホーム画面に追加すると便利です。

<img src="Apr-21-2022 03-38-41.gif">

## はじめる

1. https://tetsuakibaba.jp/project/labable/getStart.html にアクセス
2. 登録したいslackの webhook URLとチャンネルを入力して create ボタンを押す
3. 生成されたリンクにアクセスして利用開始です。

ローカル環境でテスト動作させる場合は以下の手順。
 ```
git clone https://github.com/TetsuakiBaba/labable.git
cd labable
echo '<?php $sslkey = 'set your ssl key here'; ?>' > sslkey.php
php -S locahost:8000
```

ブラウザから localhost:8000 で閲覧してください。送信したいチャンネル合わせてindex.htmlのチャンネル欄の値を変更してください。phpコマンドがない場合はmacはbrew等でインストールしてあげてください。

webhook URLの取得については https://slack.com/intl/ja-jp/help/articles/115005265063-Slack-%E3%81%A7%E3%81%AE-Incoming-Webhook-%E3%81%AE%E5%88%A9%E7%94%A8 を参照してください。

## License
  * APP ICON: <a href="https://www.flaticon.com/free-icons/portfolio" title="portfolio icons">Portfolio icons created by Freepik - Flaticon</a>
## Reference
  * phpでSlackにメッセージを送る：https://qiita.com/kdtsh/items/814e766080a9761b14a7