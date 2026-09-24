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
        Schema::create('protocols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('category')->index();
            $table->string('version')->default('1.0.0');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->integer('votes_count')->default(0)->index();
            $table->integer('score')->default(0)->index();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protocols');
    }
};
