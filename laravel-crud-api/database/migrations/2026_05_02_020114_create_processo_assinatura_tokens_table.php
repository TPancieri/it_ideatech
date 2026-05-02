<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processo_assinatura_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('processo_id')
                ->constrained('processos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();

            $table->timestamps();

            $table->index(['processo_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processo_assinatura_tokens');
    }
};
