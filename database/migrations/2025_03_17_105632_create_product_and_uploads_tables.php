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
        Schema::create('products', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('name');
            $table->text('description');
            $table->decimal('price');
            $table->foreignId('users_id')->constrained()->onDelete('cascade');
        });

        Schema::create('uploads', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignId('users_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable()->default(null);;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('uploads');
    }
};
