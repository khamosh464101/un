<?php

namespace Modules\Projects\Notifications\Ticket;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use Modules\Projects\Models\Activity;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\Notification as FCMNNotification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;

use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use Ramsey\Uuid\Uuid;

class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $uuid;
    public $ticketId;
    public $title;
    public $activity;
    public $user;
    public $action;
    


    /**
     * Create a new notification instance.
     */
    public function __construct( $tmpTicket, $user, $action)
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->ticketId = $tmpTicket['id'];
        $this->activity = Activity::find($tmpTicket['activity_id']);
        $this->title = $tmpTicket['title'];
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
        $url = $frontendUrl . '/project-management/tasks/' . $this->ticketId;

        return (new MailMessage)
        ->subject("[{$this->activity->project->title}] {$this->user->name} has {$this->action} {$this->title} task")
        ->greeting("{$this->user->name} {$this->action} {$this->title} task")
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
        $url = $frontendUrl . '/project-management/tasks/' . $this->ticketId;
        return [
            'ticket_id' => $this->ticketId,
            'title' => "{$this->user->name} has {$this->action} \"{$this->title}\" task",
            'subtitle' => "On \"{$this->activity->project->title}\" project",
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
            $url = $frontendUrl . '/project-management/tasks/' . $this->ticketId;
        return (new FcmMessage(notification: new FcmNotification(
            title : "Task Log Time",
            body: "{$this->user->name} has {$this->action} \"{$this->title}\" task",
            image : 'https://www.gstatic.com/mobilesdk/240501_mobilesdk/firebase_28dp.png', 
            )))

            ->data([
                'link' => $url,
                'navigation' => json_encode([
                    'root' => 'Tasks',
                    'screen' => 'TaskDetails',
                    'params' => [
                        'id' => $this->ticketId,
                        'tab' => 'details',
                    ],
                ]),
                'icon' => $this->user->photo,
                'uuid' => $this->uuid,
                'test' => '12345678900'
            ])
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
