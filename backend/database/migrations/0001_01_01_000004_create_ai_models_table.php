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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('provider', 50); // openai, anthropic, google
            $table->string('model_id', 100); // gpt-4o, claude-3-5-sonnet, etc.
            $table->text('description')->nullable();
            $table->integer('max_tokens')->default(4096);
            $table->integer('context_window')->default(128000);
            $table->decimal('input_price', 10, 6)->default(0); // per 1K tokens
            $table->decimal('output_price', 10, 6)->default(0); // per 1K tokens
            $table->boolean('is_active')->default(true);
            $table->boolean('supports_vision')->default(false);
            $table->boolean('supports_function_calling')->default(false);
            $table->boolean('supports_streaming')->default(true);
            $table->json('capabilities')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['provider', 'model_id']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
