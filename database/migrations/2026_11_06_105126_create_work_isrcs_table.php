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
        Schema::create('work_isrcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->string('isrc', 12)->index();
            $table->timestamps();

            $table->unique(['work_id', 'isrc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_isrcs');
    }
};
