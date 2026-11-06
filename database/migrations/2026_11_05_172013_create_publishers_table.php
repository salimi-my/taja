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
        Schema::create('publishers', function (Blueprint $table) {
            $table->id();
            $table->string('publisher_code')->nullable()->unique();
            $table->string('name')->index();
            $table->json('rights_types_granted');
            $table->string('ipi_base_number')->nullable();
            $table->string('ipi_name_number', 11)->nullable()->unique();
            $table->string('company_registration_number')->unique();
            $table->text('registration_address');
            $table->string('official_email');
            $table->date('incorporation_date')->nullable();
            $table->string('society_cmo')->index();
            $table->string('society_number')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('status')->index();
            $table->date('agreement_start_date');
            $table->date('agreement_end_date')->nullable();
            $table->boolean('auto_renewal')->nullable();
            $table->boolean('pre_term')->nullable();
            $table->string('post_term')->nullable();
            $table->json('territories_granted');
            $table->string('bank_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('client_account_number')->nullable();
            $table->string('swift_code')->nullable();
            $table->text('bank_address')->nullable();
            $table->string('person_in_charge_name')->nullable();
            $table->string('person_in_charge_title')->nullable();
            $table->string('person_in_charge_nric')->nullable();
            $table->string('person_in_charge_mobile_no')->nullable();
            $table->string('person_in_charge_email')->nullable();
            $table->decimal('taja_percentage_deal', 5, 2)->nullable();
            $table->decimal('client_percentage_deal', 5, 2)->nullable();
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
        Schema::dropIfExists('publishers');
    }
};
