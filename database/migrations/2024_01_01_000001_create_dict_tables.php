<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 字典分组表
        Schema::create('dict_groups', function (Blueprint $table) {
            $table->comment('字典分组表');
            $table->id();
            $table->string('code', 50)->unique()->comment('唯一标识');
            $table->string('name', 100)->comment('显示名称');
            $table->string('description')->nullable()->comment('描述');
            $table->boolean('is_system')->default(false)->comment('是否系统内置');
            $table->timestamps();
        });

        // 字典项表
        Schema::create('dict_items', function (Blueprint $table) {
            $table->comment('字典项表');
            $table->id();
            $table->foreignId('group_id')->comment('所属分组ID')->constrained('dict_groups')->cascadeOnDelete();
            $table->string('code', 50)->comment('项标识');
            $table->string('label', 100)->comment('显示文本');
            $table->string('value', 100)->comment('存储值');
            $table->integer('sort')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->json('extra')->nullable()->comment('扩展数据');
            $table->timestamps();

            $table->unique(['group_id', 'code']);
            $table->index(['group_id', 'is_enabled', 'sort']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dict_items');
        Schema::dropIfExists('dict_groups');
    }
};
