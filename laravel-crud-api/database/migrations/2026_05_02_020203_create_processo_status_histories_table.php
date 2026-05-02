<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processo_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('processo_id')
                ->constrained('processos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');

            $table->nullableMorphs('actor');

            $table->string('reason')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['processo_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processo_status_histories');
    }
};
