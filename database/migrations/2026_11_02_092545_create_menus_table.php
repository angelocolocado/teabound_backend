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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Category::class, 'category_id')->constrained();
            $table->string('name');
            $table->longText('notes')->nullable();
            $table->decimal('price', 8, 2);
            $table->integer('status')->default(\App\Enum\MenuStatusEnum::inactive);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
