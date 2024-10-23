<?php

namespace App\Listeners;

use App\Events\LateClockInEvent;
use App\Models\User;
use App\Notifications\LateClockIn;
use App\Notifications\LateClockInManagerAdmin;
use Illuminate\Support\Facades\Notification;

class LateClockInListener
{

    /**
     * Handle the event.
     *
     * @param LateClockInEvent $event
     * @return void
     */
    public function handle(LateClockInEvent $event)
    {
        // AFV (upgrade 6.0.19): new upstream e-mail/in-app alerts of the hidden Attendance module (config/databackd.php)
        // are not sent while the module is hidden - the clock-in button on the dashboard still works, 6.0.10 sent nothing here
        if (databackd_hidden('menu', 'attendance')) {
            return;
        }

        $attendance = $event->attendance;
        $employee = $attendance->user;

        if (!$employee) {
            return;
        }

        Notification::send($employee, new LateClockIn($attendance));

        $recipientIds = User::allAdmins($attendance->company_id)->pluck('id')->toArray();

        $reportingTo = $employee->employeeDetail?->reporting_to;

        if (!is_null($reportingTo)) {
            $recipientIds[] = $reportingTo;
        }

        $recipientIds = array_unique(array_filter($recipientIds));
        $recipientIds = array_diff($recipientIds, [$employee->id]);

        if (empty($recipientIds)) {
            return;
        }

        $recipients = User::whereIn('id', $recipientIds)->get();

        Notification::send($recipients, new LateClockInManagerAdmin($attendance));
    }

}
