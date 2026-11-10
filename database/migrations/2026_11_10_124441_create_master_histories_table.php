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
     * timestamps. The subject is a writer or a publisher (a polymorphic key
     * without a foreign key, because both are soft-deleted). Its code and name
     * are copied so an entry still reads well after the record changes.
     */
    public function up(): void
    {
        Schema::create('master_histories', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_code')->nullable();
            $table->string('subject_name');
            $table->string('change_type')->index();
            $table->json('previous_snapshot')->nullable();
            $table->json('new_snapshot')->nullable();
            $table->timestamp('recorded_at')->useCurrent()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('csv_import_id')->nullable()->constrained()->nullOnDelete();
            $table->text('remarks')->nullable();

            $table->index(['subject_type', 'subject_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_histories');
    }
};
