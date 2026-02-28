<?php

namespace Lartrix\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 通知消息模型
 */
class NotificationMessage extends Model
{
    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'title',
        'content',
        'type',
        'category_key',
        'guard_name',
        'user_id',
        'from_user_id',
        'target_guards',
        'is_read',
        'read_at',
        'extra',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'extra' => 'array',
        'target_guards' => 'array',
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
        $array['readAt'] = $array['read_at'];
        $array['fromUserId'] = $array['from_user_id'];
        $array['targetGuards'] = $array['target_guards'];
        $array['categoryKey'] = $array['category_key'];
        $array['isRead'] = $array['is_read'];
        $array['userId'] = $array['user_id'];
        $array['guardName'] = $array['guard_name'];

        // 添加分类信息
        if ($this->relationLoaded('category') && $this->category) {
            $array['category'] = [
                'name' => $this->category->name,
                'color' => $this->category->color,
                'icon' => $this->category->icon,
            ];
        }

        return $array;
    }

    /**
     * 序列化日期格式
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 关联接收用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('lartrix.models.user'), 'user_id');
    }

    /**
     * 关联发送用户
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(config('lartrix.models.user'), 'from_user_id');
    }

    /**
     * 关联通知分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NotificationCategory::class, 'category_key', 'key');
    }
}
