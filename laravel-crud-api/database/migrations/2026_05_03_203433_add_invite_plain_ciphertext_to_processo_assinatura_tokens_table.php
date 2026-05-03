<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processo_assinatura_tokens', function (Blueprint $table): void {
            $table->text('invite_plain_ciphertext')->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('processo_assinatura_tokens', function (Blueprint $table): void {
            $table->dropColumn('invite_plain_ciphertext');
        });
    }
};
