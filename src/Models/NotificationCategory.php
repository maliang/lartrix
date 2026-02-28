<?php

namespace Lartrix\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 通知分类模型
 */
class NotificationCategory extends Model
{
    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'name',
        'key',
        'icon',
        'color',
        'sort',
        'message_types',
        'guard_name',
        'enabled',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'message_types' => 'array',
        'enabled' => 'boolean',
        'sort' => 'integer',
        'color' => 'string',
    ];

    /**
     * 将键名转换为驼峰命名
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        // 转换字段名为驼峰命名
        $array['createdAt'] = $array['created_at'];
        $array['updatedAt'] = $array['updated_at'];
        $array['messageTypes'] = $array['message_types'];
        $array['guardName'] = $array['guard_name'];

        return $array;
    }

    /**
     * 序列化日期格式
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
