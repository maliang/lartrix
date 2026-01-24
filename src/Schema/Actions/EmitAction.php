<?php

namespace Lartrix\Schema\Actions;

/**
 * EmitAction - 触发事件动作
 * 
 * 对应 vschema 的 EmitAction 类型
 */
class EmitAction implements ActionInterface
{
    protected string $event;
    protected mixed $payload = null;

    public function __construct(string $event)
    {
        $this->event = $event;
    }

    /**
     * 创建实例
     */
    public static function make(string $event): static
    {
        return new static($event);
    }

    /**
     * 设置事件负载
     */
    public function payload(mixed $payload): static
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        $result = ['emit' => $this->event];

        if ($this->payload !== null) {
            $result['payload'] = $this->payload;
        }

        return $result;
    }
}
