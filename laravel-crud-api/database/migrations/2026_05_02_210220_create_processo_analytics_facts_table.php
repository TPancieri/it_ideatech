<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processo_analytics_facts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('processo_id')
                ->constrained('processos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('processo_title');
            $table->string('processo_category');
            $table->string('processo_status');
            $table->timestamp('processo_created_at');
            $table->timestamp('processo_updated_at')->nullable();
            $table->string('document_path')->nullable();

            $table->foreignId('responsible_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('responsible_user_email')->nullable();

            $table->foreignId('signatario_id')
                ->constrained('clientes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('signatario_nome');
            $table->string('signatario_email');
            $table->string('signatario_funcao')->nullable();
            $table->string('signatario_setor')->nullable();
            $table->string('signatario_status')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamp('convite_primeiro_envio_em')->nullable();
            $table->timestamp('convite_ultimo_envio_em')->nullable();
            $table->unsignedInteger('convites_enviados')->default(0);

            $table->string('tipo_resposta')->nullable();
            $table->timestamp('resposta_em')->nullable();
            $table->decimal('tempo_resposta_horas', 12, 3)->nullable();
            $table->text('justificativa_reprovacao')->nullable();

            $table->timestamps();

            $table->unique(['processo_id', 'signatario_id']);
            $table->index(['processo_status', 'processo_category']);
            $table->index(['signatario_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processo_analytics_facts');
    }
};
