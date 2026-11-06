<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A problem with how the whole file is laid out (for example the rows of
     * one work are not together) cannot be fixed by skipping rows, so while
     * this is set the import cannot be confirmed.
     */
    public function up(): void
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->text('layout_error')->nullable()->after('failure_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->dropColumn('layout_error');
        });
    }
};
