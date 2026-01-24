<?php

namespace Lartrix\Schema\Actions;

/**
 * IfAction - 条件判断动作
 * 
 * 对应 vschema 的 IfAction 类型
 * 支持两种模式：
 * 1. then/else 为动作数组：['if' => 'cond', 'then' => [...], 'else' => [...]]
 * 2. then/else 为单个动作（如 FetchAction）：['if' => 'cond', 'then' => {fetch...}, 'else' => {fetch...}]
 */
class IfAction implements ActionInterface
{
    protected string $condition;
    protected ActionInterface|array|null $then = null;
    protected ActionInterface|array|null $else = null;

    public function __construct(string $condition)
    {
        $this->condition = $condition;
    }

    /**
     * 创建实例
     */
    public static function make(string $condition): static
    {
        return new static($condition);
    }

    /**
     * 条件为真时执行
     * 
     * @param ActionInterface|array $actions 单个动作或动作数组
     */
    public function then(ActionInterface|array $actions): static
    {
        $this->then = $actions;
        return $this;
    }

    /**
     * 条件为假时执行
     * 
     * @param ActionInterface|array $actions 单个动作或动作数组
     */
    public function else(ActionInterface|array $actions): static
    {
        $this->else = $actions;
        return $this;
    }

    /**
     * 转换动作为数组
     */
    protected function convertActions(ActionInterface|array|null $actions): mixed
    {
        if ($actions === null) {
            return null;
        }

        // 单个 ActionInterface 实例，直接转换（用于嵌套 FetchAction 等场景）
        if ($actions instanceof ActionInterface) {
            return $actions->toArray();
        }

        // 数组形式，逐个转换
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
        $result = [
            'if' => $this->condition,
            'then' => $this->convertActions($this->then),
        ];

        $elseResult = $this->convertActions($this->else);
        if ($elseResult !== null) {
            $result['else'] = $elseResult;
        }

        return $result;
    }
}
