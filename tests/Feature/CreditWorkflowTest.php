<?php

namespace Tests\Feature;

use App\Models\CreditApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CreditWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
    }

    public function test_login_requires_active_account(): void
    {
        $this->seed();
        $this->loginAs('marketing')
            ->assertOk()->assertJsonPath('user.username', 'marketing');
        $this->getJson('/api/session')->assertJsonPath('user.username', 'marketing');

        User::where('username', 'marketing')->update(['is_active' => false]);
        $this->getJson('/api/session')->assertUnauthorized();
        $this->postJson('/api/login', ['username' => 'marketing', 'password' => 'Demo12345!'])
            ->assertUnprocessable();
    }

    public function test_application_moves_through_approval_documents_and_disbursement(): void
    {
        $this->seed();
        Storage::fake('local');
        $this->loginAs('marketing');
        $this->post('/api/applications', [
            'nik' => '1234567890123456', 'name' => 'Rina Putri', 'birth_date' => '1990-05-10',
            'marital_status' => 'menikah', 'spouse_name' => 'Bima Putra', 'phone' => '081234567890',
            'address' => 'Jakarta', 'dealer_name' => 'Dealer Sentral', 'vehicle_brand' => 'Toyota',
            'vehicle_model' => 'Avanza', 'vehicle_type' => 'MPV', 'vehicle_color' => 'Hitam',
            'vehicle_price' => 250000000, 'down_payment' => 50000000,
            'tenor_months' => 48, 'monthly_installment' => 5000000,
            'ktp' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
            'kartu_keluarga' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
            'bukti_tanda_jadi' => UploadedFile::fake()->create('tanda-jadi.pdf', 100, 'application/pdf'),
        ])->assertCreated();

        $application = CreditApplication::firstOrFail();
        $this->assertSame('submitted', $application->status);
        $this->assertCount(3, $application->documents);
        $this->postJson("/api/applications/{$application->id}/decision", ['decision' => 'approved'])->assertForbidden();
        $this->loginAs('approver');
        $this->postJson("/api/applications/{$application->id}/decision", ['decision' => 'approved'])->assertOk();
        $this->loginAs('backoffice');
        $this->postJson("/api/applications/{$application->id}/issue")->assertOk();
        $this->loginAs('marketing');
        $this->post('/api/applications/'.$application->id.'/signed', [
            'signed_document' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
        ])->assertOk();
        $this->loginAs('backoffice');
        $this->postJson("/api/applications/{$application->id}/disburse", [
            'reference_no' => 'TRX-001', 'disbursed_on' => now()->toDateString(),
        ])->assertOk();

        $application->refresh();
        $this->assertSame('disbursed', $application->status);
        $this->assertEquals(200000000, $application->disbursement->amount);
        $this->assertSame(5, $application->events()->count());
        $this->postJson("/api/applications/{$application->id}/disburse", [
            'reference_no' => 'TRX-002', 'disbursed_on' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_reports_include_empty_area_and_menu_permissions(): void
    {
        $this->seed();
        $this->loginAs('backoffice');
        $this->get('/api/reports')
            ->assertOk()->assertJsonPath('areas.0.area_name', 'Bandung')
            ->assertJsonPath('areas.0.active_user_count', 0)
            ->assertJsonPath('areas.1.active_user_count', 3)
            ->assertJsonCount(6, 'access');
        $this->loginAs('marketing');
        $this->get('/api/reports')->assertForbidden();
    }

    private function loginAs(string $username): TestResponse
    {
        $response = $this->postJson('/api/login', ['username' => $username, 'password' => 'Demo12345!']);
        $cookie = $response->headers->getCookies();
        foreach ($cookie as $item) {
            $this->withUnencryptedCookie($item->getName(), $item->getValue());
        }

        return $response;
    }
}
