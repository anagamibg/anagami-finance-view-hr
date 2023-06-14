<?php

namespace App\Notifications;

use App\Models\Passport;
use App\Models\VisaDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImmigrationExpiryAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $immigrationDocument;
    protected $alertType;
    protected $documentType;

    /**
     * Create a new notification instance.
     */
    public function __construct($immigrationDocument, string $alertType = 'expiring_soon', string $documentType = 'passport')
    {
        $this->immigrationDocument = $immigrationDocument;
        $this->alertType = $alertType;
        $this->documentType = $documentType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = new MailMessage();

        $documentName = $this->documentType === 'passport' ? __('ui-shared.expiry.passport') : __('ui-shared.expiry.visa');
        $document = mb_strtolower($documentName);
        $documentNumber = $this->documentType === 'passport' ? $this->immigrationDocument->passport_number : $this->immigrationDocument->visa_number;
        $daysUntilExpiry = now()->diffInDays($this->immigrationDocument->expiry_date, false);
        $issueDate = $this->immigrationDocument->issue_date->format('d.m.Y');
        $expiryDate = $this->immigrationDocument->expiry_date->format('d.m.Y');
        $params = ['label' => $documentName, 'document' => $document, 'number' => $documentNumber];

        if ($this->alertType === 'expiring_soon') {
            $message->subject(__('ui-shared.expiry.imm_self_subject', $params))
                ->greeting(__('ui-shared.expiry.greeting', ['name' => $notifiable->name]))
                ->line(__('ui-shared.expiry.imm_self_intro', $params))
                ->line(__('ui-shared.expiry.imm_details', $params))
                ->line(__('ui-shared.expiry.imm_number', $params))
                ->line(__('ui-shared.expiry.issue_date', ['date' => $issueDate]))
                ->line(__('ui-shared.expiry.expiry_date', ['date' => $expiryDate]))
                ->line(__('ui-shared.expiry.days_left', ['days' => (int) round($daysUntilExpiry)]))
                ->line(__('ui-shared.expiry.imm_self_renew', $params));
        } else {
            $message->subject(__('ui-shared.expiry.imm_hr_subject', $params))
                ->greeting(__('ui-shared.expiry.greeting', ['name' => $notifiable->name]))
                ->line(__('ui-shared.expiry.imm_hr_intro', $params))
                ->line(__('ui-shared.expiry.imm_details', $params))
                ->line(__('ui-shared.expiry.employee', ['name' => $this->immigrationDocument->user->name]))
                ->line(__('ui-shared.expiry.imm_number', $params))
                ->line(__('ui-shared.expiry.issue_date', ['date' => $issueDate]))
                ->line(__('ui-shared.expiry.expiry_date', ['date' => $expiryDate]))
                ->line(__('ui-shared.expiry.days_left', ['days' => (int) round($daysUntilExpiry)]))
                ->line(__('ui-shared.expiry.imm_hr_follow', $params));
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $documentName = $this->documentType === 'passport' ? __('ui-shared.expiry.passport') : __('ui-shared.expiry.visa');
        $documentNumber = $this->documentType === 'passport' ? $this->immigrationDocument->passport_number : $this->immigrationDocument->visa_number;
        $daysUntilExpiry = now()->diffInDays($this->immigrationDocument->expiry_date, false);
        $params = ['label' => $documentName, 'document' => mb_strtolower($documentName), 'number' => $documentNumber, 'days' => (int) round($daysUntilExpiry)];

        if ($this->alertType === 'expiring_soon') {
            return [
                'title' => __('ui-shared.expiry.imm_self_title', $params),
                'message' => __('ui-shared.expiry.imm_self_message', $params),
                'document_id' => $this->immigrationDocument->id,
                'document_type' => $this->documentType,
                'document_number' => $documentNumber,
                'expiry_date' => $this->immigrationDocument->expiry_date->format('d M Y'),
                'days_until_expiry' => $daysUntilExpiry,
                'type' => 'immigration_expiry_alert'
            ];
        } else {
            return [
                'title' => __('ui-shared.expiry.imm_hr_title', $params),
                'message' => __('ui-shared.expiry.imm_hr_message', $params + ['name' => $this->immigrationDocument->user->name]),
                'document_id' => $this->immigrationDocument->id,
                'document_type' => $this->documentType,
                'employee_name' => $this->immigrationDocument->user->name,
                'document_number' => $documentNumber,
                'expiry_date' => $this->immigrationDocument->expiry_date->format('d M Y'),
                'days_until_expiry' => $daysUntilExpiry,
                'type' => 'hr_immigration_expiry_alert'
            ];
        }
    }
}