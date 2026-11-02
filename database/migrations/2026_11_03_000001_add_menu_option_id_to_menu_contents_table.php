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
        Schema::table('menu_contents', function (Blueprint $table): void {
            $table->foreignId('menu_id')->nullable()->change();
            $table->foreignId('menu_option_id')
                ->nullable()
                ->after('menu_id')
                ->constrained('menu_options')
                ->cascadeOnDelete();

            $table->index(['menu_option_id', 'inventory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_contents', function (Blueprint $table): void {
            $table->dropForeign(['menu_option_id']);
            $table->dropIndex(['menu_option_id', 'inventory_id']);
            $table->dropColumn('menu_option_id');
        });
    }
};
