<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('bjs_id', 50)->nullable()->after('id');

            $table->tinyInteger('status_bjs')->nullable()->after('status');

            $table->unsignedBigInteger('processed_by')->nullable()->after('remains');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamp('processed_at')->nullable()->after('processed_by');

            $table->timestamp('last_synced_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
            $table->dropColumn(['bjs_id', 'status_bjs', 'processed_by', 'processed_at', 'last_synced_at']);
        });
    }
};
