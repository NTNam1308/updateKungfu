<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Notifications\CustomDbChannel;

class CreateNotification extends Notification
{
    private $result;
    private $group;
    private $created_at;
    private $type_notify;

    public function __construct( $result = null, $group = null, $created_at = null, $type_notify = null )
    {
        $this->result = $result;
        $this->group = $group;
        $this->created_at = $created_at;
        $this->type_notify = $type_notify;
    }

    public function via($notifiable)
    {
        return [CustomDbChannel::class];
    }

    public function toDatabase($notifiable)
    {
        return [
          'group' => $this->group,
          'created_at' => $this->created_at,
          'type_notify' => $this->type_notify 
        ];
    }

    public function toArray($notifiable)
    {
        return $this->result ;
    }
}