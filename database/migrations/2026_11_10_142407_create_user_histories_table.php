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
     * timestamps. Users are hard-deleted, so the subject has no foreign key and
     * its name and email are copied, and the actor's name is copied too, so an
     * entry still reads well after either account is gone.
     */
    public function up(): void
    {
        Schema::create('user_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_name');
            $table->string('subject_email');
            $table->string('change_type')->index();
            $table->json('previous_snapshot')->nullable();
            $table->json('new_snapshot')->nullable();
            $table->timestamp('recorded_at')->useCurrent()->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->text('remarks')->nullable();

            $table->index(['subject_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_histories');
    }
};
