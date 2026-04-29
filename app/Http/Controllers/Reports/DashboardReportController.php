<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardReportController extends Controller
{
    public function index()
    {
        return view('reports.dashboard.index');
    }
}
