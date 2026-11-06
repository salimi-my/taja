<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->foreignId('writer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('role');
            $table->string('contributor_name')->index();
            $table->decimal('performance_share', 5, 2);
            $table->decimal('mechanical_share', 5, 2);
            $table->decimal('sync_share', 5, 2)->nullable();
            $table->decimal('lyrics_share', 5, 2)->nullable();
            $table->boolean('is_controlled');
            $table->json('territories_controlled');
            $table->json('territory_exclusions')->nullable();
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->index(['work_id', 'effective_end_date']);
        });

        // Exactly one of writer_id / publisher_id is set. SQLite cannot add a
        // check constraint to an existing table, so it relies on validation.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE work_parties ADD CONSTRAINT work_parties_one_party_check CHECK ((writer_id IS NULL) <> (publisher_id IS NULL))'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_parties');
    }
};
