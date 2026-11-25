<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the single invoiced month with a range of months, and add the line description and the timesheet PDF.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('period_start')->nullable()->after('project_id');
            $table->date('period_end')->nullable()->after('period_start');
            $table->string('line_description')->nullable()->after('period_end');
            $table->string('timesheet_pdf_path')->nullable()->after('pdf_path');
        });

        DB::table('invoices')->orderBy('id')->each(function (object $invoice) {
            $month = sprintf('%04d-%02d-01', $invoice->period_year, $invoice->period_month);

            DB::table('invoices')->where('id', $invoice->id)->update(['period_start' => $month, 'period_end' => $month]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->date('period_start')->nullable(false)->change();
            $table->date('period_end')->nullable(false)->change();
            $table->dropColumn(['period_year', 'period_month']);
        });
    }

    /**
     * Reverse the migrations. A range of months is reduced to its last month.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedSmallInteger('period_year')->nullable()->after('project_id');
            $table->unsignedTinyInteger('period_month')->nullable()->after('period_year');
        });

        DB::table('invoices')->orderBy('id')->each(function (object $invoice) {
            [$year, $month] = explode('-', $invoice->period_end);

            DB::table('invoices')->where('id', $invoice->id)->update(['period_year' => (int) $year, 'period_month' => (int) $month]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedSmallInteger('period_year')->nullable(false)->change();
            $table->unsignedTinyInteger('period_month')->nullable(false)->change();
            $table->dropColumn(['period_start', 'period_end', 'line_description', 'timesheet_pdf_path']);
        });
    }
};
