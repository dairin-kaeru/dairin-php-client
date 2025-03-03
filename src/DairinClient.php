<?php
namespace DairinClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DairinClient
{
    protected string $apiKey;
    protected string $secretKey;
    protected string $baseUrl;
    protected Client $httpClient;

    /**
     * コンストラクタ
     *
     * @param string $apiKey        APIキー
     * @param string $secretKey     シークレットキー
     * @param string $baseUrl       APIのベースURL（例: https://dair.in）
     * @param array  $httpClientOptions GuzzleHttp\Client用のオプション（任意）
     */
    public function __construct(string $apiKey, string $secretKey, string $baseUrl = 'https://dair.in', array $httpClientOptions = [])
    {
        $this->apiKey    = $apiKey;
        $this->secretKey = $secretKey;
        $this->baseUrl   = rtrim($baseUrl, '/');
        $this->httpClient = new Client($httpClientOptions);
    }

    /**
     * HMAC署名を生成する
     *
     * @param string      $httpMethod  HTTPメソッド（例: GET, POST）
     * @param string      $path        リクエストパス（例: /api/v1/resource）
     * @param array       $queryParams クエリパラメータの連想配列
     * @param string|null $body        リクエストボディ（JSON文字列など）
     * @param string      $timestamp   UNIXタイムスタンプ
     * @param string      $nonce       リクエストごとのユニークな文字列
     *
     * @return string 生成された署名（Base64エンコード済み）
     */
    protected function generateSignature(string $httpMethod, string $path, array $queryParams, ?string $body, string $timestamp, string $nonce): string
    {
        // クエリパラメータをキーでソートして連結
        $sortedQueryString = '';
        if (!empty($queryParams)) {
            ksort($queryParams);
            $sortedQueryString = http_build_query($queryParams);
        }

        // ボディのハッシュ生成（Base64エンコード済み）
        $bodyHash = $body ? base64_encode(hash('sha256', $body, true)) : '';

        // Canonical Stringの生成（各項目を改行コードで連結）
        $canonicalString = implode("\n", [
            strtoupper($httpMethod),
            $path,
            $sortedQueryString,
            $bodyHash,
            $timestamp,
            $nonce
        ]);

        // HMAC-SHA256署名の生成
        $signature = base64_encode(hash_hmac('sha256', $canonicalString, $this->secretKey, true));
        return $signature;
    }

    /**
     * POSTリクエストをJSON形式で送信する
     *
     * @param string $path        APIのパス（例: /api/v1/resource）
     * @param array  $data        リクエストボディとして送信する連想配列（自動的にJSONエンコードされます）
     * @param array  $queryParams クエリパラメータ（任意）
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws GuzzleException
     */
    protected function postJson(string $path, array $data, array $queryParams = [])
    {
        $url = $this->baseUrl . $path;
        $httpMethod = 'POST';
        $body = json_encode($data);

        $timestamp = (string)time();
        $nonce = bin2hex(random_bytes(8)); // 16文字のランダムな文字列

        // 署名の生成
        $signature = $this->generateSignature($httpMethod, $path, $queryParams, $body, $timestamp, $nonce);

        // リクエストヘッダーの生成
        $headers = [
            'X-API-Key'   => $this->apiKey,
            'X-Timestamp' => $timestamp,
            'X-Nonce'     => $nonce,
            'X-Signature' => $signature,
            'Content-Type'=> 'application/json',
        ];

        // GuzzleHTTPでリクエストを送信
        $options = [
            'headers' => $headers,
            'body'    => $body,
            'query'   => $queryParams
        ];

        return $this->httpClient->request($httpMethod, $url, $options);
    }

    /**
     * 疎通確認リクエストを送信する
     * @return \Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    public function ping()
    {
        return $this->postJson('/api/v1/ping', []);
    }
}
