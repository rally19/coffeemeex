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
            $table->string('code')->unique(); // unique code or custom id
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('order_status', ['pending', 'processing', 'completed', 'cancelled', 'failed'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_proof')->nullable();
            $table->text('comments_public')->nullable();
            $table->text('comments_private')->nullable();
            $table->string('name'); // below are for user data history
            $table->string('email');
            $table->string('phone_numbers', 25)->nullable();  // above are for user data history
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('orders_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // unique code or custom id
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('item_id')->nullable()->constrained('items')->onDelete('set null');
            $table->integer('quantity')->nullable();
            $table->decimal('cost', 10, 2);
            $table->string('item_code')->nullable(); // this and below are item historical purspose
            $table->string('item_name')->nullable();
            $table->decimal('item_price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders_items');
        Schema::dropIfExists('orders');
    }
};
