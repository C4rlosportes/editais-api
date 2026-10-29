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
        Schema::create('conferencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscricao_id')->constrained('inscricoes');
            $table->foreignId('requisito_id')->constrained('requisitos');
            $table->string('resultado');
            $table->text('observacao')->nullable();
            $table->string('conferido_por')->nullable();
            $table->timestamp('updated_at')->useCurrent();
            $table->unique(['inscricao_id', 'requisito_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conferencias');
    }
};
