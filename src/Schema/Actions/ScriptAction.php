<?php

namespace Lartrix\Schema\Actions;

/**
 * ScriptAction - 脚本执行动作
 * 
 * 对应 vschema 的 ScriptAction 类型
 */
class ScriptAction implements ActionInterface
{
    public function __construct(
        protected string $script
    ) {}

    /**
     * 创建实例
     */
    public static function make(string $script): static
    {
        return new static($script);
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return ['script' => $this->script];
    }
}
