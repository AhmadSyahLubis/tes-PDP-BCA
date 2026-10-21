<?php

namespace Tests\Feature;

use App\Models\CreditApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_cases_cover_each_stage_and_can_be_seeded_again(): void
    {
        Storage::fake('local');
        $this->seed();

        $this->assertDatabaseCount('credit_applications', 6);
        $this->assertDatabaseCount('application_documents', 20);
        $this->assertDatabaseCount('application_events', 17);
        $this->assertDatabaseCount('disbursements', 1);
        foreach (['submitted', 'rejected', 'approved', 'issued', 'signed', 'disbursed'] as $status) {
            $this->assertSame(1, CreditApplication::where('status', $status)->count());
        }

        $disbursed = CreditApplication::with(['documents', 'events', 'disbursement'])
            ->where('application_no', 'CR-DEMO-006')->firstOrFail();
        $this->assertCount(4, $disbursed->documents);
        $this->assertCount(5, $disbursed->events);
        $this->assertEquals(280000000, $disbursed->disbursement->amount);
        foreach ($disbursed->documents as $document) {
            Storage::disk('local')->assertExists($document->path);
            $this->assertStringStartsWith('%PDF-1.4', Storage::disk('local')->get($document->path));
        }

        $this->seed();
        $this->assertDatabaseCount('credit_applications', 6);
        $this->assertDatabaseCount('application_documents', 20);
        $this->assertDatabaseCount('application_events', 17);
    }
}
