<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->unsignedInteger('low_stock_threshold')->default(0);
            $table->string('display_unit')->default('pcs');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->string('image_path')->nullable();
        });

        Schema::table('payment_records', function (Blueprint $table) {
            $table->decimal('cash_received', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['low_stock_threshold', 'display_unit']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('payment_records', function (Blueprint $table) {
            $table->dropColumn(['cash_received', 'change_amount']);
        });
    }
};
