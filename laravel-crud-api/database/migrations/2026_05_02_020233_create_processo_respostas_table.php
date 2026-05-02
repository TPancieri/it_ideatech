<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processo_respostas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('processo_id')
                ->constrained('processos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('tipo'); // approved | rejected
            $table->text('justificativa')->nullable();

            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['processo_id', 'cliente_id']);
            $table->index(['processo_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processo_respostas');
    }
};
