<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePreprocessingsTable extends Migration
{
    public function up()
    {
        Schema::table('preprocessings', function (Blueprint $table) {
            // Rename column 'type' to 'name'
            $table->renameColumn('type', 'name');

            // Rename column 'result' to 'summary'
            $table->renameColumn('result', 'summary');

            // Add new column 'file_path'
            $table->string('file_path')->after('name')->nullable(); // or remove nullable if required
        });
    }

    public function down()
    {
        Schema::table('preprocessings', function (Blueprint $table) {
            $table->renameColumn('name', 'type');
            $table->renameColumn('summary', 'result');
            $table->dropColumn('file_path');
        });
    }
}

