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

    /**
     * 中間CVを発生させる
     * @param string $partner_code 顧客獲得に寄与した代理店を識別する文字列（提携コード）
     * @param string $campaign_code 適用するキャンペーンを識別する文字列
     * @param string $customer_uid プロジェクト内で顧客を識別する文字列
     * @return \Psr\Http\Message\ResponseInterface|void
     * @throws GuzzleException
     */
    public function signup(
        string $partner_code,
        string $campaign_code,
        string $customer_uid,
    )
    {
        if (empty($partner_code) || mb_strlen($partner_code) > 255) {
            throw new \InvalidArgumentException('partner_codeが不正です。');
        }
        if (empty($campaign_code) || mb_strlen($campaign_code) > 255) {
            throw new \InvalidArgumentException('campaign_codeが不正です。');
        }
        if (empty($customer_uid) || mb_strlen($customer_uid) > 255) {
            throw new \InvalidArgumentException('customer_uidが不正です。');
        }

        return $this->postJson('/api/v1/signup', [
            'partner_code' => $partner_code,
            'campaign_code' => $campaign_code,
            'customer_uid' => $customer_uid,
        ]);
    }

    /**
     * 最終CVを発生させる
     * @param string $customer_uid プロジェクト内で顧客を識別する文字列
     * @param string $campaign_code 適用するキャンペーンを識別する文字列
     * @param string|null $event_id 最終CVの追補情報（メモ）として指定できる任意の文字列
     * @param int|null $sales_amount 定率のキャンペーンで報酬計算の掛け値となる販売金額
     * @return \Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    public function complete(
        string  $customer_uid,
        string  $campaign_code,
        ?string $event_id = null,
        ?int    $sales_amount = null
    )
    {
        if (empty($customer_uid) || mb_strlen($customer_uid) > 255) {
            throw new \InvalidArgumentException('customer_uidが不正です。');
        }
        if (empty($campaign_code) || mb_strlen($campaign_code) > 255) {
            throw new \InvalidArgumentException('campaign_codeが不正です。');
        }
        if (!is_null($event_id) && strlen($event_id) > 1024 * 10 - 1) { //実際には最大長 65,535 バイト
            throw new \InvalidArgumentException('event_idが不正です。');
        }
        if (!is_null($sales_amount) && $sales_amount < 0) {
            throw new \InvalidArgumentException('sales_amountが不正です。');
        }

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
     * @return \Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     * @throws \JsonException
     */
    protected function postJson(string $path, array $data, array $queryParams = [])
    {
        $url = $this->baseUrl . $path;
        $httpMethod = 'POST';
        $body = json_encode($data, JSON_THROW_ON_ERROR);

        $timestamp = (string)time();
        $nonce = bin2hex(random_bytes(16)); // 32文字のランダムな文字列

        // 署名の生成
        $signature = $this->hmac->generateSignature($httpMethod, $path, $queryParams, $body, $timestamp, $nonce);

        // リクエストヘッダーの生成
        $headers = [
            'X-API-Key' => $this->apiKey,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
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
