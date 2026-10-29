<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove os campos antigos de vigência dos editais.
     */
    public function up(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            $table->dropColumn([
                'data_inicio',
                'data_fim',
            ]);
        });
    }

    /**
     * Restaura os campos caso a migration seja revertida.
     */
    public function down(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
        });
    }
};
