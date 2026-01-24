<?php

namespace Lartrix\Schema\Actions;

/**
 * WebSocketAction - WebSocket 长连接动作
 * 
 * 对应 vschema 的 WebSocketAction 类型
 * 
 * 操作类型：
 * - connect: 创建（或复用）连接并绑定回调
 * - send: 向已存在的连接发送消息
 * - close: 关闭连接
 */
class WebSocketAction implements ActionInterface
{
    protected string $ws;
    protected ?string $op = null;
    protected ?string $id = null;
    protected string|array|null $protocols = null;
    protected ?int $timeout = null;

    // send 操作参数
    protected mixed $message = null;
    protected ?string $sendAs = null;

    // 响应解析
    protected ?string $responseType = null;

    // 生命周期回调
    protected array $onOpen = [];
    protected array $onMessage = [];
    protected array $onError = [];
    protected array $onClose = [];

    // 流程回调
    protected array $then = [];
    protected array $catch = [];
    protected array $finally = [];

    // close 操作参数
    protected ?int $code = null;
    protected ?string $reason = null;

    public function __construct(string $ws)
    {
        $this->ws = $ws;
    }

    /**
     * 创建实例
     */
    public static function make(string $ws): static
    {
        return new static($ws);
    }

    /**
     * 设置操作类型
     */
    public function op(string $op): static
    {
        $this->op = $op;
        return $this;
    }

    /**
     * 连接操作
     */
    public function connect(): static
    {
        return $this->op('connect');
    }

    /**
     * 发送操作
     */
    public function send(): static
    {
        return $this->op('send');
    }

    /**
     * 关闭操作
     */
    public function close(?int $code = null, ?string $reason = null): static
    {
        $this->op = 'close';
        $this->code = $code;
        $this->reason = $reason;
        return $this;
    }

    /**
     * 设置连接 ID
     */
    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * 设置子协议
     */
    public function protocols(string|array $protocols): static
    {
        $this->protocols = $protocols;
        return $this;
    }

    /**
     * 设置连接超时
     */
    public function timeout(int $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * 设置发送消息
     */
    public function message(mixed $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 设置消息序列化方式
     */
    public function sendAs(string $sendAs): static
    {
        $this->sendAs = $sendAs;
        return $this;
    }

    /**
     * 设置响应解析方式
     */
    public function responseType(string $responseType): static
    {
        $this->responseType = $responseType;
        return $this;
    }

    /**
     * 连接打开回调
     */
    public function onOpen(ActionInterface|array $actions): static
    {
        $this->onOpen = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    /**
     * 收到消息回调
     */
    public function onMessage(ActionInterface|array $actions): static
    {
        $this->onMessage = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    /**
     * 错误回调
     */
    public function onError(ActionInterface|array $actions): static
    {
        $this->onError = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    /**
     * 连接关闭回调
     */
    public function onClose(ActionInterface|array $actions): static
    {
        $this->onClose = is_array($actions) ? $actions : [$actions];
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
        $result = ['ws' => $this->ws];

        if ($this->op !== null) {
            $result['op'] = $this->op;
        }
        if ($this->id !== null) {
            $result['id'] = $this->id;
        }
        if ($this->protocols !== null) {
            $result['protocols'] = $this->protocols;
        }
        if ($this->timeout !== null) {
            $result['timeout'] = $this->timeout;
        }
        if ($this->message !== null) {
            $result['message'] = $this->message;
        }
        if ($this->sendAs !== null) {
            $result['sendAs'] = $this->sendAs;
        }
        if ($this->responseType !== null) {
            $result['responseType'] = $this->responseType;
        }

        // 生命周期回调
        if (!empty($this->onOpen)) {
            $result['onOpen'] = $this->normalizeActions($this->onOpen);
        }
        if (!empty($this->onMessage)) {
            $result['onMessage'] = $this->normalizeActions($this->onMessage);
        }
        if (!empty($this->onError)) {
            $result['onError'] = $this->normalizeActions($this->onError);
        }
        if (!empty($this->onClose)) {
            $result['onClose'] = $this->normalizeActions($this->onClose);
        }

        // 流程回调
        if (!empty($this->then)) {
            $result['then'] = $this->normalizeActions($this->then);
        }
        if (!empty($this->catch)) {
            $result['catch'] = $this->normalizeActions($this->catch);
        }
        if (!empty($this->finally)) {
            $result['finally'] = $this->normalizeActions($this->finally);
        }

        // close 参数
        if ($this->code !== null) {
            $result['code'] = $this->code;
        }
        if ($this->reason !== null) {
            $result['reason'] = $this->reason;
        }

        return $result;
    }
}
