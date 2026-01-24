<?php

namespace Lartrix\Schema;

/**
 * JsonNode 接口
 * 
 * 对应 vschema 的 JsonNode 类型
 */
interface JsonNodeInterface
{
    /**
     * 转换为数组
     */
    public function toArray(): array;

    /**
     * 转换为 JSON 字符串
     */
    public function toJson(): string;
}
