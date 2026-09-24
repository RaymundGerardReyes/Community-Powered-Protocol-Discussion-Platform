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
        Schema::create('threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content');
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->integer('votes_count')->default(0);
            $table->timestamps();

            $table->index(['protocol_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threads');
    }
};
