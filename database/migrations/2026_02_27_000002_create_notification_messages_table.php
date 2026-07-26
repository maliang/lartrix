<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 运行迁移
     */
    public function up(): void
    {
        Schema::create('notification_messages', function (Blueprint $table) {
            $table->comment('通知消息表');
            $table->id();
            $table->string('title')->comment('消息标题');
            $table->text('content')->comment('消息内容');
            $table->string('type')->comment('消息类型');
            $table->string('category_key')->comment('关联的分类 key');
            $table->string('guard_name')->comment('所属 guard');
            $table->unsignedBigInteger('user_id')->nullable()->comment('接收用户 ID');
            $table->unsignedBigInteger('from_user_id')->nullable()->comment('发送用户 ID');
            $table->json('target_guards')->nullable()->comment('目标 guards');
            $table->boolean('is_read')->default(false)->comment('是否已读');
            $table->timestamp('read_at')->nullable()->comment('已读时间');
            $table->json('extra')->nullable()->comment('额外数据');
            $table->timestamps();

            $table->index(['guard_name', 'user_id']);
            $table->index(['guard_name', 'is_read']);
            $table->index('type');
            $table->index('category_key');
        });
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_messages');
    }
};
