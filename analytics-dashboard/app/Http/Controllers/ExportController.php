<?php

namespace App\Http\Controllers;

use App\Exports\MetricsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $period = max(0, (int) $request->query('period', 7));
        $sourceId = $request->filled('source_id') ? (int) $request->query('source_id') : null;

        $filename = 'analytics-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new MetricsExport($period, $sourceId), $filename);
    }
}
