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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('user');
            $table->string('link');
            $table->integer('start_count')->nullable();
            $table->integer('count');
            $table->integer('service_id');
            $table->tinyInteger('status')->default(0);
            $table->integer('remains')->nullable();
            $table->dateTime('order_created_at');
            $table->text('order_cancel_reason')->nullable();
            $table->text('order_fail_reason')->nullable();
            $table->decimal('charge', 10, 2)->nullable();
            $table->timestamps();

            $table->index('service_id');
            $table->index('status');
            $table->index('order_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
