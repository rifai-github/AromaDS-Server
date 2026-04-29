<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarketingReportController extends Controller
{
    public function index()
    {
        return view('reports.marketing.index');
    }
}
