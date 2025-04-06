<?php

namespace Modules\Projects\Notifications\Comments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Projects\Models\TicketComment;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\Notification as FCMNNotification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;

use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class CommentNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $comment;
    public $user;
    public $action;
    


    /**
     * Create a new notification instance.
     */
    public function __construct(TicketComment $comment, $user, $action)
    {
        $this->comment = $comment;
        $this->user = $user;
        $this->action = $action;
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
        $url = $frontendUrl . '/project-management/tickets/' . $this->comment->ticket_id;

        return (new MailMessage)
        ->subject("[{$this->comment->ticket->activity->project->title}] {$this->user->name} has {$this->action} a comment on {$this->comment->ticket->title} ticket")
        ->greeting("{$this->user->name} {$this->action} a comment on {$this->comment->ticket->title} ticket")
        ->line($this->comment->content)
        ->action('Go to Ticket', $url);
        // ->action('Open task', route('projects.tasks.open', ['project' => $this->comment->task->project_id, 'task' => $this->comment->task->id]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $frontendUrl = config('frontend.url');
        $url = $frontendUrl . '/project-management/tickets/' . $this->comment->ticket_id;
        return [
            'ticket_id' => $this->comment->ticket->id,
            'title' => "{$this->user->name} has {$this->action} a comment on \"{$this->comment->ticket->title}\" ticket",
            'subtitle' => "On \"{$this->comment->ticket->activity->project->title}\" project",
            'link' => $url,
            'causer_id' => $this->user->id,
            'causer_photo' => $this->user->photo,
            'created_at' => $notifiable->created_at,
            'read_at' => $notifiable->read_at,
        ];
    }



        // Send via the Firebase Cloud Messaging (FCM) channel
        public function toFirebase($notifiable)
        {
            $frontendUrl = config('frontend.url');
            $url = $frontendUrl . '/project-management/tickets/' . $this->comment->ticket_id;
            if ($this->user->device_token) {
                $notification = Notification::fromArray([
                    'title' => "Ticket Comment",
                    'body' => "{$this->user->name} has {$this->action} a comment on \"{$this->comment->ticket->title}\" ticket",
                    'image' => 'https://www.gstatic.com/mobilesdk/240501_mobilesdk/firebase_28dp.png', 
                ]);

                $message = CloudMessage::new()
                ->withNotification($notification) // optional
                ->withData(['link' => $url,
                'icon' => $this->user->photo]) // optional
                ->toToken($this->user->device_token);
                $config = WebPushConfig::fromArray([
                    'headers' => [
                        'TTL' => '3600',
                    ]
                ]);
                $message = $message->withWebPushConfig($config);
                $result = $this->messaging->send($message);
                return $result;
            }
            return [];
        
        }

        public function toFcm($notifiable): FcmMessage
    {
        $frontendUrl = config('frontend.url');
            $url = $frontendUrl . '/project-management/tickets/' . $this->comment->ticket_id;
        return (new FcmMessage(notification: new FcmNotification(
            title : "Ticket Comment",
            body: "{$this->user->name} has {$this->action} a comment on \"{$this->comment->ticket->title}\" ticket",
            image : 'https://www.gstatic.com/mobilesdk/240501_mobilesdk/firebase_28dp.png', 
            )))
            ->data(['link' => $url,
            'icon' => $this->user->photo])
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
