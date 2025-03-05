# dairin-php-client

Dairin のプロジェクト操作用 API を呼び出すための PHP クライアントライブラリです。

広告主様のシステムに組み込んでご使用いただけます。

Dairin の API を PHP 以外の実行環境でご利用いただきたい場合は、本リポジトリの実装を参考にご自身の環境向けに移植してください。

## インストール

Composerを利用してインストールします。

```bash
composer require dairin-kaeru/dairin-php-client
```

#### Packagist のリンク
https://packagist.org/packages/dairin-kaeru/dairin-php-client

## 使用例

```php
require 'vendor/autoload.php';
use DairinClient\DairinClient;

$key = getenv('DAIRIN_PROJECT_API_KEY');
$secret = getenv('DAIRIN_PROJECT_API_SECRET');
$client = new DairinClient($key, $secret);

// 疎通確認
$pingResp = $client->ping();
echo json_encode(json_decode($pingResp->getBody(), true), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
echo PHP_EOL;

// 既存の顧客（中間CV済み）に最終CVを発生させる
$project_code = getenv('DAIRIN_PROJECT_CODE');
$customer_uid = '<<_CUSTOMER_UID_>>';
$completeResp = $client->complete(
    project_code: $project_code,
    customer_uid: $customer_uid,
    event_id: 'dairin-php-client から発火しました',
);
echo json_encode(json_decode($completeResp->getBody(), true), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
echo PHP_EOL;
```
