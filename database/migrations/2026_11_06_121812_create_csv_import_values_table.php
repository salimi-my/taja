<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A scratch table used while a file is processed to find values that
     * appear twice in the file without holding every value in memory. Its rows
     * are removed when the pass ends.
     */
    public function up(): void
    {
        Schema::create('csv_import_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csv_import_id')->constrained()->cascadeOnDelete();
            $table->string('field', 64);
            $table->char('value_hash', 32);
            $table->unsignedInteger('line');

            $table->unique(['csv_import_id', 'field', 'value_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_import_values');
    }
};
