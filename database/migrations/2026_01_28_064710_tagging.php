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
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('type_id')->constrained('types')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('tags_cons', function (Blueprint $table) {
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained(table: 'tags')->onDelete('cascade');
            $table->primary(['item_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags_cons');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('types');
    }
};
