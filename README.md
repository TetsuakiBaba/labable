# labable
研究室入退室をSlackを利用して簡単に管理するためのツール

## Get Start

    git clone https://github.com/TetsuakiBaba/labable.git
    echo '<?php $url = "ここにはslackのカスタムインテグレーションから取得したIncoming Webhook URLを記入する"; ?>' > slack_url.php
    php -S locahost:8000

ブラウザから localhost:8000 で閲覧してください。送信したいチャンネル合わせてindex.htmlのチャンネル欄の値を変更してください。

webhook URLの取得については https://slack.com/intl/ja-jp/help/articles/115005265063-Slack-%E3%81%A7%E3%81%AE-Incoming-Webhook-%E3%81%AE%E5%88%A9%E7%94%A8 を参照してください。

## Reference
  * phpでSlackにメッセージを送る：https://qiita.com/kdtsh/items/814e766080a9761b14a7