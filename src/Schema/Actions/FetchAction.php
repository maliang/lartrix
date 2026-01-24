<?php

namespace Lartrix\Schema\Actions;

/**
 * FetchAction - API 请求动作
 * 
 * 对应 vschema 的 FetchAction 类型
 */
class FetchAction implements ActionInterface
{
    protected string $url;
    protected string $method = 'GET';
    protected array $headers = [];
    protected ?array $params = null;
    protected mixed $body = null;
    protected array $then = [];
    protected array $catch = [];
    protected array $finally = [];
    protected bool $ignoreBaseURL = false;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * 创建实例
     */
    public static function make(string $url): static
    {
        return new static($url);
    }

    /**
     * 设置请求方法
     */
    public function method(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    /**
     * GET 请求
     */
    public function get(): static
    {
        return $this->method('GET');
    }

    /**
     * POST 请求
     */
    public function post(): static
    {
        return $this->method('POST');
    }

    /**
     * PUT 请求
     */
    public function put(): static
    {
        return $this->method('PUT');
    }

    /**
     * DELETE 请求
     */
    public function delete(): static
    {
        return $this->method('DELETE');
    }

    /**
     * PATCH 请求
     */
    public function patch(): static
    {
        return $this->method('PATCH');
    }

    /**
     * 设置请求头
     */
    public function headers(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * 设置查询参数（GET 请求）
     */
    public function params(array $params): static
    {
        $this->params = $params;
        return $this;
    }

    /**
     * 设置请求体
     */
    public function body(mixed $body): static
    {
        $this->body = $body;
        return $this;
    }

    /**
     * 忽略全局 baseURL
     */
    public function ignoreBaseURL(bool $ignore = true): static
    {
        $this->ignoreBaseURL = $ignore;
        return $this;
    }

    /**
     * 成功回调
     */
    public function then(ActionInterface|array $actions): static
    {
        $this->then = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    /**
     * 失败回调
     */
    public function catch(ActionInterface|array $actions): static
    {
        $this->catch = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    /**
     * 完成回调
     */
    public function finally(ActionInterface|array $actions): static
    {
        $this->finally = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    /**
     * 转换动作数组
     */
    protected function normalizeActions(array $actions): array
    {
        return array_map(
            fn($a) => $a instanceof ActionInterface ? $a->toArray() : $a,
            $actions
        );
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        $result = ['fetch' => $this->url];

        if ($this->method !== 'GET') {
            $result['method'] = $this->method;
        }

        if (!empty($this->headers)) {
            $result['headers'] = $this->headers;
        }

        if ($this->params !== null) {
            $result['params'] = $this->params;
        }

        if ($this->body !== null) {
            $result['body'] = $this->body;
        }

        if ($this->ignoreBaseURL) {
            $result['ignoreBaseURL'] = true;
        }

        if (!empty($this->then)) {
            $result['then'] = $this->normalizeActions($this->then);
        }

        if (!empty($this->catch)) {
            $result['catch'] = $this->normalizeActions($this->catch);
        }

        if (!empty($this->finally)) {
            $result['finally'] = $this->normalizeActions($this->finally);
        }

        return $result;
    }
}
