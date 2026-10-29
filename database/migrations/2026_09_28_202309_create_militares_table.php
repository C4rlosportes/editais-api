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
        Schema::create('militares', function (Blueprint $table) {
            $table->id();
            $table->string('usuario_keycloak')->unique();
            $table->string('perfil');
            $table->string('id_funcional')->unique();
            $table->string('nome');
            $table->string('posto_graduacao');
            $table->foreignId('opm_origem_id')->constrained('opms');
            $table->string('municipio');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('militares');
    }
};
