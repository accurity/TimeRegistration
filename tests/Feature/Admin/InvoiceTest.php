<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\Setting;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_the_draft_creation_screen_shows_a_correctly_computed_proposal(): void
    {
        $client = Client::factory()->create(['invoice_abbreviation' => 'ABC']);
        $project = Project::factory()->create(['client_id' => $client->id, 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => '3.00', 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-06', 'hours' => '2.00', 'rate' => 100]);

        $response = $this->actingAs($this->admin())->get('/admin/invoices/create?'.http_build_query([
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
        ]));

        $response->assertOk();
        $response->assertSee('500,00');
        $response->assertSee('105,00');
        $response->assertSee('605,00');
        $response->assertSee('1012620-ABC');
    }

    public function test_the_creation_screen_shows_a_neutral_approval_status_per_month_without_a_warning(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-08-05']);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05']);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 8, 'status' => 'approved']);

        $response = $this->actingAs($this->admin())->get('/admin/invoices/create?'.http_build_query([
            'project_id' => $project->id,
            'from' => '2026-08',
            'to' => '2026-09',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder(['goedgekeurd', 'niet ingediend']);
        $response->assertDontSee('Let op:');
    }

    public function test_admin_can_save_a_draft_invoice_with_the_proposed_values(): void
    {
        $client = Client::factory()->create(['invoice_abbreviation' => 'ABC']);
        $project = Project::factory()->create(['client_id' => $client->id, 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => '4.00', 'rate' => 100]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'approved']);

        $response = $this->actingAs($this->admin())->post('/admin/invoices', [
            'project_id' => $project->id,
            'period_start' => '2026-09',
            'period_end' => '2026-09',
            'invoice_number' => '1012620-ABC',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-10-15',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', [
            'client_id' => $client->id,
            'project_id' => $project->id,
            'invoice_number' => '1012620-ABC',
            'sequence_number' => 101,
            'subtotal' => 400.00,
            'vat_amount' => 84.00,
            'total' => 484.00,
            'status' => 'draft',
        ]);
    }

    public function test_the_sequence_number_of_a_new_draft_follows_a_manually_entered_invoice_number(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05']);

        $response = $this->actingAs($this->admin())->post('/admin/invoices', [
            'project_id' => $project->id,
            'period_start' => '2026-09',
            'period_end' => '2026-09',
            'invoice_number' => '1042620-INZ',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-10-15',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', ['invoice_number' => '1042620-INZ', 'sequence_number' => 104]);
    }

    public function test_the_sequence_number_of_a_draft_follows_a_manually_changed_invoice_number(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft', 'invoice_number' => '1012620-INZ', 'sequence_number' => 101]);

        $response = $this->actingAs($this->admin())->put("/admin/invoices/{$invoice->id}", [
            'invoice_number' => '1042620-INZ',
            'invoice_date' => '2026-10-02',
            'due_date' => '2026-10-16',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(104, $invoice->refresh()->sequence_number);
    }

    public function test_the_invoice_number_must_be_unique(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        Invoice::factory()->create(['client_id' => $client->id, 'invoice_number' => 'DUPLICATE-1']);

        $response = $this->actingAs($this->admin())->post('/admin/invoices', [
            'project_id' => $project->id,
            'period_start' => '2026-09',
            'period_end' => '2026-09',
            'invoice_number' => 'DUPLICATE-1',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-10-15',
        ]);

        $response->assertSessionHasErrors('invoice_number');
        $this->assertSame(1, Invoice::query()->where('invoice_number', 'DUPLICATE-1')->count());
    }

    public function test_admin_can_update_number_date_and_due_date_of_a_draft(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft', 'invoice_number' => 'OLD-1']);

        $response = $this->actingAs($this->admin())->put("/admin/invoices/{$invoice->id}", [
            'invoice_number' => 'NEW-1',
            'invoice_date' => '2026-10-02',
            'due_date' => '2026-10-16',
        ]);

        $response->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertSame('NEW-1', $invoice->invoice_number);
        $this->assertSame('2026-10-02', $invoice->invoice_date->toDateString());
        $this->assertSame('2026-10-16', $invoice->due_date->toDateString());
    }

    public function test_a_draft_invoice_can_be_deleted_without_affecting_time_entries(): void
    {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->create(['project_id' => $project->id, 'status' => 'draft']);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->admin())->delete("/admin/invoices/{$invoice->id}");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);
    }

    public function test_the_delete_form_of_a_draft_is_not_nested_inside_the_save_form(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $html = $this->actingAs($this->admin())->get("/admin/invoices/{$invoice->id}/edit")->assertOk()->getContent();

        $saveFormStart = strpos($html, 'action="'.route('admin.invoices.update', $invoice).'"');
        $saveFormEnd = strpos($html, '</form>', $saveFormStart);
        $deleteFormStart = strpos($html, 'id="delete-invoice-'.$invoice->id.'"');

        $this->assertNotFalse($deleteFormStart);
        $this->assertGreaterThan($saveFormEnd, $deleteFormStart);
    }

    public function test_a_final_invoice_cannot_be_edited_or_deleted(): void
    {
        $invoice = Invoice::factory()->final()->create();

        $editResponse = $this->actingAs($this->admin())->get("/admin/invoices/{$invoice->id}/edit");
        $editResponse->assertNotFound();

        $updateResponse = $this->actingAs($this->admin())->put("/admin/invoices/{$invoice->id}", [
            'invoice_number' => 'CHANGED-1',
            'invoice_date' => '2026-10-02',
            'due_date' => '2026-10-16',
        ]);
        $updateResponse->assertNotFound();

        $deleteResponse = $this->actingAs($this->admin())->delete("/admin/invoices/{$invoice->id}");
        $deleteResponse->assertNotFound();

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'final']);
    }

    public function test_finalizing_a_draft_freezes_amounts_locks_time_entries_and_generates_a_pdf(): void
    {
        Storage::fake('invoices');

        $client = Client::factory()->create(['invoice_abbreviation' => 'ABC']);
        $project = Project::factory()->create(['client_id' => $client->id, 'rate' => 100]);
        $entry1 = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => '3.00', 'rate' => 100]);
        $entry2 = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-06', 'hours' => '2.00', 'rate' => 100]);
        $otherMonthEntry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-08-06', 'hours' => '1.00', 'rate' => 100]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'approved', 'approved_at' => now()]);
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-01',
            'invoice_number' => '1012620-ABC',
            'invoice_date' => '2026-10-01',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin())->post("/admin/invoices/{$invoice->id}/finalize");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.invoices.index'));

        $invoice->refresh();
        $this->assertSame('final', $invoice->status);
        $this->assertSame('500.00', $invoice->subtotal);
        $this->assertSame('105.00', $invoice->vat_amount);
        $this->assertSame('605.00', $invoice->total);
        $this->assertNotNull($invoice->pdf_path);

        $this->assertSame($invoice->id, $entry1->fresh()->invoice_id);
        $this->assertSame($invoice->id, $entry2->fresh()->invoice_id);
        $this->assertNull($otherMonthEntry->fresh()->invoice_id);

        Storage::disk('invoices')->assertExists($invoice->pdf_path);
        $this->assertSame('2026/1012620-ABC.pdf', $invoice->pdf_path);
        Storage::disk('invoices')->assertExists($invoice->timesheet_pdf_path);
        $this->assertSame('2026/1012620-ABC-urenspecificatie.pdf', $invoice->timesheet_pdf_path);
    }

    public function test_finalize_is_blocked_for_a_non_draft_invoice(): void
    {
        $invoice = Invoice::factory()->final()->create();

        $response = $this->actingAs($this->admin())->post("/admin/invoices/{$invoice->id}/finalize");

        $response->assertNotFound();
    }

    public function test_the_pdf_shows_the_payment_note_with_the_term_of_the_invoice(): void
    {
        $settings = Setting::current();
        $settings->update(['company_name' => 'Accurity', 'city' => 'Leusden', 'iban' => 'NL54 KNAB 0601 8127 35']);
        $invoice = Invoice::factory()->final()->create(['invoice_date' => '2026-07-07', 'due_date' => '2026-08-04']);

        $html = view('pdfs.invoice', [
            'invoice' => $invoice,
            'settings' => $settings,
            'totalHours' => 0,
        ])->render();

        $this->assertStringContainsString('Binnen 28 dagen na factuurdatum van 07-07-2026', $html);
        $this->assertStringContainsString('Rekeningnummer:', $html);
        $this->assertStringContainsString('NL54 KNAB 0601 8127 35', $html);
        $this->assertStringContainsString('te Leusden', $html);
    }

    public function test_admin_can_regenerate_the_pdfs_of_all_invoices_that_have_one(): void
    {
        Storage::fake('invoices');
        Storage::disk('invoices')->put('2026/FINAL-1.pdf', 'old final');
        Storage::disk('invoices')->put('2026/CANCELLED-1.pdf', 'old cancelled');
        Storage::disk('invoices')->put('2026/FINAL-1-urenspecificatie.pdf', 'old timesheet');

        $final = Invoice::factory()->final()->paid()->create(['invoice_number' => 'FINAL-1', 'invoice_date' => '2026-10-01', 'pdf_path' => '2026/FINAL-1.pdf', 'total' => 605]);
        $cancelled = Invoice::factory()->cancelled()->create(['invoice_number' => 'CANCELLED-1', 'invoice_date' => '2026-10-01', 'pdf_path' => '2026/CANCELLED-1.pdf']);
        $draft = Invoice::factory()->create(['status' => 'draft', 'invoice_number' => 'DRAFT-1', 'invoice_date' => '2026-10-01']);

        $response = $this->actingAs($this->admin())->post('/admin/invoices/regenerate-pdfs');

        $response->assertRedirect(route('admin.invoices.index'));
        $response->assertSessionHas('status', "2 PDF's opnieuw gegenereerd.");

        $this->assertStringStartsWith('%PDF', Storage::disk('invoices')->get('2026/FINAL-1.pdf'));
        $this->assertStringStartsWith('%PDF', Storage::disk('invoices')->get('2026/CANCELLED-1.pdf'));
        $this->assertStringStartsWith('%PDF', Storage::disk('invoices')->get('2026/FINAL-1-urenspecificatie.pdf'));
        $this->assertStringStartsWith('%PDF', Storage::disk('invoices')->get('2026/CANCELLED-1-urenspecificatie.pdf'));
        Storage::disk('invoices')->assertMissing('2026/DRAFT-1.pdf');

        $final->refresh();
        $this->assertSame('final', $final->status);
        $this->assertSame('paid', $final->payment_status);
        $this->assertSame('605.00', $final->total);
        $this->assertSame('2026/FINAL-1-urenspecificatie.pdf', $final->timesheet_pdf_path);
        $this->assertSame('cancelled', $cancelled->fresh()->status);
        $this->assertNull($draft->fresh()->pdf_path);
    }

    public function test_client_role_cannot_regenerate_the_pdfs(): void
    {
        Storage::fake('invoices');
        Storage::disk('invoices')->put('2026/FINAL-1.pdf', 'old final');
        Invoice::factory()->final()->create(['invoice_number' => 'FINAL-1', 'invoice_date' => '2026-10-01', 'pdf_path' => '2026/FINAL-1.pdf']);

        $response = $this->actingAs(User::factory()->create(['role' => 'client']))->post('/admin/invoices/regenerate-pdfs');

        $response->assertForbidden();
        $this->assertSame('old final', Storage::disk('invoices')->get('2026/FINAL-1.pdf'));
    }

    public function test_admin_can_download_the_generated_pdf(): void
    {
        Storage::fake('invoices');
        Storage::disk('invoices')->put('2026/TEST-1.pdf', '%PDF-1.4 fake content');
        $invoice = Invoice::factory()->final()->create(['pdf_path' => '2026/TEST-1.pdf']);

        $response = $this->actingAs($this->admin())->get("/admin/invoices/{$invoice->id}/download");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_downloading_is_not_available_before_finalizing(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft', 'pdf_path' => null]);

        $response = $this->actingAs($this->admin())->get("/admin/invoices/{$invoice->id}/download");

        $response->assertNotFound();
    }

    private function setStatus(Invoice $invoice, string $status): TestResponse
    {
        return $this->actingAs($this->admin())
            ->from('/admin/invoices')
            ->patch("/admin/invoices/{$invoice->id}/status", ['status' => $status]);
    }

    public function test_admin_can_cancel_a_final_invoice(): void
    {
        $invoice = Invoice::factory()->final()->create(['invoice_number' => '1012620-ABC', 'pdf_path' => '2026/1012620-ABC.pdf']);

        $response = $this->setStatus($invoice, 'cancelled');

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/invoices');
        $invoice->refresh();
        $this->assertSame('cancelled', $invoice->status);
        $this->assertSame('1012620-ABC', $invoice->invoice_number);
        $this->assertSame('2026/1012620-ABC.pdf', $invoice->pdf_path);
    }

    public function test_a_draft_invoice_cannot_be_cancelled(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $response = $this->setStatus($invoice, 'cancelled');

        $response->assertSessionHasErrors('status');
        $this->assertSame('draft', $invoice->fresh()->status);
    }

    public function test_cancelling_an_invoice_does_not_unlock_its_time_entries(): void
    {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->final()->create(['project_id' => $project->id]);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id]);
        TimeEntry::query()->whereKey($entry->id)->update(['invoice_id' => $invoice->id]);

        $this->setStatus($invoice, 'cancelled');

        $this->assertSame($invoice->id, $entry->fresh()->invoice_id);
    }

    public function test_admin_can_mark_a_final_invoice_as_paid(): void
    {
        $invoice = Invoice::factory()->final()->create();

        $response = $this->setStatus($invoice, 'paid');

        $response->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_a_paid_invoice_can_be_set_back_to_open(): void
    {
        $invoice = Invoice::factory()->final()->paid()->create();

        $this->setStatus($invoice, 'open');

        $invoice->refresh();
        $this->assertSame('final', $invoice->status);
        $this->assertSame('open', $invoice->payment_status);
        $this->assertNull($invoice->paid_at);
    }

    public function test_a_cancelled_invoice_can_be_reinstated_keeping_its_locked_entries_and_pdf(): void
    {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->cancelled()->create(['project_id' => $project->id, 'pdf_path' => '2026/TEST-1.pdf']);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id]);
        TimeEntry::query()->whereKey($entry->id)->update(['invoice_id' => $invoice->id]);

        $this->setStatus($invoice, 'open');

        $invoice->refresh();
        $this->assertSame('final', $invoice->status);
        $this->assertSame('2026/TEST-1.pdf', $invoice->pdf_path);
        $this->assertSame($invoice->id, $entry->fresh()->invoice_id);
    }

    public function test_setting_a_draft_to_open_finalizes_it(): void
    {
        Storage::fake('invoices');

        $project = Project::factory()->create();
        $invoice = Invoice::factory()->create(['project_id' => $project->id, 'client_id' => $project->client_id, 'period_start' => '2026-09-01', 'period_end' => '2026-09-01', 'status' => 'draft']);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => '2.00', 'rate' => 100]);

        $this->setStatus($invoice, 'open');

        $invoice->refresh();
        $this->assertSame('final', $invoice->status);
        $this->assertSame('242.00', $invoice->total);
        $this->assertSame($invoice->id, $entry->fresh()->invoice_id);
        Storage::disk('invoices')->assertExists($invoice->pdf_path);
    }

    public function test_setting_a_draft_to_paid_finalizes_it_and_records_the_payment(): void
    {
        Storage::fake('invoices');

        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $this->setStatus($invoice, 'paid');

        $invoice->refresh();
        $this->assertSame('final', $invoice->status);
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertNotNull($invoice->paid_at);
        Storage::disk('invoices')->assertExists($invoice->pdf_path);
    }

    public function test_setting_a_paid_invoice_back_to_draft_unlocks_entries_and_removes_the_pdf(): void
    {
        Storage::fake('invoices');
        Storage::disk('invoices')->put('2026/TEST-1.pdf', '%PDF-1.4 fake content');
        Storage::disk('invoices')->put('2026/TEST-1-urenspecificatie.pdf', '%PDF-1.4 fake content');

        $project = Project::factory()->create();
        $invoice = Invoice::factory()->final()->paid()->create(['project_id' => $project->id, 'pdf_path' => '2026/TEST-1.pdf', 'timesheet_pdf_path' => '2026/TEST-1-urenspecificatie.pdf']);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id]);
        TimeEntry::query()->whereKey($entry->id)->update(['invoice_id' => $invoice->id]);

        $this->setStatus($invoice, 'draft');

        $invoice->refresh();
        $this->assertSame('draft', $invoice->status);
        $this->assertSame('open', $invoice->payment_status);
        $this->assertNull($invoice->paid_at);
        $this->assertNull($invoice->pdf_path);
        $this->assertNull($entry->fresh()->invoice_id);
        Storage::disk('invoices')->assertMissing('2026/TEST-1.pdf');
        $this->assertNull($invoice->timesheet_pdf_path);
        Storage::disk('invoices')->assertMissing('2026/TEST-1-urenspecificatie.pdf');
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $invoice = Invoice::factory()->final()->create();

        $response = $this->setStatus($invoice, 'bogus');

        $response->assertSessionHasErrors('status');
    }

    public function test_client_role_cannot_change_an_invoice_status(): void
    {
        $invoice = Invoice::factory()->final()->create();

        $response = $this->actingAs(User::factory()->create(['role' => 'client']))
            ->patch("/admin/invoices/{$invoice->id}/status", ['status' => 'paid']);

        $response->assertForbidden();
        $this->assertSame('open', $invoice->fresh()->payment_status);
    }

    public function test_the_overview_sorts_on_period_descending_by_default_and_toggles_to_ascending(): void
    {
        Invoice::factory()->create(['invoice_number' => 'PERIOD-AUG', 'period_start' => '2026-08-01', 'period_end' => '2026-08-01']);
        Invoice::factory()->create(['invoice_number' => 'PERIOD-DEC-2025', 'period_start' => '2025-12-01', 'period_end' => '2025-12-01']);
        Invoice::factory()->create(['invoice_number' => 'PERIOD-SEP', 'period_start' => '2026-09-01', 'period_end' => '2026-09-01']);

        $admin = $this->admin();

        $default = $this->actingAs($admin)->get('/admin/invoices');
        $default->assertSeeInOrder(['PERIOD-SEP', 'PERIOD-AUG', 'PERIOD-DEC-2025']);
        $default->assertSee(e(route('admin.invoices.index', ['direction' => 'asc'])), false);

        $ascending = $this->actingAs($admin)->get('/admin/invoices?direction=asc');
        $ascending->assertSeeInOrder(['PERIOD-DEC-2025', 'PERIOD-AUG', 'PERIOD-SEP']);
        $ascending->assertSee(e(route('admin.invoices.index', ['direction' => 'desc'])), false);
    }

    public function test_no_approval_warning_is_shown_for_a_client_that_does_not_require_approval(): void
    {
        $client = Client::factory()->withoutApproval()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05']);
        $invoice = Invoice::factory()->create(['client_id' => $client->id, 'project_id' => $project->id, 'period_start' => '2026-09-01', 'period_end' => '2026-09-01']);

        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/invoices/create?'.http_build_query([
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
        ]))->assertOk()->assertDontSee('Let op:');

        $this->actingAs($admin)->get("/admin/invoices/{$invoice->id}/edit")->assertOk()->assertDontSee('Let op:');
    }

    public function test_the_overview_shows_a_distinct_label_for_each_status_and_payment_combination(): void
    {
        Invoice::factory()->create(['status' => 'draft', 'invoice_number' => 'DRAFT-1', 'invoice_date' => '2026-10-04']);
        Invoice::factory()->final()->create(['invoice_number' => 'OPEN-1', 'payment_status' => 'open', 'invoice_date' => '2026-10-03']);
        Invoice::factory()->final()->create(['invoice_number' => 'PAID-1', 'payment_status' => 'paid', 'paid_at' => now(), 'invoice_date' => '2026-10-02']);
        Invoice::factory()->cancelled()->create(['invoice_number' => 'CANCELLED-1', 'invoice_date' => '2026-10-01']);

        $response = $this->actingAs($this->admin())->get('/admin/invoices');

        $response->assertOk();
        $response->assertSeeInOrder(['>Concept</span>', '>Openstaand</span>', '>Betaald</span>', '>Geannuleerd</span>'], false);
    }

    public function test_the_creation_screen_sums_the_uninvoiced_hours_of_all_months_in_the_range(): void
    {
        $project = Project::factory()->create(['rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-07-31', 'hours' => '9.00', 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-08-01', 'hours' => '2.00', 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-30', 'hours' => '3.00', 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-10-01', 'hours' => '9.00', 'rate' => 100]);

        $response = $this->actingAs($this->admin())->get('/admin/invoices/create?'.http_build_query([
            'project_id' => $project->id,
            'from' => '2026-08',
            'to' => '2026-09',
        ]));

        $response->assertOk();
        $response->assertSee('500,00');
        $response->assertSee('name="period_start" value="2026-08"', false);
        $response->assertSee('name="period_end" value="2026-09"', false);
    }

    public function test_the_creation_screen_proposes_the_default_line_text_within_one_vat_period(): void
    {
        $project = Project::factory()->create(['name' => 'Datamigratie']);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-07-05']);

        $response = $this->actingAs($this->admin())->get('/admin/invoices/create?'.http_build_query([
            'project_id' => $project->id,
            'from' => '2026-07',
            'to' => '2026-09',
        ]));

        $response->assertOk();
        $response->assertSee('value="Datamigratie — werkzaamheden juli – september 2026"', false);
        $response->assertDontSee('meer dan één btw-periode');
    }

    public function test_the_creation_screen_asks_for_the_line_text_when_the_range_spans_vat_periods(): void
    {
        $project = Project::factory()->create(['name' => 'Datamigratie']);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05']);

        $response = $this->actingAs($this->admin())->get('/admin/invoices/create?'.http_build_query([
            'project_id' => $project->id,
            'from' => '2026-09',
            'to' => '2026-10',
        ]));

        $response->assertOk();
        $response->assertSee('meer dan één btw-periode');
        $response->assertDontSee('werkzaamheden september – oktober 2026');
    }

    public function test_admin_can_save_a_draft_invoice_over_several_months(): void
    {
        $project = Project::factory()->create(['name' => 'Datamigratie', 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-07-05', 'hours' => '1.00', 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => '2.00', 'rate' => 100]);

        $response = $this->actingAs($this->admin())->post('/admin/invoices', [
            'project_id' => $project->id,
            'period_start' => '2026-07',
            'period_end' => '2026-09',
            'line_description' => '',
            'invoice_number' => '1012620-ABC',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-10-15',
        ]);

        $response->assertSessionHasNoErrors();
        $invoice = Invoice::query()->sole();
        $this->assertSame('2026-07-01', $invoice->period_start->toDateString());
        $this->assertSame('2026-09-01', $invoice->period_end->toDateString());
        $this->assertSame('300.00', $invoice->subtotal);
        $this->assertSame('Datamigratie — werkzaamheden juli – september 2026', $invoice->line_description);
    }

    public function test_the_line_text_is_required_when_the_range_spans_vat_periods(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05']);

        $payload = [
            'project_id' => $project->id,
            'period_start' => '2026-09',
            'period_end' => '2026-10',
            'invoice_number' => '1012620-ABC',
            'invoice_date' => '2026-11-01',
            'due_date' => '2026-11-15',
        ];

        $this->actingAs($this->admin())->post('/admin/invoices', $payload)->assertSessionHasErrors('line_description');
        $this->assertSame(0, Invoice::query()->count());

        $this->actingAs($this->admin())->post('/admin/invoices', [...$payload, 'line_description' => 'Advieswerkzaamheden'])->assertSessionHasNoErrors();
        $this->assertSame('Advieswerkzaamheden', Invoice::query()->sole()->line_description);
    }

    public function test_with_monthly_vat_periods_two_months_span_vat_periods(): void
    {
        Setting::current()->update(['vat_period' => 'month']);
        $project = Project::factory()->create();
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-07-05']);

        $response = $this->actingAs($this->admin())->post('/admin/invoices', [
            'project_id' => $project->id,
            'period_start' => '2026-07',
            'period_end' => '2026-08',
            'invoice_number' => '1012620-ABC',
            'invoice_date' => '2026-09-01',
            'due_date' => '2026-09-15',
        ]);

        $response->assertSessionHasErrors('line_description');
    }

    public function test_a_draft_cannot_be_saved_without_uninvoiced_hours_in_the_range(): void
    {
        $project = Project::factory()->create();
        $invoiced = Invoice::factory()->final()->create(['project_id' => $project->id, 'client_id' => $project->client_id]);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05']);
        TimeEntry::query()->whereKey($entry->id)->update(['invoice_id' => $invoiced->id]);

        $response = $this->actingAs($this->admin())->post('/admin/invoices', [
            'project_id' => $project->id,
            'period_start' => '2026-08',
            'period_end' => '2026-09',
            'invoice_number' => '1012620-ABC',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-10-15',
        ]);

        $response->assertSessionHasErrors('period_start');
        $this->assertSame(1, Invoice::query()->count());
    }

    public function test_the_end_month_cannot_be_before_the_start_month(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05']);

        $response = $this->actingAs($this->admin())->post('/admin/invoices', [
            'project_id' => $project->id,
            'period_start' => '2026-09',
            'period_end' => '2026-08',
            'invoice_number' => '1012620-ABC',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-10-15',
        ]);

        $response->assertSessionHasErrors('period_end');
    }

    public function test_the_line_text_of_a_draft_can_be_changed_but_not_emptied_when_it_spans_vat_periods(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft', 'period_start' => '2026-09-01', 'period_end' => '2026-10-01', 'line_description' => 'Oud']);
        $payload = ['invoice_number' => $invoice->invoice_number, 'invoice_date' => '2026-11-01', 'due_date' => '2026-11-15'];

        $this->actingAs($this->admin())->put("/admin/invoices/{$invoice->id}", [...$payload, 'line_description' => ''])->assertSessionHasErrors('line_description');
        $this->actingAs($this->admin())->put("/admin/invoices/{$invoice->id}", [...$payload, 'line_description' => 'Nieuw'])->assertSessionHasNoErrors();

        $this->assertSame('Nieuw', $invoice->fresh()->line_description);
    }

    public function test_finalizing_a_draft_over_several_months_locks_only_the_entries_in_the_range(): void
    {
        Storage::fake('invoices');

        $project = Project::factory()->create(['rate' => 100]);
        $before = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-06-30', 'hours' => '1.00', 'rate' => 100]);
        $first = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-07-01', 'hours' => '1.00', 'rate' => 100]);
        $last = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-30', 'hours' => '2.00', 'rate' => 100]);
        $after = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-10-01', 'hours' => '1.00', 'rate' => 100]);
        $invoice = Invoice::factory()->create([
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'period_start' => '2026-07-01',
            'period_end' => '2026-09-01',
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin())->post("/admin/invoices/{$invoice->id}/finalize");

        $invoice->refresh();
        $this->assertSame('300.00', $invoice->subtotal);
        $this->assertSame($invoice->id, $first->fresh()->invoice_id);
        $this->assertSame($invoice->id, $last->fresh()->invoice_id);
        $this->assertNull($before->fresh()->invoice_id);
        $this->assertNull($after->fresh()->invoice_id);
    }

    public function test_the_invoice_pdf_shows_the_line_text_and_no_timesheet(): void
    {
        $invoice = Invoice::factory()->final()->create(['line_description' => 'Advieswerkzaamheden Q3 en Q4']);

        $html = view('pdfs.invoice', [
            'invoice' => $invoice,
            'settings' => Setting::current(),
            'totalHours' => 5,
        ])->render();

        $this->assertStringContainsString('Advieswerkzaamheden Q3 en Q4', $html);
        $this->assertStringNotContainsString('pagina 2', $html);
        $this->assertStringNotContainsString('page-break', $html);
    }

    public function test_the_timesheet_pdf_groups_the_hours_per_month_without_an_approval_note(): void
    {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->final()->create(['project_id' => $project->id, 'client_id' => $project->client_id, 'period_start' => '2026-08-01', 'period_end' => '2026-09-01']);
        $entries = collect([
            TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-08-05', 'hours' => '1.50', 'description' => 'Augustuswerk']),
            TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => '2.25', 'description' => 'Septemberwerk']),
        ]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 8, 'status' => 'approved', 'approved_at' => now()]);

        $html = view('pdfs.timesheet', [
            'invoice' => $invoice,
            'settings' => Setting::current(),
            'timeEntriesPerMonth' => $entries->groupBy(fn (TimeEntry $entry) => $entry->date->format('Y-m')),
            'totalHours' => 3.75,
        ])->render();

        $this->assertStringContainsString('Urenspecificatie augustus – september 2026', $html);
        $this->assertStringContainsString("Bijlage bij factuur {$invoice->invoice_number}", $html);
        $this->assertMatchesRegularExpression('/Augustus 2026.*Augustuswerk.*Subtotaal augustus 2026.*1,50.*September 2026.*Septemberwerk.*Subtotaal september 2026.*2,25.*Totaal.*3,75/s', $html);
        $this->assertStringNotContainsString('goedgekeurd', $html);
    }

    public function test_admin_can_download_the_timesheet_pdf(): void
    {
        Storage::fake('invoices');
        Storage::disk('invoices')->put('2026/TEST-1-urenspecificatie.pdf', '%PDF-1.4 fake content');
        $invoice = Invoice::factory()->final()->create(['invoice_number' => 'TEST-1', 'timesheet_pdf_path' => '2026/TEST-1-urenspecificatie.pdf']);

        $response = $this->actingAs($this->admin())->get("/admin/invoices/{$invoice->id}/timesheet");

        $response->assertOk();
        $response->assertDownload('TEST-1-urenspecificatie.pdf');
    }

    public function test_the_overview_shows_the_period_range_and_sorts_on_the_end_month(): void
    {
        Invoice::factory()->create(['invoice_number' => 'RANGE-1', 'period_start' => '2025-12-01', 'period_end' => '2026-02-01']);
        Invoice::factory()->create(['invoice_number' => 'SINGLE-1', 'period_start' => '2026-01-01', 'period_end' => '2026-01-01']);

        $response = $this->actingAs($this->admin())->get('/admin/invoices');

        $response->assertSeeInOrder(['RANGE-1', 'SINGLE-1']);
        $response->assertSee('dec. 2025 – feb. 2026');
    }
}
