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
        Schema::create('notification_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分类名称');
            $table->string('key')->comment('分类标识');
            $table->string('icon')->nullable()->comment('图标 (Iconify)');
            $table->string('color')->nullable()->comment('颜色');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('message_types')->nullable()->comment('关联的消息类型');
            $table->string('guard_name')->comment('所属 guard');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->timestamps();

            $table->index('guard_name');
            $table->unique(['key', 'guard_name']);
        });
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_categories');
    }
};
