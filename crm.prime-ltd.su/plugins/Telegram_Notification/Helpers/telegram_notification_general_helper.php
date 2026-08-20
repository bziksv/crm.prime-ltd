<?php

use App\Controllers\App_Controller;

/* --------------------------------------------------------------------------
   SETTINGS
---------------------------------------------------------------------------*/
if (!function_exists('get_telegram_notification_setting')) {
    function get_telegram_notification_setting($key = "") {
        $config = new Telegram_Notification\Config\Telegram_Notification(); 
        $setting_value = get_array_value($config->app_settings_array, $key);
        
        if ($setting_value !== NULL) { 
            return $setting_value; 
        } else {
            return ""; 
        } 
    } 
}

/* --------------------------------------------------------------------------
   TASK ROLES AND CHECK NOTIFICATIONS
---------------------------------------------------------------------------*/
function get_task_roles_and_participants($ci, $task_id, $user_id)
{
    $result = [
        'Ваша роль' => 'Вы не учавствуете',
        'Создатель задачи' => 'Не указан',
        'Исполнители' => 'Нет',
        'Участники' => 'Нет',
    ];

    if (!$task_id) return $result;

    $task = $ci->Tasks_model->get_one($task_id);
    if (!$task) return $result;

    $db = \Config\Database::connect();

    // ------------------------------
    // Создатель задачи
    // ------------------------------
    $creator_name = '';
    $log = $db->table('activity_logs')
        ->where('log_type', 'task')
        ->where('log_type_id', $task_id)
        ->orderBy('id', 'ASC')
        ->get()
        ->getRow();

    if ($log) {
        $user = $ci->Users_model->get_one($log->created_by);
        if ($user && !$user->deleted) {
            $creator_name = make_profile_link($ci, $user);
        }
    }
    $result['Создатель задачи'] = $creator_name ?: 'Не указан';

    // ------------------------------
    // Исполнители
    // ------------------------------
    $executors_names = [];
    $executor_ids = [];
    if (!empty($task->executors)) {
        $executors = array_map('trim', explode(',', $task->executors));
        foreach ($executors as $uid) {
            $user = $ci->Users_model->get_one($uid);
            if ($user && !$user->deleted) {
                $executors_names[] = make_profile_link($ci, $user);
                $executor_ids[] = $uid;
            }
        }
    }
    $result['Исполнители'] = $executors_names ? implode(', ', $executors_names) : 'Нет';

    // ------------------------------
    // Участники
    // ------------------------------
    $participants_names = [];
    $participant_ids = [];
    if (!empty($task->collaborators)) {
        $collaborators = array_map('trim', explode(',', $task->collaborators));
        foreach ($collaborators as $uid) {
            $user = $ci->Users_model->get_one($uid);
            if ($user && !$user->deleted) {
                $participants_names[] = make_profile_link($ci, $user);
                $participant_ids[] = $uid;
            }
        }
    }
    $result['Участники'] = $participants_names ? implode(', ', $participants_names) : 'Нет';

    // ------------------------------
    // Ваша роль
    // ------------------------------
    $your_roles = [];
    $role_map = []; // для удобства проверки в настройках

    if ((int)$task->assigned_to === (int)$user_id) {
        $your_roles[] = 'Главный ответственный';
        $role_map['Главный ответственный'] = true;
    }
    if (in_array((string)$user_id, $executor_ids)) {
        $your_roles[] = 'Исполнитель';
        $role_map['Исполнитель'] = true;
    }
    if (in_array((string)$user_id, $participant_ids)) {
        $your_roles[] = 'Участник';
        $role_map['Участник'] = true;
    }

    if (empty($your_roles)) $your_roles[] = 'Вы не учавствуете';
    $result['Ваша роль'] = implode(', ', array_unique($your_roles));

    // ------------------------------
    // Проверка уведомлений по проекту
    // ------------------------------

    $send = [];
    foreach ($role_map as $role => $_) {
        $setting = $db->table('telegram_project_role_settings')
            ->where('user_id', $user_id)
            ->where('project_id', $task->project_id)
            ->where('role', $role)
            ->where('enabled', 1)
            ->get()
            ->getRow();

        if ($setting) {
            $send[] = true;
        }
    }

    if(in_array(true, $send)) {
        return $result;
    }
}

/* --------------------------------------------------------------------------
   CHANGES TEXT FUNCTIONS
---------------------------------------------------------------------------*/
function build_changes_text($serialized_changes, $statuses)
{
    if (empty($serialized_changes)) return '';

    $changes = @unserialize($serialized_changes);
    if (!is_array($changes)) return '';

    $field_names = [
        "start_date"  => "Дата начала",
        "deadline"    => "Дата окончания",
        "status"      => "Статус",
        "assigned_to" => "Ответственный",
        "status_id"   => "Изменение статуса задачи",
        'project_task_assigned' => "Изменён ответственный",
        'collaborators' => "Изменения в составе задачи"
    ];

    $ci = new App_Controller();
    $text = "<b>Изменения:</b>";

    foreach ($changes as $field => $values) {
        $from = $values['from'] ? $values['from'] : '';
        $to   = $values['to'] ? $values['to'] : '';

        if ($field === 'status_id') {
            $to = $statuses[(int)$to] ? $statuses[(int)$to] : $to;

            $text .= "\n• <b>Новый статус задачи</b>: " . $to;
        }

        if($field === 'collaborators') {
            $fromNames = '';
            $toNames = '';

            $from_ids = array_filter(explode(',', $from));
            $to_ids   = array_filter(explode(',', $to));

            $removed_ids = array_diff($from_ids, $to_ids);
            $added_ids   = array_diff($to_ids, $from_ids);

            if (!empty($removed_ids)) {
                $names = [];
                foreach ($removed_ids as $id) {
                    $user = $ci->Users_model->get_one($id);
                    $names[] = $user->first_name . ' ' . $user->last_name;
                }
                $text .= "\n• <b>Участник исключён: </b>" . implode(', ', $names);
            }

            if (!empty($added_ids)) {
                $names = [];
                foreach ($added_ids as $id) {
                    $user = $ci->Users_model->get_one($id);
                    $names[] = $user->first_name . ' ' . $user->last_name;
                }
                $text .= "\n• <b>Участник добавлен: </b>" . implode(', ', $names);
            }
        }

        if($field === 'executors') {
            $fromNames = '';
            $toNames = '';

            $from_ids = array_filter(explode(',', $from));
            $to_ids   = array_filter(explode(',', $to));

            $removed_ids = array_diff($from_ids, $to_ids);
            $added_ids   = array_diff($to_ids, $from_ids);

            if (!empty($removed_ids)) {
                $names = [];
                foreach ($removed_ids as $id) {
                    $user = $ci->Users_model->get_one($id);
                    $names[] = $user->first_name . ' ' . $user->last_name;
                }
                $text .= "\n• <b>Исполнитель исключён: </b>" . implode(', ', $names);
            }

            if (!empty($added_ids)) {
                $names = [];
                foreach ($added_ids as $id) {
                    $user = $ci->Users_model->get_one($id);
                    $names[] = $user->first_name . ' ' . $user->last_name;
                }
                $text .= "\n• <b>Исполнитель добавлен :</b> " . implode(', ', $names);
            }
        }

        if ($field == 'start_date' || $field == 'deadline') {
            $fromTime = $from ? strtotime($from) : null;
            $toTime   = $to ? strtotime($to) : null;
            if ($fromTime === $toTime) {
                continue;
            }

            if ($fromTime) {
                $from = date('Y-m-d H:i', $fromTime);
            }

            if ($toTime) {
                $to = date('Y-m-d H:i', $toTime);
            }

            if('start_date') {
                $info = 'Дата начала изменена';
            } else {
                $info = 'Дата окончания изменена';
            }

            $text .= "\n• <b>{$info}</b>: {$from} → {$to}";
        }
    }

    return $text;
}

function build_telegram_message($message, $url, $roles, $description, $changes)
{
    $message = trim($message);
    $message = $url ? "<a href='{$url}'>{$message}</a>" : $message;
    $text = "$message\n$description\n";
    $text .= "\n";

    foreach($roles as $key => $role) {
        $text .= "<b>$key</b>" . ': ' . $role . "\n";
    }

    $text .= "\n";

    if ($changes) {
        $text .= $changes;
    }      
    
    return formatTelegramText($text);
}

function formatTelegramText($text)
{
    $text = preg_replace('/<\s*br[^>]*>/i', "\n", $text);
    $text = preg_replace('/<\s*\/p\s*>/i', "\n\n", $text);
    $text = preg_replace('/<\s*p[^>]*>/i', "", $text);
    $text = preg_replace('/<\s*\/?span[^>]*>/i', "", $text);
    $text = strip_tags($text, '<b><strong><i><em><u><a><code><pre>');
    $text = preg_replace("/\n{3,}/", "\n\n", $text);

    return trim($text);
}


/* --------------------------------------------------------------------------
   GET LINK TO PROFILE
---------------------------------------------------------------------------*/
function make_profile_link($ci, $user, $type = 'user') {
    $name = trim($user->first_name . ' ' . $user->last_name);
    if($type == 'user') {
        $url = base_url("index.php/team_members/view/" . $user->id);
    } else {
        $url = base_url("index.php/clients/contact_profile/" . $user->id);
    }

    return '<a href="' . $url . '" target="_blank">' . htmlspecialchars($name) . '</a>';
}

/* --------------------------------------------------------------------------
   TELEGRAM SENDER
---------------------------------------------------------------------------*/
function telegram_send($bot_token, $chat_id, $text)
{
    // Обрезаем до 1500 символов (UTF-8)
    if (mb_strlen($text, 'UTF-8') > 1500) {
        $text = mb_substr($text, 0, 1500, 'UTF-8') . '...';
    }

    $data = [
        "chat_id" => $chat_id,
        "parse_mode" => "HTML",
        "text" => $text,
        "disable_web_page_preview" => true
    ];

    $ch = curl_init("https://api.telegram.org/bot{$bot_token}/sendMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    curl_close($ch);

    if (!$result) return false;

    $decoded = json_decode($result);
    return isset($decoded->ok) && $decoded->ok;
}

/* --------------------------------------------------------------------------
   HELPERS
---------------------------------------------------------------------------*/
$current_telegram_event = null;

function detect_event_from_changes($serialized_changes)
{
    if($serialized_changes == 'project_task_updated') {
        return ['notify_task_status_changed'];
    }

    if($serialized_changes == 'project_task_assigned' || $serialized_changes == 'project_task_created') {
        return ['notify_task_assignees_changed'];
    }

    if($serialized_changes == 'project_task_commented') {
        return ['notify_task_comment_added'];
    }

    $changes = @unserialize($serialized_changes);
    if (!is_array($changes)) return '';

    $field_names = [
        "start_date"  => "notify_task_date_changed",
        "deadline"    => "notify_task_date_changed",
        "status_id"   => "notify_task_status_changed",
        'project_task_assigned' => "notify_task_assignees_changed",
        'executors' => 'notify_task_assignees_changed',
        'project_task_commented' => 'notify_task_comment_added',
    ];

    if(array_key_exists('collaborators', $changes)) {
        return ['notify_task_assignees_changed'];
    }

    $events = [];
    foreach ($changes as $field => $values) {
        if(isset($field_names[$field])) {
            $events[] = $field_names[$field];
        }
    }

    return $events;
}

function write_log($data)
{
    $log_file = '/var/www/crm_prime_lt_usr/data/www/crm.prime-ltd.su/mylog.txt';

    $date = date('Y-m-d H:i:s');

    if (is_array($data) || is_object($data)) {
        $data = print_r($data, true);
    }

    $message = "[$date] " . $data . PHP_EOL;

    file_put_contents($log_file, $message, FILE_APPEND);
}

function getTicketArticle($key) {
    $array = [
        'ticket_commented_note' => 'Новое примечание у заявки',
        'ticket_commented' => 'Новый комментарий у заявки',
        'ticket_created' => 'Создана новая заявка',
        'ticket_closed' => 'Заявка закрыта',
        'ticket_reopened' => 'Заявка снова открыта',
        'ticket_assigned' => 'У заявки назначен новый исполнитель'
    ];

    return $array[$key] ?? 'Не опознанный тип';
}

function getEventSettingId($ci, $key) {
    $array = [
        'ticket_created' => 28,
        'ticket_commented' => 29,
        'ticket_commented_note' => 29,
        'ticket_closed' => 30,
        'ticket_reopened' => 31,
        'ticket_assigned' => 59
    ];

    $id = $array[$key];

    return explode(',', $ci->Notification_settings_model->get_one($id)->notify_to_team_members);
}

/* --------------------------------------------------------------------------
   MAIN FUNCTION
---------------------------------------------------------------------------*/
if (!function_exists('send_telegram_notification')) {
    function send_telegram_notification($event, $task_id = 0, $notification_id = 0)
    {
        $statuses = [
            1 => 'Планирование',
            2 => 'В работе',
            3 => 'Выполнено',
            4 => 'На уточнении у клиента',
            5 => 'Отложенная',
            6 => 'На проверку постановщику',
        ];

        try {
            $ci = new App_Controller();

            if($notification_id != 0) {
                $notification_info = $ci->Notifications_model->get_email_notification($notification_id);
            } else {
                $action = explode('|', $event, 2);

                $notification_info = new stdClass();
                $notification_info->event = $action[0];
                $data = json_decode($action[1]);
                $notification_info->ticket_id = $data->ticket_id;
                $notification_info->ticket_comment_id = $data->ticket_comment_id;
                $notification_info->ticket_comment_description = $data->ticket_comment_description;
            }
            
            // заявки (тикеты)
            if(in_array($notification_info->event, ['ticket_commented', 'ticket_commented_note', 'ticket_commented', 'ticket_created', 'ticket_closed', 'ticket_reopened', 'ticket_assigned'])) {
                $ticket = $ci->Tickets_model->get_one($notification_info->ticket_id);
                $project = $ci->Projects_model->get_one($ticket->project_id);
                $event = getTicketArticle($notification_info->event);
                $notifyTo = getEventSettingId($ci, $notification_info->event);

                $command = [];
                foreach($notifyTo as $uid) {
                    $user = $ci->Users_model->get_one($uid);
                    $command[] = make_profile_link($ci, $user);
                }
                
                if(!empty($ticket->assigned_to)) {
                    $notifyTo[] = $ticket->assigned_to;
                    $notifyTo = array_unique($notifyTo);
                }

                $mainUser = make_profile_link($ci, $ci->Users_model->get_one($ticket->assigned_to));

                $ticketUrl = base_url("index.php/tickets/view/" . $notification_info->ticket_id);

                $telegram_message = [
                    "<a href='" . $ticketUrl . "'>$event</a>",
                    '',
                    '<b>Заявка #</b>' . $notification_info->ticket_id . ' ' . $ticket->title,
                    '<b>Проект:</b> ' . $project->title,
                    '<b>Ответственный:</b> ' . $mainUser,
                    '',
                    '<b>Команда:</b>',
                    implode("\n", $command),
                ];

                // 'ticket_created'
                // 'ticket_closed'
                // 'ticket_reopened'
                // 'ticket_commented'
                // 'ticket_assigned'
                if(in_array($notification_info->event, ['ticket_commented', 'ticket_commented_note'])) {
                    $comment = $ci->Ticket_comments_model->get_one($notification_info->ticket_comment_id);
                    $commentedUser = $ci->Users_model->get_one($comment->created_by);

                    $files = @unserialize($comment->files);
                    if (count($files) > 0) {
                        $telegram_message[] = '';
                        $telegram_message[] = '🖼 <b>В комментарии есть вложения</b>';
                    }

                    //обрезаем информацию, если пришёл ответ с Email
                    if (str_contains($notification_info->ticket_comment_description, '--')) {
                        $type = "\n<b>Комментарий от клиента</b>";
                        $newComment = explode('--', $notification_info->ticket_comment_description)[0];
                        $profile = make_profile_link($ci, $commentedUser, 'client');
                    } else {
                        $type = "\n<b>Комментарий от сотрудника</b>";
                        $profile = make_profile_link($ci, $commentedUser, 'user');
                    }

                    $whoComented = "$type $profile:";
                    $newComment = explode('--', $notification_info->ticket_comment_description)[0];
                    $newComment = formatTelegramText($newComment);

                    $telegram_message[] = $whoComented;
                    $telegram_message[] = $newComment;
                }

                $message = implode("\n", $telegram_message);

                foreach($notifyTo as $uid) {
                    $user = $ci->Users_model->get_one($uid);

                    if(isset($user->telegram_chat_id)) {
                        try {
                            telegram_send(get_telegram_notification_setting("bot_token"), $user->telegram_chat_id, $message);
                        } catch (\Exception $ex) {
                            write_log($ex->getMessage());
                        }
                    }

                    if(isset($user->prime_webhook_url)) {
                        try {
                            $response = sendPrimeNotification($user->prime_webhook_url, $message);
                        } catch (\Exception $ex) {
                            write_log($ex->getMessage());
                        }
                    }
                }

                return;
            }

            $UserNotificationSettingsModel = new \App\Models\User_notification_settings_model();
            $message = str_replace(["%s", '.'], '', app_lang("notification_" . $event));

            if($message == 'Задача поручена ') {
                $message = 'Изменён главный ответственный';
            }

            $notification_description = "";
            $url = "";
            $changes_text = "";

            $task = $ci->Tasks_model->get_one($notification_info->task_id ?? 0);
            $actionTypes = detect_event_from_changes(
                $notification_info->activity_log_changes ?? 
                $notification_info->event ??
                $event
            );

            $filesAttachment = false;
            $comment_id = $notification_info->project_comment_id;
            if ($comment_id) {
                $comment = $ci->Project_comments_model->get_one($comment_id);

                if ($comment && $comment->created_by) {
                    $comment_user_id = $comment->created_by;
                    $filesAttachment = $comment->files != 'a:0:{}';
                }
            }

            if(isset($notification_info->activity_log_changes)) {
                $changes_text = build_changes_text($notification_info->activity_log_changes, $statuses);
            } else if($message == 'Задача обновлена') {
                $changes_text = '<b>Новый статус задачи</b>: ' . $statuses[$task->status_id];
            }

            $url_attributes_array = get_notification_url_attributes($notification_info);
            $url = get_array_value($url_attributes_array, "url");

            $notification_description = view(
                "Telegram_Notification\Views\\notifications\\notification_description_for_telegram",
                ["notification" => $notification_info, 'file' => $filesAttachment]
            );

            $db = \Config\Database::connect();

            // ------------------------------
            // Собираем всех участников проекта
            // ------------------------------
            $recipients = [];

            $creator_id = $task->assigned_to ?? null;
            if ($creator_id) {
                $user = $ci->Users_model->get_one($creator_id);
                if ($user && !$user->deleted) $recipients['Ответственный'][] = $user;
            }

            if (!empty($task->executors)) {
                foreach (array_map('trim', explode(',', $task->executors)) as $uid) {
                    $user = $ci->Users_model->get_one($uid);
                    if ($user && !$user->deleted) $recipients['Исполнитель'][] = $user;
                }
            }

            if (!empty($task->collaborators)) {
                foreach (array_map('trim', explode(',', $task->collaborators)) as $uid) {
                    $user = $ci->Users_model->get_one($uid);
                    if ($user && !$user->deleted) $recipients['Участник'][] = $user;
                }
            }

            // ------------------------------
            // Формируем список всех участников задачи
            // ------------------------------
            $all_participants = [];
            foreach ($recipients as $role_name => $users) {
                $names = [];

                foreach ($users as $u) {
                    $names[] = make_profile_link($ci, $u);
                }

                if (!empty($names)) {
                    $all_participants[$role_name] = implode(', ', $names);
                }
            }

            $notificationText = build_telegram_message(
                $message,
                $url,
                $all_participants,
                $notification_description ? formatTelegramText($notification_description) : '',
                $changes_text ?? ''
            );

            $sended = [];
            foreach ($recipients as $role => $users) {
                if($role == 'Ответственный') {
                    $role = 'Создатель задачи';
                }

                foreach ($users as $user) {
                    if (in_array($user->id, $sended)) {
                        continue;
                    } else {
                        $sended[] = $user->id;
                    }

                    $user_notification_setting = $UserNotificationSettingsModel->get_by_user($user->id);

                    if(
                        isset($comment_user_id) && 
                        // $comment_user_id == $user->id && 
                        $user_notification_setting->notify_task_my_comment_added == 0
                    ) {
                        continue;
                    }

                    $is_enabled = [];
                    write_log($actionTypes);
                    foreach ($actionTypes as $actionType) {
                        $is_enabled[] = isset($user_notification_setting->{$actionType}) ? $user_notification_setting->{$actionType} : 0;
                    }

                    if (!in_array(true, $is_enabled)) {
                        continue;
                    }

                    $setting = $db->table('telegram_project_role_settings')
                        ->where('user_id', $user->id)
                        ->where('project_id', $task->project_id)
                        ->where('role', $role)
                        ->where('enabled', 1)
                        ->get()
                        ->getRow();

                    if ($setting) {
        
                        if(isset($user->telegram_chat_id)) {
                            telegram_send(get_telegram_notification_setting("bot_token"), $user->telegram_chat_id, $notificationText);
                        }

                        if(isset($user->prime_webhook_url)) {
                            try {
                                $response = sendPrimeNotification($user->prime_webhook_url, $notificationText);
                            } catch (\Exception $ex) {
                                write_log($ex->getMessage());
                            }
                        }
                    }

                }
            }
        } catch (\Exception $ex) {
            write_log("Telegram notification error: " . $ex->getMessage());
            write_log("Telegram notification line: " . $ex->getLine());
            write_log("Telegram notification file: " . $ex->getFile());
        }

        return true;
    }

    function sendPrimeNotification($url, $message) {
        $message = formatMessage($message);
        $payload = json_encode([
            'text' => $message,
        ], JSON_UNESCAPED_UNICODE);

        $postFields = "payload=" . $payload;

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return "cURL error: " . $error;
        }

        curl_close($ch);

        return $response;
    }

    function formatMessage($text) {
        // <b> → *
        $text = preg_replace('/<b>([\s\S]*?)<\/b>/i', '*$1*', $text);
        // <a> → <url|text>
        $text = preg_replace_callback(
            '/<a\s+href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)<\/a>/si',
            function ($matches) {
                return "<{$matches[1]}|".trim($matches[2]).">";
            },
            $text
        );

        $text = str_replace('*:', '* :', $text);

        return $text;
    }
}