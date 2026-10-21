<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index()
    {
        Gate::authorize('viewReports');

        return response()->json($this->reports->summary());
    }
}
