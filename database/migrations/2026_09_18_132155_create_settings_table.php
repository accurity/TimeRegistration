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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('address');
            $table->string('postal_code');
            $table->string('city');
            $table->string('kvk_number')->nullable();
            $table->string('btw_number')->nullable();
            $table->string('iban')->nullable();
            $table->unsignedSmallInteger('default_payment_term_days')->default(14);
            $table->decimal('default_vat_percentage', 5, 2)->default(21.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
