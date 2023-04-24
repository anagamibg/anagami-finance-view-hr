<?php

namespace App\Notifications;

use App\Models\EmployeeDocumentExpiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeDocumentExpiryAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $document;
    protected $alertType;

    /**
     * Create a new notification instance.
     */
    public function __construct(EmployeeDocumentExpiry $document, string $alertType = 'expiring_soon')
    {
        $this->document = $document;
        $this->alertType = $alertType;
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

        $name = $this->document->document_name;
        $number = $this->document->document_number ?: __('ui-shared.expiry.not_available');
        $expiryDate = $this->document->expiry_date->format('d.m.Y');
        $days = (int) round($this->document->days_until_expiry);

        if ($this->alertType === 'expiring_soon') {
            $message->subject(__('ui-shared.expiry.doc_self_subject', ['name' => $name]))
                ->greeting(__('ui-shared.expiry.greeting', ['name' => $notifiable->name]))
                ->line(__('ui-shared.expiry.doc_self_intro', ['name' => $name]))
                ->line(__('ui-shared.expiry.doc_details'))
                ->line(__('ui-shared.expiry.doc_name', ['name' => $name]))
                ->line(__('ui-shared.expiry.doc_number', ['number' => $number]))
                ->line(__('ui-shared.expiry.expiry_date', ['date' => $expiryDate]))
                ->line(__('ui-shared.expiry.days_left', ['days' => $days]))
                ->line(__('ui-shared.expiry.doc_self_renew'));
                // ->action('View Document', route('employee-document-expiry.download', md5($this->document->id)))
                // ->line('Thank you for using our application!');
        } else {
            $message->subject(__('ui-shared.expiry.doc_hr_subject'))
                ->greeting(__('ui-shared.expiry.greeting', ['name' => $notifiable->name]))
                ->line(__('ui-shared.expiry.doc_hr_intro'))
                ->line(__('ui-shared.expiry.doc_details'))
                ->line(__('ui-shared.expiry.employee', ['name' => $this->document->user->name]))
                ->line(__('ui-shared.expiry.doc_name', ['name' => $name]))
                ->line(__('ui-shared.expiry.doc_number', ['number' => $number]))
                ->line(__('ui-shared.expiry.expiry_date', ['date' => $expiryDate]))
                ->line(__('ui-shared.expiry.days_left', ['days' => $days]))
                ->line(__('ui-shared.expiry.doc_hr_follow'));
                // ->action('View Document', route('employee-document-expiry.download', md5($this->document->id)))
                // ->line('Thank you for using our application!');
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
        if ($this->alertType === 'expiring_soon') {
            return [
                'title' => __('ui-shared.expiry.doc_self_title'),
                'message' => __('ui-shared.expiry.doc_self_message', ['name' => $this->document->document_name, 'days' => (int) round($this->document->days_until_expiry)]),
                'document_id' => $this->document->id,
                'document_name' => $this->document->document_name,
                'expiry_date' => $this->document->expiry_date->format('d M Y'),
                'days_until_expiry' => $this->document->days_until_expiry,
                'type' => 'document_expiry_alert'
            ];
        } else {
            return [
                'title' => __('ui-shared.expiry.doc_hr_title'),
                'message' => __('ui-shared.expiry.doc_hr_message', ['employee' => $this->document->user->name, 'name' => $this->document->document_name, 'days' => (int) round($this->document->days_until_expiry)]),
                'document_id' => $this->document->id,
                'employee_name' => $this->document->user->name,
                'document_name' => $this->document->document_name,
                'expiry_date' => $this->document->expiry_date->format('d M Y'),
                'days_until_expiry' => $this->document->days_until_expiry,
                'type' => 'hr_document_expiry_alert'
            ];
        }
    }
}
