<?php

use App\Models\Inventory;
use App\Models\Menu;
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
        Schema::create('menu_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Menu::class, 'menu_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Inventory::class, 'inventory_id')->constrained()->nullOnDelete();
            $table->string('name');
            $table->integer('stock_to_deduct_in_pcs')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_contents');
    }
};
