<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->whereNull('bjs_id')->update([
            'bjs_id' => DB::raw("CAST(id AS TEXT)"),
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('bjs_id', 50)->nullable(false)->change();
            $table->index('bjs_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['orders_bjs_id_index']);
            $table->string('bjs_id', 50)->nullable()->change();
        });
    }
};
