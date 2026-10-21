<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideCreditApplicationRequest;
use App\Http\Requests\DisburseCreditApplicationRequest;
use App\Http\Requests\StoreCreditApplicationRequest;
use App\Http\Requests\UploadSignedDocumentRequest;
use App\Models\ApplicationDocument;
use App\Models\CreditApplication;
use App\Services\CreditApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CreditApplicationController extends Controller
{
    public function __construct(private readonly CreditApplicationService $applications) {}

    public function index(Request $request)
    {
        return response()->json($this->applications->paginate($request->user(), $request->input('status')));
    }

    public function store(StoreCreditApplicationRequest $request)
    {
        $files = $request->only(['ktp', 'kartu_keluarga', 'bukti_tanda_jadi']);
        $application = $this->applications->create($request->validated(), $request->user(), $files);

        return response()->json(['id' => $application->id, 'message' => 'Pengajuan berhasil dikirim.'], 201);
    }

    public function show(CreditApplication $application)
    {
        Gate::authorize('view', $application);

        return response()->json($application->load([
            'customer', 'branch.area', 'marketing', 'reviewer', 'documents', 'events.actor', 'disbursement',
        ]));
    }

    public function decide(DecideCreditApplicationRequest $request, CreditApplication $application)
    {
        $data = $request->validated();
        $this->applications->decide($application, $request->user(), $data['decision'], $data['decision_note'] ?? null);

        return response()->json(['message' => 'Keputusan pengajuan tersimpan.']);
    }

    public function issue(Request $request, CreditApplication $application)
    {
        Gate::authorize('issue', $application);
        $this->applications->issue($application, $request->user());

        return response()->json(['message' => 'Kontrak dan PO siap dicetak.']);
    }

    public function uploadSigned(UploadSignedDocumentRequest $request, CreditApplication $application)
    {
        $this->applications->uploadSigned($application, $request->user(), $request->file('signed_document'));

        return response()->json(['message' => 'Dokumen bertanda tangan tersimpan.']);
    }

    public function disburse(DisburseCreditApplicationRequest $request, CreditApplication $application)
    {
        $this->applications->disburse($application, $request->user(), $request->validated());

        return response()->json(['message' => 'Pencairan tercatat.']);
    }

    public function download(ApplicationDocument $document)
    {
        Gate::authorize('view', CreditApplication::findOrFail($document->credit_application_id));
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
