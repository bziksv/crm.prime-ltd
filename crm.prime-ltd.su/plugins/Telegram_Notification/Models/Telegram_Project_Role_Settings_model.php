<?php
namespace Telegram_Notification\Models;

use App\Models\Crud_model;

class Telegram_Project_Role_Settings_model extends Crud_model
{
    protected $table = "telegram_project_role_settings";

    function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Сохраняет состояние уведомления
     */
    function save_setting($user_id, $project_id, $role, $enabled)
    {
        $existing = $this->get_one_where([
            "user_id" => $user_id,
            "project_id" => $project_id,
            "role" => $role
        ]);

        $data = [
            "user_id" => $user_id,
            "project_id" => $project_id,
            "role" => $role,
            "enabled" => $enabled ? 1 : 0
        ];

        if ($existing) {
            $this->ci_save($data, $existing->id);
        } else {
            $this->ci_save($data);
        }
    }

    /**
     * Получает все настройки текущего пользователя
     */
    function get_user_settings($user_id)
    {
        return $this->db->table($this->table)
            ->where('user_id', $user_id)
            ->get()
            ->getResult();
    }
}