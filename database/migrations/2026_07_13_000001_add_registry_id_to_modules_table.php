<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 为现有模块表增加唯一生态标识。 */
    public function up(): void
    {
        if (Schema::hasTable('modules') && !Schema::hasColumn('modules', 'registry_id')) {
            Schema::table('modules', function (Blueprint $table): void {
                $table->string('registry_id')->nullable()->unique()->after('name')->comment('Trix 生态唯一标识');
            });
        }
    }

    /** 移除生态标识字段。 */
    public function down(): void
    {
        if (Schema::hasTable('modules') && Schema::hasColumn('modules', 'registry_id')) {
            Schema::table('modules', function (Blueprint $table): void {
                $table->dropUnique(['registry_id']);
                $table->dropColumn('registry_id');
            });
        }
    }
};
