<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Perfil de acesso da conta.
            // O padrão é MILITAR para evitar conceder privilégios de SME por acidente.
            $table->string('perfil_acesso')->default('SEM_ACESSO');
        });

        Schema::table('militares', function (Blueprint $table) {
            // Relaciona uma conta de acesso ao cadastro funcional do militar.
            // É nullable porque contas da SME não precisam de vínculo com militares.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Garante que um cadastro de militar não seja vinculado
            // a mais de uma conta de acesso.
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('militares', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('perfil_acesso');
        });
    }
};
