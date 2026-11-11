# 依存パッケージ更新の確認（2026-11-11）

## PRとの照合

- [PR #1](https://github.com/dairin-kaeru/dairin-php-client/pull/1): Guzzle 7.9.2 → 7.15.2。PSR-7 2.13.2と関連依存の更新も含む。
- [PR #2](https://github.com/dairin-kaeru/dairin-php-client/pull/2): PSR-7 2.7.0 → 2.13.2。#1と重複し、単独ではGuzzle側の検出を解消しない。
- 両PRを統合する内容として、ComposerでGuzzle 7.16.1、PSR-7 2.13.2、Promises 2.6.0、deprecation-contracts 3.8.1、polyfill-php80 1.43.0を解決。
- Guzzle 7.16.1はPR指定版以降の不具合修正も含み、PSR-7 ^2.13.2を要求する。利用先では本ライブラリのlockファイルが適用されないため、composer.jsonのGuzzle要件も ^7.16.1に更新した。
- lockファイルのPHP要件をcomposer.jsonの既存の >=8.1 に同期。

## OSV照合

osv-scanner 2.7.0で更新前後のcomposer.lockをオンライン照合した。

```sh
osv-scanner scan source -L composer.lock --format json
```

| パッケージ | 更新前 | 更新後 | 検出件数（前 → 後） |
| --- | --- | --- | --- |
| guzzlehttp/guzzle | 7.9.2 | 7.16.1 | 9 → 0 |
| guzzlehttp/psr7 | 2.7.0 | 2.13.2 | 4 → 0 |

更新前は8パッケージを走査して終了コード1、更新後は9パッケージを走査して終了コード0。更新後のJSONは `results: []`。

更新前の検出ID:

- Guzzle: GHSA-94pj-82f3-465w、GHSA-cwxw-98qj-8qjx、GHSA-f283-ghqc-fg79、GHSA-f7vp-7xgx-4w4r、GHSA-g446-98w2-8p5w、GHSA-h95v-h523-3mw8、GHSA-v5mv-p594-2x33、GHSA-wm3w-8rrp-j577、GHSA-wpwq-4j6v-78m3。
- PSR-7: GHSA-34xg-wgjx-8xph、GHSA-c2w2-prh8-qm98、GHSA-hq7v-mx3g-29hw、GHSA-vm85-hxw5-5432。

## 動作確認

- PHP 8.5.12で `composer validate --strict`、`composer check-platform-reqs`、src内のPHP構文チェックに成功。
- Composerの更新時監査も脆弱性0件。
- GuzzleのMockHandlerでping/signup/completeのPOST先、JSON本文、認証ヘッダー、HMAC署名とHTTP 400の例外伝播を確認。
- 実APIへの送信およびPHP 8.1実機での動作確認は未実施。
