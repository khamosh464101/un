<?php

namespace Modules\Projects\Notifications\Ticket;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Projects\Models\Ticket;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\Notification as FCMNNotification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;

use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use Ramsey\Uuid\Uuid;

class AttachmentNotification extends Notification
{
    use Queueable;
    public $uuid;
    public $attachementId;
    public $title;
    public $ticket;
    public $user;
    public $action;
    


    /**
     * Create a new notification instance.
     */
    public function __construct( $tmpAttachment, $user, $action)
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->attachementId = $tmpAttachment['id'];
        $this->ticket = Ticket::find($tmpAttachment['ticket_id']);
        $this->title = $tmpAttachment['title'];
        $this->user = $user;
        $this->action = $action;

    }

    public function getKey()
    {
        return $this->uuid;  // Use UUID as the key
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', FcmChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {   
        $frontendUrl = config('frontend.url');
        $url = $frontendUrl . '/project-management/tasks/' . $this->ticket->id;

        return (new MailMessage)
        ->subject("[{$this->ticket->activity->project->title}] {$this->user->name} has {$this->action} a Attachement on {$this->ticket->title} task")
        ->greeting("{$this->user->name} {$this->action} a Attachement on {$this->ticket->title} task")
        ->line($this->title)
        ->action('Go to Task', $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $frontendUrl = config('frontend.url');
        $url = $frontendUrl . '/project-management/tasks/' . $this->ticket->id;
        return [
            'ticket_id' => $this->ticket->id,
            'title' => "{$this->user->name} has {$this->action} a Attachement on \"{$this->ticket->title}\" task",
            'subtitle' => "On \"{$this->ticket->activity->project->title}\" project",
            'link' => $url,
            'causer_id' => $this->user->id,
            'causer_photo' => $this->user->photo,
            'causer_name' => $this->user->name,
            'uuid' => $this->uuid,
            'created_at' => $notifiable->created_at,
            'read_at' => $notifiable->read_at,
        ];
    }



        public function toFcm($notifiable): FcmMessage
    {
        $frontendUrl = config('frontend.url');
            $url = $frontendUrl . '/project-management/tasks/' . $this->ticket->id;
            $imageUrl = asset('logos/habitat.png');
        return (new FcmMessage(notification: new FcmNotification(
            title : "Task Attachement",
            body: "{$this->user->name} has {$this->action} a Attachement on \"{$this->ticket->title}\" task",
            image : $imageUrl, 
            )))
            ->data(['link' => $url,
            'icon' => $this->user->photo, 'uuid' => $this->uuid, 'test' => '123456789'])
            ->custom([
                'android' => [
                    'notification' => [
                        'color' => '#0A0A0A',
                        'sound' => 'default',
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default'
                        ],
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ],
            ]);
    }
}
