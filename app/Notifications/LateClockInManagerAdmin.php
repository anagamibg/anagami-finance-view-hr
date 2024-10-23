<?php

namespace App\Notifications;

use App\Models\Attendance;
use App\Models\EmailNotificationSetting;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class LateClockInManagerAdmin extends BaseNotification
{

    private $attendance;
    private $emailSetting;
    private $minutesLate;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
        $this->company = $this->attendance->user->company;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)
            ->where('slug', 'late-clock-in-manager-admin')
            ->first();
        $this->minutesLate = $this->calculateMinutesLate();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = ['database'];

        if (!$this->emailSetting) {
            return $via;
        }

        if ($this->emailSetting->send_email == 'yes' && $notifiable->email_notifications && $notifiable->email != '') {
            array_push($via, 'mail');
        }

        if ($this->emailSetting->send_slack == 'yes' && $this->company->slackSetting->status == 'active') {
            $this->slackUserNameCheck($notifiable) ? array_push($via, 'slack') : null;
        }

        if ($this->emailSetting->send_push == 'yes' && push_setting()->status == 'active') {
            array_push($via, OneSignalChannel::class);
        }

        if ($this->emailSetting->send_push == 'yes' && push_setting()->beams_push_status == 'active') {
            $pushNotification = new \App\Http\Controllers\DashboardController();
            $pushUsersIds = [[$notifiable->id]];
            $pushNotification->sendPushNotifications(
                $pushUsersIds,
                __('email.lateClockInManagerAdmin.subject'),
                __('email.lateClockInManagerAdmin.text', ['name' => $this->attendance->user->name])
            );
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $build = parent::build($notifiable);
        $url = route('attendances.index');
        $url = getDomainSpecificUrl($url, $this->company);

        $content = __('email.lateClockInManagerAdmin.text', ['name' => $this->attendance->user->name]) . '<br><br>' .
            __('email.lateClockInManagerAdmin.employee') . ': ' . $this->attendance->user->name . '<br>' .
            __('email.lateClockInManagerAdmin.date') . ': ' . $this->formattedDate() . '<br>' .
            __('email.lateClockInManagerAdmin.clockInTime') . ': ' . $this->formattedClockInTime() . '<br>' .
            __('email.lateClockInManagerAdmin.shiftStart') . ': ' . $this->formattedShiftStart() . '<br>' .
            __('email.lateClockInManagerAdmin.minutesLate') . ': ' . $this->minutesLate;

        $build
            ->subject(__('email.lateClockInManagerAdmin.subject') . ' - ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.lateClockInManagerAdmin.action'),
                'notifiableName' => $notifiable->name
            ]);

        parent::resetLocale();

        return $build;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'id' => $this->attendance->id,
            'user_id' => $this->attendance->user_id,
            'user' => $this->attendance->user,
            'clock_in_time' => $this->attendance->clock_in_time,
            'late' => $this->attendance->late,
            'minutes_late' => $this->minutesLate,
        ];
    }

    public function toSlack($notifiable)
    {
        return $this->slackBuild($notifiable)
            ->content(
                __('email.lateClockInManagerAdmin.subject') . "\n" .
                __('email.lateClockInManagerAdmin.text', ['name' => $this->attendance->user->name]) . "\n" .
                '*' . __('email.lateClockInManagerAdmin.date') . '*: ' . $this->formattedDate() . "\n" .
                '*' . __('email.lateClockInManagerAdmin.clockInTime') . '*: ' . $this->formattedClockInTime() . "\n" .
                '*' . __('email.lateClockInManagerAdmin.minutesLate') . '*: ' . $this->minutesLate
            );
    }

    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->setSubject(__('email.lateClockInManagerAdmin.subject'))
            ->setBody(__('email.lateClockInManagerAdmin.text', ['name' => $this->attendance->user->name]));
    }

    private function calculateMinutesLate(): int
    {
        if (is_null($this->attendance->shift_start_time) || is_null($this->attendance->clock_in_time)) {
            return 0;
        }

        // shift_start_time is stored as company-local wall clock; clock_in_time is UTC
        $shiftStart = $this->shiftStartInCompanyTimezone();
        $clockIn = Carbon::parse($this->attendance->clock_in_time)
            ->timezone($this->company->timezone);

        if ($clockIn->lte($shiftStart)) {
            return 0;
        }

        return (int) $shiftStart->diffInMinutes($clockIn);
    }

    private function formattedDate(): string
    {
        return Carbon::parse($this->attendance->clock_in_time)
            ->timezone($this->company->timezone)
            ->translatedFormat($this->company->date_format);
    }

    private function formattedClockInTime(): string
    {
        return Carbon::parse($this->attendance->clock_in_time)
            ->timezone($this->company->timezone)
            ->translatedFormat($this->company->time_format);
    }

    private function formattedShiftStart(): string
    {
        if (is_null($this->attendance->shift_start_time)) {
            return '-';
        }

        // Do not re-apply company timezone — value is already company-local wall clock
        return $this->shiftStartInCompanyTimezone()
            ->translatedFormat($this->company->time_format);
    }

    private function shiftStartInCompanyTimezone(): Carbon
    {
        $rawShiftStart = $this->attendance->getRawOriginal('shift_start_time')
            ?? $this->attendance->shift_start_time->format('Y-m-d H:i:s');

        return Carbon::parse($rawShiftStart, $this->company->timezone);
    }

}
