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
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->string('tap_number')->nullable()->unique();
            $table->string('macp_work_id')->unique();
            $table->string('english_title')->nullable()->index();
            $table->string('other_title')->nullable();
            $table->string('iswc')->nullable();
            $table->string('work_type')->nullable()->index();
            $table->string('genre')->nullable()->index();
            $table->string('language')->nullable()->index();
            $table->string('duration', 8)->nullable();
            $table->string('artist_name')->nullable();
            $table->string('original_work_title')->nullable();
            $table->string('sub_title_work')->nullable();
            $table->string('society_no')->nullable();
            $table->date('administration_start_date')->nullable();
            $table->date('administration_end_date')->nullable();
            $table->date('registration_date')->nullable()->index();
            $table->string('registration_status')->index();
            $table->boolean('is_public_domain')->default(false)->index();
            $table->string('song_link')->nullable();
            $table->text('remark')->nullable();
            $table->date('release_date')->nullable();
            $table->string('compass_work_internal_no')->nullable();
            $table->string('compass_work_type')->nullable();
            $table->date('compass_start_date')->nullable();
            $table->string('wami_work_internal_no')->nullable();
            $table->string('wami_work_type')->nullable();
            $table->string('conflict_status')->default('no_conflict')->index();
            $table->text('conflict_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
