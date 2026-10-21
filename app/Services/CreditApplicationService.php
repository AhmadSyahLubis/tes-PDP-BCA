<?php

namespace App\Services;

use App\Models\ApplicationEvent;
use App\Models\CreditApplication;
use App\Models\Customer;
use App\Models\Disbursement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreditApplicationService
{
    public function paginate(User $user, ?string $status): LengthAwarePaginator
    {
        return CreditApplication::with(['customer', 'branch.area', 'marketing'])
            ->when($user->hasRole('marketing'), fn ($query) => $query->where('marketing_id', $user->id))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()->paginate(12);
    }

    public function create(array $data, User $actor, array $files): CreditApplication
    {
        $storedPaths = [];
        try {
            return DB::transaction(function () use ($data, $actor, $files, &$storedPaths) {
                $customer = Customer::updateOrCreate(['nik' => $data['nik']], collect($data)->only([
                    'name', 'birth_date', 'marital_status', 'spouse_name', 'phone', 'address',
                ])->all());
                $application = CreditApplication::create([
                    'application_no' => 'CR-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                    'customer_id' => $customer->id,
                    'branch_id' => $actor->branch_id,
                    'marketing_id' => $actor->id,
                    ...collect($data)->only([
                        'dealer_name', 'vehicle_brand', 'vehicle_model', 'vehicle_type', 'vehicle_color',
                        'vehicle_price', 'down_payment', 'tenor_months', 'monthly_installment',
                    ])->all(),
                    'status' => 'submitted',
                ]);

                foreach ($files as $type => $file) {
                    $path = $this->storeDocument($application, $actor, $type, $file);
                    $storedPaths[] = $path;
                }
                $this->recordEvent($application, $actor, 'submitted');

                return $application;
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($storedPaths);
            throw $error;
        }
    }

    public function decide(CreditApplication $application, User $actor, string $decision, ?string $note): void
    {
        DB::transaction(function () use ($application, $actor, $decision, $note) {
            $locked = $this->lockAtStatus($application, 'submitted');
            $locked->update([
                'status' => $decision, 'decision_note' => $note,
                'reviewer_id' => $actor->id, 'reviewed_at' => now(),
            ]);
            $this->recordEvent($locked, $actor, $decision, $note);
        });
    }

    public function issue(CreditApplication $application, User $actor): void
    {
        DB::transaction(function () use ($application, $actor) {
            $locked = $this->lockAtStatus($application, 'approved');
            $locked->update(['status' => 'issued', 'issued_at' => now()]);
            $this->recordEvent($locked, $actor, 'issued', 'Kontrak dan PO siap dicetak.');
        });
    }

    public function uploadSigned(CreditApplication $application, User $actor, UploadedFile $file): void
    {
        $path = null;
        try {
            DB::transaction(function () use ($application, $actor, $file, &$path) {
                $locked = $this->lockAtStatus($application, 'issued');
                $path = $this->storeDocument($locked, $actor, 'signed_contract', $file);
                $locked->update(['status' => 'signed', 'signed_at' => now()]);
                $this->recordEvent($locked, $actor, 'signed');
            });
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }
    }

    public function disburse(CreditApplication $application, User $actor, array $data): void
    {
        DB::transaction(function () use ($application, $actor, $data) {
            $locked = $this->lockAtStatus($application, 'signed');
            Disbursement::create([
                'credit_application_id' => $locked->id,
                'processed_by' => $actor->id,
                'amount' => $locked->vehicle_price - $locked->down_payment,
                ...$data,
            ]);
            $locked->update(['status' => 'disbursed']);
            $this->recordEvent($locked, $actor, 'disbursed', $data['reference_no']);
        });
    }

    private function lockAtStatus(CreditApplication $application, string $expected): CreditApplication
    {
        $locked = CreditApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
        if ($locked->status !== $expected) {
            throw ValidationException::withMessages(['status' => "Aksi ini hanya berlaku saat status {$expected}."]);
        }

        return $locked;
    }

    private function storeDocument(CreditApplication $application, User $actor, string $type, UploadedFile $file): string
    {
        $path = $file->store("applications/{$application->id}", 'local');
        try {
            $application->documents()->create([
                'uploaded_by' => $actor->id, 'type' => $type,
                'original_name' => $file->getClientOriginalName(), 'path' => $path,
                'mime_type' => $file->getMimeType(),
            ]);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        return $path;
    }

    private function recordEvent(CreditApplication $application, User $actor, string $action, ?string $note = null): void
    {
        ApplicationEvent::create([
            'credit_application_id' => $application->id, 'actor_id' => $actor->id,
            'action' => $action, 'note' => $note, 'created_at' => now(),
        ]);
    }
}
