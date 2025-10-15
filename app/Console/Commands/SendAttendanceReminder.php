<?php

namespace App\Console\Commands;

use App\Events\AttendanceReminderEvent;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\EmployeeShiftSchedule;
use App\Models\Holiday;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAttendanceReminder extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-attendance-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send attendance reminder to the employee';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        Company::active()
            ->select(['companies.id as id', 'timezone', 'attendance_settings.*'])
            ->join('attendance_settings', 'attendance_settings.company_id', '=', 'companies.id')
            ->where('alert_after_status', 1)
            ->whereNotNull('alert_after')
            ->where('alert_after', '<>', 0)->chunk(50, function ($companies) {

                foreach ($companies as $company) {

                    // if setting is disabled, continue
                    if ($company->alert_after_status == 0) {
                        continue;
                    }

                    $today = now($company->timezone)->format('Y-m-d');

                    $holiday = Holiday::where('company_id', $company->id)->where('date', $today)->first();

                    // Today is holiday
                    if ($holiday) {
                        continue;
                    }

                    $employeeShiftTime = EmployeeShiftSchedule::with('shift', 'user.employeeDetail')
                        ->where(function ($query) use ($company) {
                            $query->where('shift_start_time', '<=', now($company->timezone));
                            $query->where('shift_end_time', '>=', now($company->timezone));
                        })
                        ->whereHas('shift', function ($query) {
                            $query->where('shift_name', '<>', 'Day Off');
                        })
                        ->whereHas('user', function ($query) use ($today, $company) {
                            $query->where('company_id', $company->id)
                                ->whereHas('employeeDetail', function ($query) use ($today) {
                                    $query->where('attendance_reminder', '!=', $today)
                                        ->orWhereNull('attendance_reminder');
                                });
                        })->get();


                    foreach ($employeeShiftTime as $employeeShiftTimes) {

                        if (is_null($employeeShiftTimes->shift) || is_null($employeeShiftTimes->shift->office_start_time)) {
                            continue;
                        }

                        // Skip Day Off (also excluded in query; guard for safety)
                        if ($employeeShiftTimes->shift->shift_name == 'Day Off') {
                            continue;
                        }

                        $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $employeeShiftTimes->shift->office_start_time, $company->timezone);
                        $currentDateTime = now($company->timezone)->addMinutes((int)$company->alert_after ?? 0);

                        if ($currentDateTime->greaterThan($startDateTime)) {

                            $clockInCount = Attendance::getTotalUserClockInWithTime(Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $employeeShiftTimes->shift->office_start_time), Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $employeeShiftTimes->shift->office_end_time), $employeeShiftTimes->user_id);

                            if (!$clockInCount) {
                                // Skip approved full-day leave (single/multiple); half-day may still need to clock in
                                $onApprovedFullDayLeave = Leave::where('user_id', $employeeShiftTimes->user_id)
                                    ->whereDate('leave_date', $today)
                                    ->where('status', 'approved')
                                    ->whereIn('duration', ['single', 'multiple'])
                                    ->exists();

                                if ($onApprovedFullDayLeave) {
                                    continue;
                                }

                                event(new AttendanceReminderEvent($employeeShiftTimes->user));
                                $employeeShiftTimes->user->employeeDetail->attendance_reminder = $today;
                                $employeeShiftTimes->user->employeeDetail->saveQuietly();
                            }

                        }

                    }
                }

            });

        return Command::SUCCESS;

    }

}
