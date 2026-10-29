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
        Schema::create('escolhas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscricao_id')->constrained('inscricoes');
            $table->foreignId('destino_id')->constrained('destinos');
            $table->unsignedInteger('ordem');
            $table->unique(['inscricao_id', 'destino_id']);
            $table->unique(['inscricao_id', 'ordem']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escolhas');
    }
};
