<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 为后台菜单表增加模块归属字段和索引。 */
    public function up(): void
    {
        if (!Schema::hasTable('admin_menus') || Schema::hasColumn('admin_menus', 'module')) {
            return;
        }

        Schema::table('admin_menus', function (Blueprint $table) {
            $table->string('module')->nullable()->after('title')->index()->comment('所属模块');
        });

        \Illuminate\Support\Facades\DB::table('admin_menus')
            ->whereNull('module')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->each(function ($menu): void {
                $module = strtolower(strtok((string) $menu->name, '.') ?: (string) $menu->name);
                \Illuminate\Support\Facades\DB::table('admin_menus')
                    ->where('id', $menu->id)
                    ->update(['module' => $module ?: null]);
            });
    }

    /** 回滚后台菜单表的模块归属字段和索引。 */
    public function down(): void
    {
        if (!Schema::hasTable('admin_menus') || !Schema::hasColumn('admin_menus', 'module')) {
            return;
        }

        Schema::table('admin_menus', function (Blueprint $table) {
            $table->dropIndex(['module']);
            $table->dropColumn('module');
        });
    }
};
