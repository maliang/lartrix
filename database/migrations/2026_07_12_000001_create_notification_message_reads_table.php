<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 创建广播通知的用户级已读回执表。 */
    public function up(): void
    {
        Schema::create('notification_message_reads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at');
            $table->unique(['notification_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /** 删除广播通知已读回执表。 */
    public function down(): void
    {
        Schema::dropIfExists('notification_message_reads');
    }
};
