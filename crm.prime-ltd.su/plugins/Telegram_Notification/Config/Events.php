<?php

namespace Telegram_Notification\Config;

use CodeIgniter\Events\Events;

Events::on('pre_system', function () {
    helper("telegram_notification_general");
});
