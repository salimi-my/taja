<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Large files are validated and imported by a queued job, so the import
     * records its progress, the full number of errors and warnings (the lists
     * only keep the first ones) and why it failed.
     */
    public function up(): void
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->unsignedTinyInteger('progress')->default(0)->after('status');
            $table->unsignedInteger('error_count')->default(0)->after('error_rows');
            $table->unsignedInteger('warning_count')->default(0)->after('error_count');
            $table->text('failure_message')->nullable()->after('imported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->dropColumn(['progress', 'error_count', 'warning_count', 'failure_message']);
        });
    }
};
