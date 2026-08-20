<?php

namespace App\Libraries;

class NotificationGrouper
{
    private array $notifications = [];

    public function __construct(array $notifications)
    {
        $this->notifications = $notifications;
    }

    public function get_grouped_unread_by_task(): array
    {
        $notifications = [];

        foreach ($this->notifications as $notification) {
            $this->initialize_notification_ids_in_group($notification);

            $index = $this->create_new_index($notification);

            if (array_key_exists($index, $notifications)) {
                $this->add_notification_id_in_group($notifications[$index], $notification->id);
            } else {
                $notifications[$index] = $notification;
            }
        }

        return $notifications;
    }

    private function create_new_index(object $notification): int
    {
        $index = $notification->id;

        if ($this->is_read($notification)) {
            return $index;
        }

        if ($this->is_task($notification)) {
            $index = $notification->task_id;
        } elseif ($this->is_ticket($notification)) {
            $index = $notification->ticket_id;
        }

        return $index;
    }

    private function is_task(object $notification): bool
    {
        $task_id = intval($notification->task_id);

        return $task_id > 0;
    }

    private function is_ticket(object $notification): bool
    {
        $ticket_id = intval($notification->ticket_id);

        return $ticket_id > 0;
    }

    private function initialize_notification_ids_in_group(object $notification): void
    {
        $notification->notification_ids_in_group = [];
    }

    private function add_notification_id_in_group(object $notification, int $id): void
    {
        $notification->notification_ids_in_group[] = $id;
    }

    private function is_read($notification): bool
    {
        return $notification->is_read;
    }
}
