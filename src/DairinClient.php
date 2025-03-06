<?php

namespace DairinClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DairinClient
{
    protected string $apiKey;
    protected DairinHMAC $hmac;
    protected string $baseUrl;
    protected Client $httpClient;

    /**
     * コンストラクタ
     *
     * @param string $apiKey プロジェクトのAPIキー
     * @param string $secretKey プロジェクトのシークレットキー
     * @param string $baseUrl APIのベースURL（例: https://dair.in）
     * @param array $httpClientOptions GuzzleHttp\Client用のオプション（任意）
     */
    public function __construct(string $apiKey, string $secretKey, string $baseUrl = 'https://dair.in', array $httpClientOptions = [])
    {
        $this->apiKey = $apiKey;
        $this->hmac = new DairinHMAC($secretKey);
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->httpClient = new Client($httpClientOptions);
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

    public function signup(
        string $partner_code,
        string $campaign_code,
        string $customer_uid,
    )
    {
        assert($partner_code && mb_strlen($partner_code) <= 255);
        assert($campaign_code && mb_strlen($campaign_code) <= 255);
        assert($customer_uid && mb_strlen($customer_uid) <= 255);

        return $this->postJson('/api/v1/signup', [
            'partner_code' => $partner_code,
            'campaign_code' => $campaign_code,
            'customer_uid' => $customer_uid,
        ]);
    }

    /**
     * 既存の customer_uid をもつ Customer に最終コンバージョンを発生させる。
     * @param string $customer_uid プロジェクト内で顧客を識別する文字列
     * @param string|null $event_id 最終コンバージョンの追補情報（メモ）として指定できる任意の文字列
     * @param string|null $campaign_code 適用するキャンペーンを識別する文字列
     * @param int|null $sales_amount 定率のキャンペーンで報酬計算の掛け値となる販売金額
     * @return \Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    public function complete(
        string  $customer_uid,
        ?string $event_id = null,
        ?string $campaign_code = null,
        ?int    $sales_amount = null
    )
    {
        assert(mb_strlen($customer_uid) <= 255);
        assert($event_id === null || strlen($event_id) <= 1024 * 10 - 1); //実際には最大長 65,535 バイト
        assert($campaign_code === null || strlen($campaign_code) <= 255);
        assert($sales_amount === null || $sales_amount >= 0);

        return $this->postJson('/api/v1/complete', [
            'customer_uid' => $customer_uid,
            'event_id' => $event_id,
            'campaign_code' => $campaign_code,
            'sales_amount' => $sales_amount,
        ]);
    }

    /**
     * POSTリクエストをJSON形式で送信する
     *
     * @param string $path APIのパス（例: /api/v1/resource）
     * @param array $data リクエストボディとして送信する連想配列（自動的にJSONエンコードされます）
     * @param array $queryParams クエリパラメータ（任意）
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
        $signature = $this->hmac->generateSignature($httpMethod, $path, $queryParams, $body, $timestamp, $nonce);

        // リクエストヘッダーの生成
        $headers = [
            'X-API-Key' => $this->apiKey,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
            'Content-Type' => 'application/json',
        ];

        // GuzzleHTTPでリクエストを送信
        $options = [
            'headers' => $headers,
            'body' => $body,
            'query' => $queryParams
        ];

        return $this->httpClient->request($httpMethod, $url, $options);
    }
}
