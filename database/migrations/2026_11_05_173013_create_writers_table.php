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
        Schema::create('writers', function (Blueprint $table) {
            $table->id();
            $table->string('writer_code')->nullable()->unique();
            $table->string('name')->index();
            $table->string('default_role')->nullable();
            $table->string('ipi_base_number')->nullable();
            $table->string('ipi_name_number', 11)->nullable()->unique();
            $table->string('nric')->nullable()->index();
            $table->date('date_of_birth')->nullable();
            $table->date('date_of_death')->nullable();
            $table->string('nationality')->nullable();
            $table->string('cmo_society')->index();
            $table->foreignId('publisher_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('controlled_territories')->nullable();
            $table->json('rights_types_controlled')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->index();
            $table->date('agreement_start_date');
            $table->date('agreement_end_date')->nullable();
            $table->boolean('auto_renewal')->nullable();
            $table->boolean('pre_term')->nullable();
            $table->string('post_term')->nullable();
            $table->decimal('taja_percentage_deal', 5, 2)->nullable();
            $table->decimal('writer_percentage_deal', 5, 2)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('swift_code')->nullable();
            $table->text('bank_address')->nullable();
            $table->string('heir_name')->nullable();
            $table->string('heir_nric')->nullable();
            $table->string('heir_relationship')->nullable();
            $table->string('heir_mobile_number')->nullable();
            $table->string('heir_email')->nullable();
            $table->string('source_system')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('writers');
    }
};
