<?php

/* Don't change or add any new config in this file */

namespace Telegram_Notification\Config;

use CodeIgniter\Config\BaseConfig;
use Telegram_Notification\Models\Telegram_Integration_settings_model;

class Telegram_Notification extends BaseConfig {

    public $app_settings_array = array();

    public function __construct() {
        $telegram_integration_settings_model = new Telegram_Integration_settings_model();

        $settings = $telegram_integration_settings_model->get_all_settings()->getResult();
        foreach ($settings as $setting) {
            $this->app_settings_array[$setting->setting_name] = $setting->setting_value;
        }
    }

}
