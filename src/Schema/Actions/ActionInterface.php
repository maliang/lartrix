<?php

namespace Lartrix\Schema\Actions;

/**
 * Action 接口
 * 
 * 对应 vschema 的 Action 类型
 */
interface ActionInterface
{
    /**
     * 转换为数组
     */
    public function toArray(): array;
}
