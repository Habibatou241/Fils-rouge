<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreprocessingsTable extends Migration
{
    public function up()
{
    Schema::create('preprocessings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('dataset_id')->constrained()->onDelete('cascade');
        $table->string('name'); // nom du prétraitement (ex: "Suppression valeurs manquantes")
        $table->string('file_path'); // chemin du fichier résultant (CSV/XLSX)
        $table->json('summary')->nullable(); // statistiques résumées
        $table->timestamps();
    });
}


    public function down()
{
    Schema::dropIfExists('preprocessings');
}

}
