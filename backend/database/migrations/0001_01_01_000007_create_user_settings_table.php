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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('default_model_id')->nullable()->constrained('ai_models')->onDelete('set null');
            $table->text('default_system_prompt')->nullable();
            $table->enum('theme', ['light', 'dark', 'auto'])->default('auto');
            $table->string('language', 10)->default('ja');
            $table->json('preferences')->nullable(); // Additional preferences
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
