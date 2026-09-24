<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Illuminate\Http\Request;

class BacklogController extends Controller
{
    public function index()
    {
        $logs = LoginLog::with('user')->latest()->paginate(15);
        $totalLogins = LoginLog::count();
        $uniqueDevices = LoginLog::distinct('device_type')->count('device_type');
        $todayLogins = LoginLog::whereDate('login_at', now()->today())->count();

        return view('backlog.index', compact('logs', 'totalLogins', 'uniqueDevices', 'todayLogins'));
    }
}