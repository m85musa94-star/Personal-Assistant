<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class HrController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = today();
        $isHr = $user->isHrManager();
        $me = $user->employee;

        $active = Employee::where('status', 'active');
        $headcount = (clone $active)->count();
        $byDept = Department::withCount(['employees as active_count' => fn ($q) => $q->where('status', 'active')])->orderBy('name')->get();
        $unassigned = (clone $active)->whereNull('department_id')->count();

        $onLeave = LeaveRequest::with(['employee', 'type'])->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->get();
        $soon = LeaveRequest::with(['employee', 'type'])->where('status', 'approved')
            ->whereDate('start_date', '>', $today)->whereDate('start_date', '<=', $today->copy()->addDays(14))
            ->orderBy('start_date')->get();

        $pendingQuery = LeaveRequest::with(['employee', 'type'])->where('status', 'pending');
        if (! $isHr) {
            $pendingQuery->whereHas('employee', fn ($q) => $q->where('manager_id', $me?->id ?? 0));
        }
        $pending = $pendingQuery->orderBy('start_date')->get();

        $checkedIn = AttendanceRecord::with('employee')->whereNull('check_out')
            ->whereDate('check_in', $today)->get();

        $expiring = collect();
        if ($isHr) {
            $limit = $today->copy()->addDays(60);
            $expiring = Employee::where('status', 'active')->where(function ($q) use ($limit) {
                $q->whereDate('id_expiry', '<=', $limit)->orWhereDate('passport_expiry', '<=', $limit)->orWhereDate('contract_end', '<=', $limit);
            })->get()->filter(fn (Employee $e) => $e->expiringDocuments() !== [])->values();
        }

        $newHires = (clone $active)->whereDate('hire_date', '>=', $today->copy()->subDays(30))->orderByDesc('hire_date')->get();

        return view('hr.index', compact('headcount', 'byDept', 'unassigned', 'onLeave', 'soon', 'pending', 'checkedIn', 'expiring', 'newHires', 'isHr', 'me'));
    }
}
