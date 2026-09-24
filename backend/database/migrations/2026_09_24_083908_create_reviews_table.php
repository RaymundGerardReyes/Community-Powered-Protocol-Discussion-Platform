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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->enum('verdict', ['approved', 'changes_requested', 'rejected']);
            $table->string('summary');
            $table->text('findings')->nullable();
            $table->timestamps();

            $table->unique(['protocol_id', 'user_id'], 'unique_user_protocol_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
