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
        Schema::create('inscricoes', function (Blueprint $table) {
            $table->id();
            $table->string('protocolo')->unique();
            $table->foreignId('edital_id')->constrained('editais');
            $table->foreignId('militar_id')->constrained('militares');
            $table->string('situacao');
            $table->boolean('aceite_condicoes');
            $table->unsignedInteger('version');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->unique(['edital_id', 'militar_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscricoes');
    }
};
