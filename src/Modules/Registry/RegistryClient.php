<?php

namespace Lartrix\Modules\Registry;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** 统一处理模块市场地址、认证、超时和 HTTP 错误。 */
final class RegistryClient
{
    /** @var callable|null */
    private $requestFactory;

    /** 初始化模块市场客户端。 */
    public function __construct(
        private readonly ?string $url = null,
        private readonly ?string $authKey = null,
        private readonly ?int $timeout = null,
        ?callable $requestFactory = null,
    ) {
        $this->requestFactory = $requestFactory;
    }

    /** 返回规范化后的模块市场根地址。 */
    public function baseUrl(): string
    {
        return rtrim($this->url ?? (string) config('lartrix.module_market.url', ''), '/');
    }

    /** 返回当前 Auth Key。 */
    public function authKey(): string
    {
        return trim($this->authKey ?? (string) config('lartrix.module_market.auth_key', ''));
    }

    /** 创建携带统一认证和超时的 Laravel HTTP 请求。 */
    public function request(): PendingRequest
    {
        $request = is_callable($this->requestFactory)
            ? ($this->requestFactory)()
            : Http::acceptJson()->timeout($this->timeout ?? (int) config('lartrix.module_market.timeout', 30));

        return $this->authKey() === '' ? $request : $request->withToken($this->authKey());
    }

    /** 请求 JSON，并返回稳定的成功或失败结构。 */
    public function getJson(string $endpoint, array $query = []): array
    {
        if ($this->baseUrl() === '') {
            return $this->failure('registry_url_missing', '模块市场地址未配置。');
        }

        return $this->normalize($this->request()->get($this->urlFor($endpoint), $query));
    }

    /** 提交 JSON，并返回稳定的成功或失败结构。 */
    public function postJson(string $endpoint, array $payload = []): array
    {
        if ($this->baseUrl() === '') {
            return $this->failure('registry_url_missing', '模块市场地址未配置。');
        }

        return $this->normalize($this->request()->post($this->urlFor($endpoint), $payload));
    }

    /** 下载包内容；HTTP 失败时返回 null。 */
    public function download(string $url): ?string
    {
        if (!$this->isTrustedDownloadUrl($url)) {
            return null;
        }

        // 禁止跟随重定向，避免同源下载端点把 Auth Key 转发到外部主机。
        $response = $this->request()->withOptions(['allow_redirects' => false])->get($url);

        return $response->successful() ? $response->body() : null;
    }

    /** 仅允许从配置的 Registry 同源地址下载包。 */
    private function isTrustedDownloadUrl(string $url): bool
    {
        $base = parse_url($this->baseUrl());
        $target = parse_url($url);
        if (!is_array($base) || !is_array($target)) {
            return false;
        }

        $scheme = strtolower((string) ($target['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return $scheme === strtolower((string) ($base['scheme'] ?? ''))
            && strtolower((string) ($target['host'] ?? '')) === strtolower((string) ($base['host'] ?? ''))
            && ($target['port'] ?? null) === ($base['port'] ?? null);
    }

    /** 拼接模块市场端点。 */
    public function urlFor(string $endpoint): string
    {
        return $this->baseUrl() . '/' . ltrim($endpoint, '/');
    }

    /** 将 Laravel 响应归一化。 */
    private function normalize(Response $response): array
    {
        $payload = $response->json();
        $payload = is_array($payload) ? $payload : [];

        if (!$response->successful()) {
            return $this->failure(
                'registry_http_error',
                (string) ($payload['msg'] ?? $payload['message'] ?? "模块市场请求失败：HTTP {$response->status()}"),
                $response->status(),
                $payload,
            );
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $payload, 'reason' => null, 'message' => null];
    }

    /** 构造统一失败结构。 */
    private function failure(string $reason, string $message, int $status = 0, array $data = []): array
    {
        return compact('reason', 'message', 'status', 'data') + ['ok' => false];
    }
}
