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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // unique code or custom id
            $table->string('name');
            $table->enum('status', ['unknown', 'available', 'unavailable', 'closed'])->default('unknown');
            $table->text('description')->nullable();
            $table->string('thumbnail_pic')->nullable();
            $table->integer('stock');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
