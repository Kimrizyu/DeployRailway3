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
        Schema::table('lamarans', function (Blueprint $table) {
            $table->string('cv_file_path')->nullable();
            $table->string('application_letter_path')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lamarans', function (Blueprint $table) {
            //
        });
    }
};
