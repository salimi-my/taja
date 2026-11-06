<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `rights_histories.csv_import_id` was created without a foreign key
     * because this table did not exist yet, so the key is added here.
     */
    public function up(): void
    {
        Schema::create('csv_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('status')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->json('warnings')->nullable();
            $table->json('errors')->nullable();
            $table->unsignedInteger('imported_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::table('rights_histories', function (Blueprint $table) {
            $table->foreign('csv_import_id')->references('id')->on('csv_imports')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rights_histories', function (Blueprint $table) {
            $table->dropForeign(['csv_import_id']);
        });

        Schema::dropIfExists('csv_imports');
    }
};
