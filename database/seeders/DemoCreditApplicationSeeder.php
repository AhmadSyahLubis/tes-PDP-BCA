<?php

namespace Database\Seeders;

use App\Models\CreditApplication;
use App\Models\Customer;
use App\Models\Disbursement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DemoCreditApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $marketing = User::where('username', 'marketing')->firstOrFail();
        $approver = User::where('username', 'approver')->firstOrFail();
        $backoffice = User::where('username', 'backoffice')->firstOrFail();

        $cases = [
            ['no' => 'CR-DEMO-001', 'status' => 'submitted', 'days' => 1, 'nik' => '9000000000000001', 'name' => 'Sari Wulandari', 'birth' => '1992-04-12', 'marital' => 'menikah', 'spouse' => 'Dimas Wicaksono', 'phone' => '080000000001', 'address' => 'Jl. Cempaka Raya 10, Jakarta Pusat', 'dealer' => 'Dealer Citra Mobil', 'brand' => 'Toyota', 'model' => 'Avanza', 'type' => 'MPV', 'color' => 'Putih', 'price' => 240000000, 'down' => 60000000, 'tenor' => 48, 'installment' => 5100000],
            ['no' => 'CR-DEMO-002', 'status' => 'rejected', 'days' => 8, 'nik' => '9000000000000002', 'name' => 'Rahmat Pradana', 'birth' => '1988-09-23', 'marital' => 'belum_menikah', 'spouse' => null, 'phone' => '080000000002', 'address' => 'Jl. Melati 22, Jakarta Barat', 'dealer' => 'Dealer Sinar Otomotif', 'brand' => 'Honda', 'model' => 'Brio', 'type' => 'Hatchback', 'color' => 'Merah', 'price' => 180000000, 'down' => 20000000, 'tenor' => 60, 'installment' => 3700000],
            ['no' => 'CR-DEMO-003', 'status' => 'approved', 'days' => 7, 'nik' => '9000000000000003', 'name' => 'Dewi Lestari', 'birth' => '1990-06-17', 'marital' => 'menikah', 'spouse' => 'Fajar Mahendra', 'phone' => '080000000003', 'address' => 'Jl. Anggrek 7, Jakarta Selatan', 'dealer' => 'Dealer Mandiri Motor', 'brand' => 'Mitsubishi', 'model' => 'Xpander', 'type' => 'MPV', 'color' => 'Abu-abu', 'price' => 305000000, 'down' => 90000000, 'tenor' => 48, 'installment' => 6200000],
            ['no' => 'CR-DEMO-004', 'status' => 'issued', 'days' => 6, 'nik' => '9000000000000004', 'name' => 'Bagas Pratama', 'birth' => '1985-11-08', 'marital' => 'menikah', 'spouse' => 'Nina Paramita', 'phone' => '080000000004', 'address' => 'Jl. Kenanga 15, Jakarta Timur', 'dealer' => 'Dealer Citra Mobil', 'brand' => 'Suzuki', 'model' => 'XL7', 'type' => 'SUV', 'color' => 'Hitam', 'price' => 280000000, 'down' => 70000000, 'tenor' => 48, 'installment' => 6000000],
            ['no' => 'CR-DEMO-005', 'status' => 'signed', 'days' => 5, 'nik' => '9000000000000005', 'name' => 'Maya Fitriani', 'birth' => '1995-01-30', 'marital' => 'belum_menikah', 'spouse' => null, 'phone' => '080000000005', 'address' => 'Jl. Flamboyan 4, Jakarta Utara', 'dealer' => 'Dealer Sinar Otomotif', 'brand' => 'Toyota', 'model' => 'Raize', 'type' => 'SUV', 'color' => 'Biru', 'price' => 250000000, 'down' => 80000000, 'tenor' => 36, 'installment' => 5900000],
            ['no' => 'CR-DEMO-006', 'status' => 'disbursed', 'days' => 5, 'nik' => '9000000000000006', 'name' => 'Andi Saputra', 'birth' => '1987-03-14', 'marital' => 'menikah', 'spouse' => 'Rina Kurnia', 'phone' => '080000000006', 'address' => 'Jl. Teratai 18, Jakarta Pusat', 'dealer' => 'Dealer Mandiri Motor', 'brand' => 'Honda', 'model' => 'HR-V', 'type' => 'SUV', 'color' => 'Silver', 'price' => 390000000, 'down' => 110000000, 'tenor' => 60, 'installment' => 6500000],
        ];

        foreach ($cases as $case) {
            $this->seedCase($case, $marketing, $approver, $backoffice);
        }
    }

    private function seedCase(array $case, User $marketing, User $approver, User $backoffice): void
    {
        if (CreditApplication::where('application_no', $case['no'])->exists()) {
            return;
        }

        $writtenPaths = [];
        try {
            DB::transaction(function () use ($case, $marketing, $approver, $backoffice, &$writtenPaths): void {
                $customer = Customer::firstOrCreate(['nik' => $case['nik']], [
                    'name' => $case['name'], 'birth_date' => $case['birth'],
                    'marital_status' => $case['marital'], 'spouse_name' => $case['spouse'],
                    'phone' => $case['phone'], 'address' => $case['address'],
                ]);
                $createdAt = now()->subDays($case['days'])->setTime(10, 0);
                $steps = $case['status'] === 'rejected'
                    ? ['submitted', 'rejected']
                    : array_slice(['submitted', 'approved', 'issued', 'signed', 'disbursed'], 0, array_search($case['status'], ['submitted', 'approved', 'issued', 'signed', 'disbursed'], true) + 1);

                $applicationId = DB::table('credit_applications')->insertGetId([
                    'application_no' => $case['no'], 'customer_id' => $customer->id,
                    'branch_id' => $marketing->branch_id, 'marketing_id' => $marketing->id,
                    'reviewer_id' => count($steps) > 1 ? $approver->id : null,
                    'dealer_name' => $case['dealer'], 'vehicle_brand' => $case['brand'],
                    'vehicle_model' => $case['model'], 'vehicle_type' => $case['type'],
                    'vehicle_color' => $case['color'], 'vehicle_price' => $case['price'],
                    'down_payment' => $case['down'], 'tenor_months' => $case['tenor'],
                    'monthly_installment' => $case['installment'], 'status' => $case['status'],
                    'decision_note' => $case['status'] === 'rejected' ? 'Berkas pendapatan belum lengkap.' : null,
                    'reviewed_at' => count($steps) > 1 ? $createdAt->copy()->addDay() : null,
                    'issued_at' => in_array('issued', $steps, true) ? $createdAt->copy()->addDays(2) : null,
                    'signed_at' => in_array('signed', $steps, true) ? $createdAt->copy()->addDays(3) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addDays(count($steps) - 1),
                ]);
                $application = CreditApplication::findOrFail($applicationId);

                foreach (['ktp', 'kartu_keluarga', 'bukti_tanda_jadi'] as $type) {
                    $writtenPaths[] = $this->seedDocument($application, $marketing, $case, $type);
                }
                if (in_array('signed', $steps, true)) {
                    $writtenPaths[] = $this->seedDocument($application, $marketing, $case, 'signed_contract');
                }

                foreach ($steps as $index => $step) {
                    $actor = match ($step) {
                        'approved', 'rejected' => $approver,
                        'issued', 'disbursed' => $backoffice,
                        default => $marketing,
                    };
                    $application->events()->create([
                        'actor_id' => $actor->id, 'action' => $step,
                        'note' => match ($step) {
                            'rejected' => 'Berkas pendapatan belum lengkap.',
                            'issued' => 'Kontrak dan PO siap dicetak.',
                            'disbursed' => 'TRX-DEMO-006',
                            default => null,
                        },
                        'created_at' => $createdAt->copy()->addDays($index),
                    ]);
                }

                if ($case['status'] === 'disbursed') {
                    Disbursement::create([
                        'credit_application_id' => $application->id,
                        'processed_by' => $backoffice->id,
                        'amount' => $case['price'] - $case['down'],
                        'disbursed_on' => $createdAt->copy()->addDays(4)->toDateString(),
                        'reference_no' => 'TRX-DEMO-006',
                    ]);
                }
            });
        } catch (Throwable $error) {
            Storage::disk('local')->delete($writtenPaths);
            throw $error;
        }
    }

    private function seedDocument(CreditApplication $application, User $marketing, array $case, string $type): string
    {
        $path = "demo/{$case['no']}/{$type}.pdf";
        $title = match ($type) {
            'ktp' => 'Identitas konsumen',
            'kartu_keluarga' => 'Kartu keluarga',
            'bukti_tanda_jadi' => 'Bukti bayar tanda jadi',
            default => 'Kontrak bertanda tangan',
        };
        if (! Storage::disk('local')->put($path, $this->samplePdf($title, $case))) {
            throw new RuntimeException("Gagal menyimpan dokumen demo {$type}.");
        }
        $application->documents()->create([
            'uploaded_by' => $marketing->id, 'type' => $type,
            'original_name' => "{$type}-{$case['no']}.pdf", 'path' => $path,
            'mime_type' => 'application/pdf',
        ]);

        return $path;
    }

    private function samplePdf(string $title, array $case): string
    {
        $lines = [
            'DOKUMEN SIMULASI', $title, "Pengajuan: {$case['no']}",
            "Konsumen: {$case['name']}", "NIK fiktif: {$case['nik']}",
            'Berkas ini dibuat untuk demonstrasi aplikasi.',
            'Tidak berlaku sebagai dokumen identitas atau kontrak resmi.',
        ];
        $stream = "BT\n";
        foreach ($lines as $index => $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $size = $index === 0 ? 18 : 12;
            $stream .= "/F1 {$size} Tf\n1 0 0 1 50 ".(770 - $index * 38)." Tm\n({$escaped}) Tj\n";
        }
        $stream .= "ET\n";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
