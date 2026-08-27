<?php

namespace App\Controllers;

class Chat extends Security_Controller {

    /** @var \App\Models\Chat_model */
    public $Chat_model;

    function __construct() {
        parent::__construct();
        $this->Chat_model = new \App\Models\Chat_model();
        $this->init_permission_checker("message_permission");
    }

    private function deny_json($message = null) {
        echo json_encode(array(
            "success" => false,
            "message" => $message ?: app_lang('error_occurred'),
        ));
        exit();
    }

    private function ensure_chat_access() {
        if (!$this->login_user || !$this->login_user->id) {
            $this->deny_json("Не авторизован");
        }
        if ($this->login_user->user_type !== 'staff') {
            $this->deny_json("Доступ только для сотрудников");
        }
        if (!$this->check_access_on_messages_for_this_user()) {
            $this->deny_json(app_lang('error_occurred'));
        }
    }

    private function ensure_member($conversation_id) {
        if ($this->Chat_model->user_is_member($conversation_id, $this->login_user->id)) {
            return;
        }

        // Deep-link / refresh after "delete chat": restore own soft-deleted membership.
        if ($this->Chat_model->restore_membership($conversation_id, $this->login_user->id)
            && $this->Chat_model->user_is_member($conversation_id, $this->login_user->id)) {
            return;
        }

        $this->deny_json(app_lang('error_occurred'));
    }

    function panel() {
        $this->ensure_chat_access();

        $auto_cleanup_days = (int) get_setting("user_" . $this->login_user->id . "_chat_auto_cleanup_days");
        if (in_array($auto_cleanup_days, array(30, 60, 90, 180, 365), true)) {
            $this->Chat_model->auto_cleanup_own_messages($this->login_user->id, $auto_cleanup_days);
        }

        $view_data['conversations'] = $this->Chat_model->get_inbox($this->login_user->id);
        $view_data['staff_users'] = $this->Chat_model->get_staff_users($this->login_user->id);
        $view_data['login_user'] = $this->login_user;

        return $this->template->view('chat/panel', $view_data);
    }

    function conversation() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
        ));

        $auto_cleanup_days = (int) get_setting("user_" . $this->login_user->id . "_chat_auto_cleanup_days");
        if (in_array($auto_cleanup_days, array(30, 60, 90, 180, 365), true)) {
            $this->Chat_model->auto_cleanup_own_messages($this->login_user->id, $auto_cleanup_days);
        }

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $this->ensure_member($conversation_id);

        $conversation = $this->Chat_model->get_conversation($conversation_id, $this->login_user->id);
        if (!$conversation) {
            $this->deny_json(app_lang('error_occurred'));
        }

        $messages = $this->Chat_model->get_messages($conversation_id);
        $last_read_message_id = (int) ($conversation->last_read_message_id ?? 0);
        $this->Chat_model->mark_read($conversation_id, $this->login_user->id);

        $view_data['conversation'] = $conversation;
        $view_data['messages'] = $messages;
        $view_data['last_read_message_id'] = $last_read_message_id;
        $view_data['pinned_messages'] = $this->Chat_model->get_pinned_messages($conversation_id, 10);
        $view_data['members'] = $this->Chat_model->get_members($conversation_id);
        $view_data['login_user'] = $this->login_user;
        $view_data['layout'] = $this->request->getPost('layout') === 'page' ? 'page' : 'widget';

        return $this->template->view('chat/conversation', $view_data);
    }

    function start_dm() {
        try {
            $this->ensure_chat_access();

            $other_id = (int) $this->request->getPost('user_id');
            if (!$other_id) {
                $this->deny_json("Не выбран сотрудник");
            }
            if ($other_id === (int) $this->login_user->id) {
                $this->deny_json("Нельзя писать самому себе");
            }
            if (!$this->Chat_model->is_staff_user($other_id)) {
                $this->deny_json("Пользователь недоступен");
            }

            $conversation_id = $this->Chat_model->create_dm($this->login_user->id, $other_id);
            if (!$conversation_id) {
                $this->deny_json("Не удалось создать чат");
            }

            echo json_encode(array("success" => true, "conversation_id" => (int) $conversation_id));
        } catch (\Throwable $e) {
            echo json_encode(array(
                "success" => false,
                "message" => "Ошибка: " . $e->getMessage(),
            ));
        }
    }

    function create_group() {
        try {
            $this->ensure_chat_access();

            $title = trim((string) $this->request->getPost('title'));
            $member_ids = $this->request->getPost('member_ids');
            if (!is_array($member_ids)) {
                $member_ids = array();
            }

            if ($title === '' || count($member_ids) < 1) {
                $this->deny_json("Укажите название и хотя бы одного участника");
            }

            $valid_members = array();
            foreach ($member_ids as $uid) {
                $uid = (int) $uid;
                if ($uid && $uid !== (int) $this->login_user->id && $this->Chat_model->is_staff_user($uid)) {
                    $valid_members[] = $uid;
                }
            }

            if (!$valid_members) {
                $this->deny_json("Не удалось добавить участников");
            }

            $conversation_id = $this->Chat_model->create_group($title, $this->login_user->id, $valid_members);
            if (!$conversation_id) {
                $this->deny_json("Не удалось создать группу");
            }

            echo json_encode(array("success" => true, "conversation_id" => (int) $conversation_id));
        } catch (\Throwable $e) {
            echo json_encode(array(
                "success" => false,
                "message" => "Ошибка: " . $e->getMessage(),
            ));
        }
    }

    function send() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $this->ensure_member($conversation_id);

        $message = trim((string) $this->request->getPost('message'));
        $target_path = get_setting("timeline_file_path");
        $files_data = move_files_from_temp_dir_to_permanent_dir($target_path, "message");
        $files = @unserialize($files_data);
        $has_files = is_array($files) && count($files) > 0;

        if ($message === '' && !$has_files) {
            $this->deny_json(app_lang('field_required'));
        }

        $reply_to = (int) $this->request->getPost('reply_to_message_id');

        $message_id = $this->Chat_model->send_message(
            $conversation_id,
            $this->login_user->id,
            $message,
            $has_files ? $files_data : '',
            $reply_to
        );

        $messages = $this->Chat_model->get_messages($conversation_id, max(0, $message_id - 1));
        $html = $this->template->view('chat/message_items', array(
            'messages' => $messages,
            'login_user' => $this->login_user,
        ), true);

        echo json_encode(array(
            "success" => true,
            "message_id" => $message_id,
            "html" => $html,
        ));
    }

    function download_message_files($message_id = 0) {
        if (!$this->login_user || !$this->login_user->id || $this->login_user->user_type !== 'staff') {
            app_redirect("forbidden");
        }
        if (!$this->check_access_on_messages_for_this_user()) {
            app_redirect("forbidden");
        }

        $message_id = (int) $message_id;
        $info = $this->Chat_model->get_message_for_user($message_id, $this->login_user->id);
        if (!$info || empty($info->files)) {
            app_redirect("forbidden");
        }

        return $this->download_app_files(get_setting("timeline_file_path"), $info->files);
    }

    function download_message_file($message_id = 0, $index = 0) {
        if (!$this->login_user || !$this->login_user->id || $this->login_user->user_type !== 'staff') {
            app_redirect("forbidden");
        }
        if (!$this->check_access_on_messages_for_this_user()) {
            app_redirect("forbidden");
        }

        $message_id = (int) $message_id;
        $index = (int) $index;
        $info = $this->Chat_model->get_message_for_user($message_id, $this->login_user->id);
        if (!$info || empty($info->files)) {
            app_redirect("forbidden");
        }

        $files = @unserialize($info->files);
        if (!is_array($files) || !isset($files[$index]) || !is_array($files[$index])) {
            app_redirect("forbidden");
        }

        return $this->download_app_files(
            get_setting("timeline_file_path"),
            serialize(array($files[$index]))
        );
    }

    function poll() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
            "after_id" => "numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $after_id = (int) $this->request->getPost('after_id');
        $this->ensure_member($conversation_id);

        $messages = $this->Chat_model->get_messages($conversation_id, $after_id);
        if ($messages) {
            $this->Chat_model->mark_read($conversation_id, $this->login_user->id);
        }

        $html = $messages
            ? $this->template->view('chat/message_items', array('messages' => $messages, 'login_user' => $this->login_user), true)
            : '';

        $last_id = $after_id;
        foreach ($messages as $m) {
            $last_id = max($last_id, (int) $m->id);
        }

        echo json_encode(array(
            "success" => true,
            "html" => $html,
            "last_id" => $last_id,
            "count" => count($messages),
        ));
    }

    function count_unread() {
        $this->ensure_chat_access();
        $total = $this->Chat_model->count_unread($this->login_user->id);
        echo json_encode(array("success" => true, "total_notifications" => $total));
    }

    function mark_read() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $this->ensure_member($conversation_id);
        $this->Chat_model->mark_read($conversation_id, $this->login_user->id);

        echo json_encode(array(
            "success" => true,
            "conversation_id" => $conversation_id,
        ));
    }

    function toggle_star() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $this->ensure_member($conversation_id);

        $result = $this->Chat_model->toggle_star($conversation_id, $this->login_user->id);
        if ($result === null) {
            $this->deny_json(app_lang('error_occurred'));
        }

        echo json_encode(array(
            "success" => true,
            "conversation_id" => $conversation_id,
            "starred" => !empty($result['starred']),
        ));
    }

    function toggle_pin() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
            "message_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $message_id = (int) $this->request->getPost('message_id');
        $this->ensure_member($conversation_id);

        $result = $this->Chat_model->toggle_pin($conversation_id, $message_id, $this->login_user->id);
        if ($result === null) {
            $this->deny_json(app_lang('error_occurred'));
        }

        $pinned_messages = $this->Chat_model->get_pinned_messages($conversation_id, 10);
        $bar_html = $this->template->view('chat/pinned_bar', array(
            'pinned_messages' => $pinned_messages,
        ), true);

        echo json_encode(array(
            "success" => true,
            "pinned" => !empty($result['pinned']),
            "message_id" => (int) $result['message_id'],
            "bar_html" => $bar_html,
            "pinned_count" => count($pinned_messages),
        ));
    }

    function filter_messages() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $this->ensure_member($conversation_id);

        $q = trim((string) $this->request->getPost('q'));
        $mode = (string) $this->request->getPost('mode'); // all|files|pinned|search
        $around_id = (int) $this->request->getPost('around_id');
        $options = array('limit' => 100);
        if ($mode === 'files') {
            $options['files'] = true;
        } else if ($mode === 'pinned') {
            $options['pinned'] = true;
        }
        if ($q !== '') {
            $options['q'] = $q;
        }

        if ($around_id > 0 && ($mode === 'all' || $mode === '')) {
            $messages = $this->Chat_model->get_messages_around($conversation_id, $around_id, 40, 40);
            $mode = 'all';
        } else if ($mode === 'all' && $q === '') {
            $messages = $this->Chat_model->get_messages($conversation_id);
        } else {
            $messages = $this->Chat_model->find_messages($conversation_id, $options);
        }

        $html = $messages
            ? $this->template->view('chat/message_items', array(
                'messages' => $messages,
                'login_user' => $this->login_user,
            ), true)
            : '<div class="prime-chat-filter-empty">Ничего не найдено</div>';

        echo json_encode(array(
            "success" => true,
            "html" => $html,
            "count" => count($messages),
            "mode" => $mode,
            "around_id" => $around_id,
        ));
    }

    function edit_message() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
            "message_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $message_id = (int) $this->request->getPost('message_id');
        $message = trim((string) $this->request->getPost('message'));
        $this->ensure_member($conversation_id);

        $existing = $this->Chat_model->get_message($message_id);
        if (!$existing || (int) $existing->conversation_id !== $conversation_id) {
            $this->deny_json(app_lang('error_occurred'));
        }

        $updated = $this->Chat_model->edit_message($message_id, $this->login_user->id, $message);
        if ($updated === false) {
            $this->deny_json(app_lang('field_required'));
        }
        if ($updated === null) {
            $this->deny_json('Редактирование доступно только в течение часа');
        }

        $enriched = $this->Chat_model->get_message_enriched($message_id);
        $html = $enriched
            ? $this->template->view('chat/message_items', array(
                'messages' => array($enriched),
                'login_user' => $this->login_user,
            ), true)
            : '';

        $preview = trim(strip_tags((string) $updated->message));
        if (mb_strlen($preview) > 80) {
            $preview = mb_substr($preview, 0, 80) . '…';
        }

        echo json_encode(array(
            "success" => true,
            "message_id" => $message_id,
            "html" => $html,
            "preview" => $preview,
            "edited_at" => $updated->edited_at,
        ));
    }

    function staff_profile() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "user_id" => "required|numeric",
        ));

        $user_id = (int) $this->request->getPost('user_id');
        $user = $this->Chat_model->get_staff_profile($user_id);
        if (!$user) {
            $this->deny_json(app_lang('error_occurred'));
        }

        $online = !empty($user->last_online) && is_online_user($user->last_online);
        $can_open_full = $this->can_view_team_members_list() || ((int) $this->login_user->id === $user_id);

        $html = $this->template->view('chat/staff_profile', array(
            'user' => $user,
            'online' => $online,
            'can_open_full' => $can_open_full,
            'login_user' => $this->login_user,
        ), true);

        echo json_encode(array(
            "success" => true,
            "html" => $html,
            "user_id" => $user_id,
        ));
    }

    function clear_history() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $this->ensure_member($conversation_id);

        if (!$this->Chat_model->clear_history($conversation_id)) {
            $this->deny_json(app_lang('error_occurred'));
        }

        echo json_encode(array(
            "success" => true,
            "message" => "История очищена",
        ));
    }

    function delete_conversation() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $this->ensure_member($conversation_id);

        if (!$this->Chat_model->leave_conversation($conversation_id, $this->login_user->id)) {
            $this->deny_json(app_lang('error_occurred'));
        }

        echo json_encode(array(
            "success" => true,
            "message" => "Чат удалён",
            "conversation_id" => $conversation_id,
        ));
    }

    function group_info() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $this->ensure_member($conversation_id);

        $conversation = $this->Chat_model->get_conversation($conversation_id, $this->login_user->id);
        if (!$conversation || $conversation->type !== 'group') {
            $this->deny_json(app_lang('error_occurred'));
        }

        $members = $this->Chat_model->get_members($conversation_id);
        $is_admin = $this->Chat_model->is_group_admin($conversation_id, $this->login_user->id);

        $member_ids = array();
        foreach ($members as $m) {
            $member_ids[(int) $m->id] = true;
        }
        $invite_candidates = array();
        if ($is_admin) {
            // all active staff except those already in the group
            foreach ($this->Chat_model->get_staff_users(0) as $user) {
                $uid = (int) $user->id;
                if ($uid && empty($member_ids[$uid])) {
                    $invite_candidates[] = $user;
                }
            }
        }

        $html = $this->template->view('chat/group_info', array(
            'conversation' => $conversation,
            'members' => $members,
            'is_admin' => $is_admin,
            'login_user' => $this->login_user,
            'system_icons' => prime_chat_group_system_icons(),
            'invite_candidates' => $invite_candidates,
        ), true);

        echo json_encode(array(
            "success" => true,
            "html" => $html,
            "conversation_id" => $conversation_id,
            "members_count" => count($members),
            "is_admin" => $is_admin,
        ));
    }

    function remove_member() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
            "user_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $user_id = (int) $this->request->getPost('user_id');
        $this->ensure_member($conversation_id);

        $result = $this->Chat_model->remove_member($conversation_id, $user_id, $this->login_user->id);
        if (empty($result['ok'])) {
            $this->deny_json($result['message'] ?? app_lang('error_occurred'));
        }

        $members = $this->Chat_model->get_members($conversation_id);
        $message_html = '';
        $message_id = (int) ($result['message_id'] ?? 0);
        if ($message_id) {
            $messages = $this->Chat_model->get_messages($conversation_id, max(0, $message_id - 1));
            $message_html = $this->template->view('chat/message_items', array(
                'messages' => $messages,
                'login_user' => $this->login_user,
            ), true);
        }

        echo json_encode(array(
            "success" => true,
            "message" => $result['system_text'] ?? 'Участник исключён',
            "members_count" => count($members),
            "message_id" => $message_id,
            "html" => $message_html,
        ));
    }

    function add_members() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $this->ensure_member($conversation_id);

        $raw = $this->request->getPost('member_ids');
        if (!is_array($raw)) {
            $raw = $raw ? array($raw) : array();
        }
        $member_ids = array_values(array_filter(array_map('intval', $raw)));

        $result = $this->Chat_model->add_members($conversation_id, $member_ids, $this->login_user->id);
        if (empty($result['ok'])) {
            $this->deny_json($result['message'] ?? app_lang('error_occurred'));
        }

        $members = $this->Chat_model->get_members($conversation_id);
        $message_html = '';
        $message_id = (int) ($result['message_id'] ?? 0);
        if ($message_id) {
            $messages = $this->Chat_model->get_messages($conversation_id, max(0, $message_id - 1));
            $message_html = $this->template->view('chat/message_items', array(
                'messages' => $messages,
                'login_user' => $this->login_user,
            ), true);
        }

        // Refresh group info panel markup for admin
        $conversation = $this->Chat_model->get_conversation($conversation_id, $this->login_user->id);
        $is_admin = $this->Chat_model->is_group_admin($conversation_id, $this->login_user->id);
        $member_map = array();
        foreach ($members as $m) {
            $member_map[(int) $m->id] = true;
        }
        $invite_candidates = array();
        if ($is_admin) {
            foreach ($this->Chat_model->get_staff_users(0) as $user) {
                if (empty($member_map[(int) $user->id])) {
                    $invite_candidates[] = $user;
                }
            }
        }
        $info_html = $this->template->view('chat/group_info', array(
            'conversation' => $conversation,
            'members' => $members,
            'is_admin' => $is_admin,
            'login_user' => $this->login_user,
            'system_icons' => prime_chat_group_system_icons(),
            'invite_candidates' => $invite_candidates,
        ), true);

        echo json_encode(array(
            "success" => true,
            "message" => $result['system_text'] ?? 'Участники добавлены',
            "members_count" => count($members),
            "message_id" => $message_id,
            "html" => $message_html,
            "info_html" => $info_html,
        ));
    }

    function set_group_avatar() {
        $this->ensure_chat_access();
        $this->validate_submitted_data(array(
            "conversation_id" => "required|numeric",
        ));

        $conversation_id = (int) $this->request->getPost('conversation_id');
        $this->ensure_member($conversation_id);

        $icon = trim((string) $this->request->getPost('icon'));
        $avatar_value = '';

        if ($icon !== '') {
            $icons = prime_chat_group_system_icons();
            if (!isset($icons[$icon])) {
                $this->deny_json('Неизвестная иконка');
            }
            $avatar_value = 'icon:' . $icon;
        } else {
            $file = get_array_value($_FILES, 'avatar_file');
            $tmp = get_array_value($file, 'tmp_name');
            $size = get_array_value($file, 'size');
            $name = get_array_value($file, 'name');
            if (!$tmp) {
                // also support croppie-style data URI posted as avatar_image
                $posted = $this->request->getPost('avatar_image');
                if ($posted && preg_match('/^data:image\/(\w+);base64,/', $posted, $m)) {
                    $raw = base64_decode(substr($posted, strpos($posted, ',') + 1));
                    if ($raw) {
                        $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
                        $tmp_name = get_setting("temp_file_path") . 'chat_group_' . uniqid() . '.' . $ext;
                        if (!is_dir(get_setting("temp_file_path"))) {
                            @mkdir(get_setting("temp_file_path"), 0755, true);
                        }
                        file_put_contents($tmp_name, $raw);
                        $moved = move_temp_file('avatar.png', get_setting("profile_image_path"), 'chat_group', $tmp_name, '', '', true, strlen($raw));
                        if ($moved) {
                            $avatar_value = serialize($moved);
                        }
                    }
                }
            } else {
                $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
                if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)) {
                    $this->deny_json('Допустимы только изображения JPG/PNG/GIF/WEBP');
                }
                $moved = move_temp_file('avatar.png', get_setting("profile_image_path"), 'chat_group', $tmp, '', '', false, (int) $size);
                if (!$moved) {
                    $this->deny_json('Не удалось загрузить файл');
                }
                $avatar_value = serialize($moved);
            }
        }

        if ($avatar_value === '' && !$this->request->getPost('clear')) {
            $this->deny_json('Выберите иконку или загрузите изображение');
        }

        if ($this->request->getPost('clear')) {
            $avatar_value = '';
        }

        if (!$this->Chat_model->set_group_avatar($conversation_id, $this->login_user->id, $avatar_value)) {
            $this->deny_json('Только администратор может менять иконку');
        }

        $conversation = $this->Chat_model->get_conversation($conversation_id, $this->login_user->id);
        $avatar_html = prime_chat_group_avatar_html($conversation->display_image ?? '', 'sm');

        echo json_encode(array(
            "success" => true,
            "message" => "Иконка обновлена",
            "avatar_html" => $avatar_html,
            "avatar_html_list" => prime_chat_group_avatar_html($conversation->display_image ?? '', ''),
            "conversation_id" => $conversation_id,
        ));
    }

    function save_auto_cleanup() {
        $this->ensure_chat_access();

        $allowed = array(0, 30, 60, 90, 180, 365);
        $days = (int) $this->request->getPost('days');
        if (!in_array($days, $allowed, true)) {
            $this->deny_json('Неверный срок');
        }

        $setting_name = "user_" . $this->login_user->id . "_chat_auto_cleanup_days";
        $this->Settings_model->save_setting($setting_name, (string) $days, "user");

        $deleted = 0;
        if ($days > 0) {
            $deleted = $this->Chat_model->auto_cleanup_own_messages($this->login_user->id, $days);
        }

        echo json_encode(array(
            "success" => true,
            "days" => $days,
            "deleted" => $deleted,
            "message" => $days > 0
                ? ("Автоочистка: " . $days . " дн." . ($deleted ? ". Удалено: " . $deleted : ""))
                : "Автоочистка выключена",
        ));
    }
}
