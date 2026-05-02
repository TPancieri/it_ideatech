<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_eventos', function (Blueprint $table) {
            $table->id();

            $table->string('acao');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('actor');

            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('meta')->nullable();

            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['acao', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_eventos');
    }
};
