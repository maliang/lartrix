<?php

namespace Lartrix\Services;

class BaseService
{
    /**
     * 静态工厂方法，创建服务实例
     */
    public static function make(): static
    {
        return new static();
    }
}