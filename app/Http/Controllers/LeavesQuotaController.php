<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Exports\LeaveQuotaReportExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Helper\Reply;
use App\Helper\LeaveHelper;
use App\Models\LeaveType;
use App\Scopes\ActiveScope;
use Illuminate\Http\Request;
use App\Models\EmployeeLeaveQuota;
use Illuminate\Support\Facades\Artisan;

class LeavesQuotaController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.leaves';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('leaves', $this->user->modules));
            return $next($request);
        });
    }

    public function update(Request $request, $id)
    {
        // AFV (upgrade 6.0.19): the quota form is shown only with update_leaves_quota = all, the endpoint did not check it
        abort_403(user()->permission('update_leaves_quota') != 'all');

        // AFV (upgrade 6.0.19): employee_leave_quotas has no company_id / CompanyScope - only quotas of this company's users
        $type = EmployeeLeaveQuota::with('leaveType')
            ->whereHas('user', function ($query) {
                $query->withoutGlobalScope(ActiveScope::class)->where('users.company_id', company()->id);
            })
            ->findOrFail($id);
        $leaveType = $type->leaveType;
        $supportsCarryForward = $leaveType && $leaveType->unused_leave === 'carry forward';

        $periodLeaves = (float) ($request->period_leaves ?? $request->leaves ?? 0);
        $carryForwardLeaves = $supportsCarryForward
            ? (float) ($request->carry_forward_leaves ?? 0)
            : 0;

        if ($periodLeaves < 0 || $carryForwardLeaves < 0) {
            return Reply::error('messages.employeeLeaveQuota');
        }

        $totalLeaves = $periodLeaves + $carryForwardLeaves;

        if ($totalLeaves < $type->leaves_used) {
            return Reply::error('messages.employeeLeaveQuota');
        }

        $type->carry_forward_leaves = $carryForwardLeaves;
        $type->no_of_leaves = $totalLeaves;
        $type->leave_type_impact = $request->leaveimpact;

        LeaveHelper::recalculateRemainingAfterUpdate($type, $totalLeaves);

        $type->save();

        session()->forget('user');

        return Reply::success(__('messages.leaveTypeAdded'));
    }

    public function employeeLeaveTypes($userId)
    {
        if ($userId != 0) {
            $employee = User::withoutGlobalScope(ActiveScope::class)->with(['roles', 'leaveTypes'])->findOrFail($userId);
            $options = '';
            
            foreach($employee->leaveTypes as $leavesQuota) {
                $hasLeave = ($leavesQuota->leaveType && $leavesQuota->leaveType->deleted_at == null) ? $leavesQuota->leaveType->leaveTypeCondition($leavesQuota->leaveType, $employee) : false;

                if ($hasLeave) {
                    $labelSuffix = $leavesQuota->leaveType->isUnlimited()
                        ? __('app.unlimitedLeaveType')
                        : $leavesQuota->leaves_remaining;
                    $options .= '<option value="' . $leavesQuota->leave_type_id . '"> ' .  $leavesQuota->leaveType->type_name .' (' . $labelSuffix . ') </option>'; /** @phpstan-ignore-line */
                }
            }
        }
        else {
            $leaveQuotas = LeaveType::all();

            $options = '';

            foreach ($leaveQuotas as $leaveQuota) {
                $labelSuffix = $leaveQuota->isUnlimited()
                    ? __('app.unlimitedLeaveType')
                    : $leaveQuota->no_of_leaves;
                $options .= '<option value="' . $leaveQuota->id . '"> ' .  $leaveQuota->type_name . ' (' . $labelSuffix . ') </option>'; /** @phpstan-ignore-line */
            }
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $options]);
    }

    public function exportAllLeaveQuota($id, $year, $month)
    {
        abort_403(!canDataTableExport());
        // AFV (upgrade 6.0.19): same gate as the leave quota report (LeaveReportController) - the export did not check it
        $viewLeaveReportPermission = user()->permission('view_leave_report');
        abort_403(!in_array($viewLeaveReportPermission, ['all', 'added', 'owned', 'both']));
        abort_403($viewLeaveReportPermission == 'owned' && $id != user()->id);
        $name = __('app.leaveQuotaReport') . '-' . Carbon::createFromDate($year, $month, 1)->startOfDay()->translatedFormat('F-Y');
        return Excel::download(new LeaveQuotaReportExport($id, $year, $month), $name . '.xlsx');
    }

}
