<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The table is insert-only, so it has `recorded_at` instead of the usual
     * timestamps. `csv_import_id` gets its foreign key when `csv_imports` is
     * created.
     */
    public function up(): void
    {
        Schema::create('rights_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_party_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('change_type')->index();
            $table->json('previous_snapshot')->nullable();
            $table->json('new_snapshot')->nullable();
            $table->date('effective_date');
            $table->timestamp('recorded_at')->useCurrent();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('csv_import_id')->nullable()->index();
            $table->text('remarks')->nullable();

            $table->index(['work_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rights_histories');
    }
};
