<?php

namespace Modules\Projects\Notifications;

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
use App\Models\Setting;

class HourNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $uuid;
    public $hourId;
    public $title;
    public $ticket;
    public $user;
    public $action;
    


    /**
     * Create a new notification instance.
     */
    public function __construct( $tmpHour, $user, $action)
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->hourId = $tmpHour['id'];
        $this->ticket = Ticket::find($tmpHour['ticket_id']);
        $this->title = $tmpHour['title'];
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
        $url = $frontendUrl . '/project-management/tickets/' . $this->ticket->id;

        return (new MailMessage)
        ->subject("[{$this->ticket->activity->project->title}] {$this->user->name} has {$this->action} a Log Time on {$this->ticket->title} ticket")
        ->greeting("{$this->user->name} {$this->action} a Log Time on {$this->ticket->title} ticket")
        ->line($this->title)
        ->action('Go to Ticket', $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $frontendUrl = config('frontend.url');
        $url = $frontendUrl . '/project-management/tickets/' . $this->ticket->id;
        return [
            'ticket_id' => $this->ticket->id,
            'title' => "{$this->user->name} has {$this->action} a Log Time on \"{$this->ticket->title}\" ticket",
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
            $url = $frontendUrl . '/project-management/tickets/' . $this->ticket->id;
        return (new FcmMessage(notification: new FcmNotification(
            title : "Ticket Log Time",
            body: "{$this->user->name} has {$this->action} a Log Time on \"{$this->ticket->title}\" ticket",
            image : 'https://www.gstatic.com/mobilesdk/240501_mobilesdk/firebase_28dp.png', 
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
