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
        Schema::create('people', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->autoIncrement()->primary();
            $table->integer('edad')->index();
            $table->string('nombre')->index();
            $table->string('paterno')->index();
            $table->string('materno')->index();
            $table->date('fecha_nacimiento')->index();
            $table->enum('sexo', ['M', 'H']);
            $table->string('calle')->index();
            $table->string('curp', 18)->nullable()->unique();
            $table->string('int', 10)->index()->nullable();
            $table->string('ext', 10)->index();
            $table->string('colonia')->index();
            $table->integer('cp')->index();
            $table->string('ine_cve', 18)->unique();
            $table->tinyInteger('ine_e');
            $table->tinyInteger('ine_d');
            $table->tinyInteger('ine_m');
            $table->integer('ine_s');
            $table->integer('ine_l');
            $table->integer('ine_mza');
            $table->string('ine_consec')->index();
            $table->tinyInteger('ine_cred')->index();
            $table->string('ine_folio', 20)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
