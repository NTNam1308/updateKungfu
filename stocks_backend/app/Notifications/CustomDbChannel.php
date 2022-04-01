<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CustomDbChannel 
{

  public function send($notifiable, Notification $notification)
  {
    $arrData = $notification->toArray($notifiable);
    $database = $notification->toDatabase($notifiable);

    return $notifiable->routeNotificationFor('database')->create([
        'id' => $notification->id,
        'type' => get_class($notification),
        'data' => $arrData,
        'is_new' => 0,
        'type_notify' => $database['type_notify'],
        'group' => $database['group'],
        'created_at' => $database['created_at'],
        'read_at' => null,
    ]);
  }

}