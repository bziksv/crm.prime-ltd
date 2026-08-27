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
            $index = $this->create_new_index($notification);

            if (array_key_exists($index, $notifications)) {
                $existing = $notifications[$index];

                if ($this->is_newer($notification, $existing)) {
                    $group_ids = $existing->notification_ids_in_group ?? [];
                    $group_ids[] = (int) $existing->id;
                    $notification->notification_ids_in_group = $group_ids;
                    $notifications[$index] = $notification;
                } else {
                    $this->add_notification_id_in_group($existing, (int) $notification->id);
                }
            } else {
                $notification->notification_ids_in_group = [];
                $notifications[$index] = $notification;
            }
        }

        return $notifications;
    }

    private function create_new_index(object $notification): int
    {
        $index = (int) $notification->id;

        if ($this->is_read($notification)) {
            return $index;
        }

        if ($this->is_task($notification)) {
            $index = (int) $notification->task_id;
        } elseif ($this->is_ticket($notification)) {
            $index = (int) $notification->ticket_id;
        }

        return $index;
    }

    private function is_task(object $notification): bool
    {
        return intval($notification->task_id) > 0;
    }

    private function is_ticket(object $notification): bool
    {
        return intval($notification->ticket_id) > 0;
    }

    private function add_notification_id_in_group(object $notification, int $id): void
    {
        if (!isset($notification->notification_ids_in_group) || !is_array($notification->notification_ids_in_group)) {
            $notification->notification_ids_in_group = [];
        }

        $notification->notification_ids_in_group[] = $id;
    }

    private function is_read($notification): bool
    {
        return (int) $notification->is_read === 1;
    }

    private function is_newer(object $candidate, object $current): bool
    {
        $candidateTs = strtotime($candidate->created_at ?? '');
        $currentTs = strtotime($current->created_at ?? '');

        if ($candidateTs && $currentTs && $candidateTs !== $currentTs) {
            return $candidateTs > $currentTs;
        }

        return (int) $candidate->id > (int) $current->id;
    }
}
