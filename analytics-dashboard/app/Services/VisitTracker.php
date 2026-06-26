<?php

namespace App\Services;

use App\Models\Metric;
use App\Models\Source;
use Illuminate\Http\Request;

class VisitTracker
{
    public function record(Request $request): void
    {
        $source = Source::resolveFromRequest($request);

        Metric::incrementVisit($source->id);
    }
}
