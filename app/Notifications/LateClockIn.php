<?php

namespace App\Notifications;

use App\Models\Attendance;
use App\Models\EmailNotificationSetting;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class LateClockIn extends BaseNotification
{

    private $attendance;
    private $emailSetting;
    private $minutesLate;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
        $this->company = $this->attendance->user->company;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)
            ->where('slug', 'late-clock-in')
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
                __('email.lateClockIn.subject'),
                __('email.lateClockIn.text')
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
        $url = route('dashboard');
        $url = getDomainSpecificUrl($url, $this->company);

        $content = __('email.lateClockIn.text') . '<br><br>' .
            __('email.lateClockIn.date') . ': ' . $this->formattedDate() . '<br>' .
            __('email.lateClockIn.clockInTime') . ': ' . $this->formattedClockInTime() . '<br>' .
            __('email.lateClockIn.shiftStart') . ': ' . $this->formattedShiftStart() . '<br>' .
            __('email.lateClockIn.minutesLate') . ': ' . $this->minutesLate;

        $build
            ->subject(__('email.lateClockIn.subject') . ' - ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.lateClockIn.action'),
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
            'clock_in_time' => $this->attendance->clock_in_time,
            'late' => $this->attendance->late,
            'minutes_late' => $this->minutesLate,
        ];
    }

    public function toSlack($notifiable)
    {
        return $this->slackBuild($notifiable)
            ->content(
                __('email.lateClockIn.subject') . "\n" .
                __('email.lateClockIn.text') . "\n" .
                '*' . __('email.lateClockIn.date') . '*: ' . $this->formattedDate() . "\n" .
                '*' . __('email.lateClockIn.clockInTime') . '*: ' . $this->formattedClockInTime() . "\n" .
                '*' . __('email.lateClockIn.minutesLate') . '*: ' . $this->minutesLate
            );
    }

    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->setSubject(__('email.lateClockIn.subject'))
            ->setBody(__('email.lateClockIn.text'));
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
