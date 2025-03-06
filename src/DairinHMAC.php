<?php

namespace DairinClient;

class DairinHMAC
{
    /**
     * @param string $secretKey プロジェクトのシークレットキー（HMAC署名の鍵）
     */
    public function __construct(private readonly string $secretKey)
    {
    }

    /**
     * HMAC署名を生成する
     *
     * @param string $httpMethod HTTPメソッド（例: GET, POST）
     * @param string $path リクエストパス（例: /api/v1/resource）
     * @param array $queryParams クエリパラメータの連想配列
     * @param string|null $body リクエストボディ（JSON文字列など）
     * @param string $timestamp UNIXタイムスタンプ
     * @param string $nonce リクエストごとのユニークな文字列
     *
     * @return string 生成された署名（Base64エンコード済み）
     */
    public function generateSignature(string $httpMethod, string $path, array $queryParams, ?string $body, string $timestamp, string $nonce): string
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
        return base64_encode(hash_hmac('sha256', $canonicalString, $this->secretKey, true));
    }
}
