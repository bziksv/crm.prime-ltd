<?php
$open_conversation_id = !empty($open_conversation_id) ? (int) $open_conversation_id : 0;

if (!function_exists('prime_messenger_time_label')) {
    function prime_messenger_time_label($datetime) {
        if (!$datetime) {
            return '';
        }
        $ts = strtotime($datetime);
        if (!$ts) {
            return '';
        }
        $today = strtotime('today');
        $yesterday = strtotime('yesterday');
        if ($ts >= $today) {
            return date('H:i', $ts);
        }
        if ($ts >= $yesterday) {
            return 'Вчера';
        }
        if ($ts >= strtotime('-6 days', $today)) {
            $days = array('Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб');
            return $days[(int) date('w', $ts)];
        }
        return date('d.m.Y', $ts);
    }
}
?>
<div id="page-content" class="page-wrapper clearfix prime-messenger-page">
    <div class="prime-messenger">
        <aside class="prime-messenger-sidebar">
            <div class="prime-messenger-toolbar">
                <div class="prime-messenger-search-wrap">
                    <i data-feather="search" class="icon-14 pm-search-icon"></i>
                    <input type="text" id="pm-filter" class="form-control" placeholder="Найти сотрудника или чат" autocomplete="off">
                </div>

                <button type="button" class="pm-tool-btn" id="pm-new-menu" title="Новый чат">
                    <i data-feather="plus" class="icon-18"></i>
                </button>
                <button type="button" class="pm-tool-btn pm-tool-btn-primary" id="pm-contacts" title="Контакты — написать лично">
                    <i data-feather="users" class="icon-16"></i>
                </button>
                <button type="button" class="pm-tool-btn" id="pm-settings-btn" title="Настройки чата">
                    <i data-feather="settings" class="icon-16"></i>
                </button>
            </div>

            <div class="prime-messenger-chips">
                <button type="button" class="pm-chip active js-pm-type-filter" data-filter="all">Все</button>
                <button type="button" class="pm-chip js-pm-type-filter" data-filter="unread">Непрочитанные</button>
                <button type="button" class="pm-chip js-pm-type-filter" data-filter="dm">Личные</button>
                <button type="button" class="pm-chip js-pm-type-filter" data-filter="group">Группы</button>
            </div>

            <div class="prime-messenger-list" id="pm-list">
                <?php if ($conversations) { ?>
                    <?php foreach ($conversations as $item) { ?>
                        <?php
                        $unread = (int) ($item->unread_count ?? 0);
                        $time = $item->last_message_at ?: $item->updated_at;
                        $is_group = $item->type === 'group';
                        $is_starred = !empty($item->is_starred);
                        $sender = '';
                        if ($is_group && !empty($item->last_sender_first_name)) {
                            $sender = $item->last_sender_first_name . ': ';
                        }
                        ?>
                        <div role="button" tabindex="0"
                            class="prime-messenger-item js-pm-open <?php echo $unread ? 'is-unread' : ''; ?> <?php echo $is_starred ? 'is-starred' : ''; ?>"
                            data-id="<?php echo $item->id; ?>"
                            data-type="<?php echo esc($item->type); ?>"
                            data-unread="<?php echo $unread ? '1' : '0'; ?>"
                            data-starred="<?php echo $is_starred ? '1' : '0'; ?>"
                            data-peer-id="<?php echo (!$is_group && !empty($item->peer_id)) ? (int) $item->peer_id : ''; ?>"
                            data-sort-time="<?php echo $time ? (int) strtotime($time) : 0; ?>"
                            data-sort-name="<?php echo esc(mb_strtolower($item->display_title)); ?>"
                            data-title="<?php echo esc(mb_strtolower($item->display_title . ' ' . ($item->preview ?? ''))); ?>">
                            <div class="prime-messenger-avatar">
                                <?php if ($is_group) { ?>
                                    <span class="js-pm-group-avatar-slot" data-conversation-id="<?php echo (int) $item->id; ?>">
                                        <?php echo prime_chat_group_avatar_html($item->display_image ?? '', ''); ?>
                                    </span>
                                <?php } else { ?>
                                    <img src="<?php echo get_avatar($item->display_image); ?>" alt="">
                                    <?php if (!empty($item->peer_last_online) && is_online_user($item->peer_last_online)) { ?>
                                        <i class="online"></i>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                            <div class="prime-messenger-item-body">
                                <div class="prime-messenger-item-top">
                                    <strong><?php echo esc($item->display_title); ?></strong>
                                    <span class="pm-item-meta">
                                        <button type="button"
                                            class="pm-star-btn js-pm-star <?php echo $is_starred ? 'is-on' : ''; ?>"
                                            data-id="<?php echo $item->id; ?>"
                                            title="<?php echo $is_starred ? 'Убрать из избранного' : 'В избранное'; ?>"
                                            aria-label="<?php echo $is_starred ? 'Убрать из избранного' : 'В избранное'; ?>">
                                            <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">
                                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                                            </svg>
                                        </button>
                                        <span class="pm-time"><?php echo prime_messenger_time_label($time); ?></span>
                                    </span>
                                </div>
                                <div class="prime-messenger-item-preview">
                                    <span><?php echo $item->preview ? esc($sender . $item->preview) : 'Нет сообщений'; ?></span>
                                    <?php if ($unread) { ?>
                                        <em><?php echo $unread > 99 ? '99+' : $unread; ?></em>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="prime-messenger-empty-list">
                        <div class="pm-empty-icon"><i data-feather="message-circle" class="icon-24"></i></div>
                        <div class="pm-empty-title"><?php echo app_lang('no_chats_yet'); ?></div>
                        <div class="pm-empty-text">Напишите коллеге или создайте группу</div>
                        <button type="button" class="btn btn-primary btn-sm mt15" id="pm-empty-start"><?php echo app_lang('start_chat'); ?></button>
                    </div>
                <?php } ?>
            </div>
        </aside>

        <section class="prime-messenger-main" id="pm-main">
            <div class="prime-messenger-placeholder" id="pm-placeholder">
                <div class="pm-placeholder-art">
                    <i data-feather="message-square" class="icon-40"></i>
                </div>
                <h3>Выберите чат и начните общение</h3>
                <p>Личные переписки и группы сотрудников в одном месте</p>
                <div class="pm-placeholder-actions">
                    <button type="button" class="btn btn-primary" id="pm-placeholder-start"><?php echo app_lang('start_chat'); ?></button>
                </div>
            </div>
            <div id="pm-settings" class="pm-settings hide" aria-hidden="true">
                <div class="pm-settings-header">
                    <div>
                        <div class="pm-settings-kicker">Мессенджер</div>
                        <h3 class="pm-settings-title">Настройки чата</h3>
                    </div>
                    <button type="button" class="pm-tool-btn" id="pm-settings-close" title="Закрыть">
                        <i data-feather="x" class="icon-16"></i>
                    </button>
                </div>
                <div class="pm-settings-body">
                    <section class="pm-settings-block">
                        <h4 class="pm-settings-block-title">Сортировка списка</h4>
                        <p class="pm-settings-block-text">Избранные чаты всегда остаются сверху</p>
                        <div class="pm-settings-options" role="radiogroup" aria-label="Сортировка списка">
                            <label class="pm-settings-option">
                                <input type="radio" name="pm-sort" value="recent" class="js-pm-sort" checked>
                                <span>
                                    <strong>По последним сообщениям</strong>
                                    <em>Сначала самые свежие переписки</em>
                                </span>
                            </label>
                            <label class="pm-settings-option">
                                <input type="radio" name="pm-sort" value="unread" class="js-pm-sort">
                                <span>
                                    <strong>Сначала непрочитанные</strong>
                                    <em>Непрочитанные выше прочитанных</em>
                                </span>
                            </label>
                            <label class="pm-settings-option">
                                <input type="radio" name="pm-sort" value="name" class="js-pm-sort">
                                <span>
                                    <strong>По имени</strong>
                                    <em>Алфавитный порядок A→Я</em>
                                </span>
                            </label>
                        </div>
                    </section>
                    <section class="pm-settings-block">
                        <h4 class="pm-settings-block-title">Отправка сообщений</h4>
                        <p class="pm-settings-block-text">Горячая клавиша в поле ввода</p>
                        <div class="pm-settings-options" role="radiogroup" aria-label="Отправка сообщений">
                            <label class="pm-settings-option">
                                <input type="radio" name="pm-send-key" value="enter" class="js-pm-send-key" checked>
                                <span>
                                    <strong>Enter</strong>
                                    <em>Новая строка — Shift+Enter</em>
                                </span>
                            </label>
                            <label class="pm-settings-option">
                                <input type="radio" name="pm-send-key" value="mod_enter" class="js-pm-send-key">
                                <span>
                                    <strong class="js-pm-mod-enter-label">Ctrl+Enter</strong>
                                    <em>Enter переносит на новую строку</em>
                                </span>
                            </label>
                        </div>
                    </section>
                    <section class="pm-settings-block">
                        <h4 class="pm-settings-block-title">Автоочистка своих сообщений</h4>
                        <p class="pm-settings-block-text">Старые ваши сообщения удаляются для всех участников чата</p>
                        <div class="pm-settings-options" role="radiogroup" aria-label="Автоочистка своих сообщений">
                            <label class="pm-settings-option">
                                <input type="radio" name="pm-auto-cleanup" value="0" class="js-pm-auto-cleanup" checked>
                                <span>
                                    <strong>Не удалять</strong>
                                    <em>Сообщения остаются без срока</em>
                                </span>
                            </label>
                            <?php foreach (array(30, 60, 90, 180, 365) as $days) { ?>
                                <label class="pm-settings-option">
                                    <input type="radio" name="pm-auto-cleanup" value="<?php echo (int) $days; ?>" class="js-pm-auto-cleanup">
                                    <span>
                                        <strong><?php echo (int) $days; ?> дней</strong>
                                        <em>Удалять сообщения старше <?php echo (int) $days; ?> дн.</em>
                                    </span>
                                </label>
                            <?php } ?>
                        </div>
                    </section>
                </div>
            </div>
            <div id="pm-thread" class="hide"></div>
        </section>
    </div>
</div>

<!-- Choice: personal or group -->
<div class="modal fade" id="pm-new-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Новый чат</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p0">
                <button type="button" class="prime-messenger-item js-pm-open-dm-modal">
                    <div class="prime-messenger-avatar-group"><i data-feather="user" class="icon-16"></i></div>
                    <div class="prime-messenger-item-body">
                        <strong>Личное сообщение</strong>
                        <div class="text-off">Написать одному сотруднику</div>
                    </div>
                </button>
                <button type="button" class="prime-messenger-item js-pm-open-group-modal">
                    <div class="prime-messenger-avatar-group" style="background:#dcfce7;color:#15803d"><i data-feather="users" class="icon-16"></i></div>
                    <div class="prime-messenger-item-body">
                        <strong><?php echo app_lang('new_group'); ?></strong>
                        <div class="text-off">Создать групповой чат</div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$job_titles = $job_titles ?? array();
$staff_users = $staff_users ?? array();

// Case-insensitive job groups + options for structured picker
$pm_job_groups = array();
foreach ($staff_users as $user) {
    $job = trim((string) ($user->job_title ?? ''));
    if ($job === '' || $job === 'Untitled') {
        $job = 'Без должности';
    }
    $key = mb_strtolower($job === 'Без должности' ? '' : $job);
    if (!isset($pm_job_groups[$key])) {
        $pm_job_groups[$key] = array('label' => $job, 'users' => array());
    }
    $pm_job_groups[$key]['users'][] = $user;
}
uksort($pm_job_groups, function ($a, $b) use ($pm_job_groups) {
    return strnatcasecmp($pm_job_groups[$a]['label'], $pm_job_groups[$b]['label']);
});

$pm_job_options = array();
foreach ($pm_job_groups as $key => $group) {
    if ($key === '') {
        continue;
    }
    $pm_job_options[] = array(
        'key' => $key,
        'label' => $group['label'],
        'count' => count($group['users']),
    );
}
?>
<div class="modal fade" id="pm-dm-modal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php echo app_lang('start_chat'); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="pm-people-filters mb15">
                    <div class="pm-people-filter-row">
                        <input type="text" class="form-control js-pm-people-filter" placeholder="<?php echo app_lang('search_people'); ?>…">
                        <?php if ($pm_job_options) {
                            echo view('chat/job_filter', array('job_options' => $pm_job_options));
                        } ?>
                    </div>
                </div>
                <div class="prime-messenger-people pm-people-grouped">
                    <?php foreach ($pm_job_groups as $job_key => $group) {
                        $job_label = $group['label'];
                        $users_in_job = $group['users'];
                        ?>
                        <div class="pm-people-group" data-job-group="<?php echo esc($job_key); ?>">
                            <div class="pm-people-group-head">
                                <div class="pm-people-group-toggle is-static">
                                    <strong><?php echo esc($job_label); ?></strong>
                                    <em><?php echo count($users_in_job); ?></em>
                                </div>
                            </div>
                            <?php foreach ($users_in_job as $user) {
                                $job = trim((string) ($user->job_title ?? ''));
                                if ($job === 'Untitled') {
                                    $job = '';
                                }
                                ?>
                                <button type="button" class="prime-messenger-item js-pm-start-dm"
                                    data-user-id="<?php echo $user->id; ?>"
                                    data-job="<?php echo esc($job_key); ?>"
                                    data-title="<?php echo esc(mb_strtolower($user->first_name . ' ' . $user->last_name . ' ' . $job)); ?>">
                                    <div class="prime-messenger-avatar">
                                        <img src="<?php echo get_avatar($user->image); ?>" alt="">
                                    </div>
                                    <div class="prime-messenger-item-body">
                                        <strong><?php echo esc($user->first_name . ' ' . $user->last_name); ?></strong>
                                    </div>
                                </button>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pm-group-modal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php echo app_lang('new_group'); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="pm-group-title" class="form-control mb15" placeholder="<?php echo app_lang('chat_group_name'); ?>">

                <?php
                $staff_teams = $staff_teams ?? array();
                if ($staff_teams) {
                    ?>
                    <div class="pm-team-pick mb15">
                        <div class="pm-team-pick-label">Команды</div>
                        <div class="pm-team-chips">
                            <?php foreach ($staff_teams as $team) {
                                $member_ids = array_values(array_filter(array_map('intval', explode(',', (string) ($team->members ?? '')))));
                                if (!$member_ids) {
                                    continue;
                                }
                                ?>
                                <button type="button"
                                    class="pm-team-chip js-pm-pick-team"
                                    data-members="<?php echo esc(implode(',', $member_ids)); ?>"
                                    title="Выбрать всех из команды">
                                    <span class="pm-team-chip-name"><?php echo esc($team->title); ?></span>
                                    <em><?php echo count($member_ids); ?></em>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>

                <div class="mb10 text-off"><?php echo app_lang('chat_members'); ?></div>
                <div class="pm-people-filters mb15">
                    <div class="pm-people-filter-row">
                        <input type="text" class="form-control js-pm-people-filter" placeholder="<?php echo app_lang('search_people'); ?>…">
                        <?php if ($pm_job_options) {
                            echo view('chat/job_filter', array('job_options' => $pm_job_options));
                        } ?>
                    </div>
                </div>
                <div class="pm-people-bulk mb10">
                    <button type="button" class="btn btn-default btn-sm js-pm-select-visible" data-check="1">Выбрать видимых</button>
                    <button type="button" class="btn btn-default btn-sm js-pm-select-visible" data-check="0">Снять видимых</button>
                </div>
                <div class="prime-messenger-people pm-people-grouped">
                    <?php foreach ($pm_job_groups as $job_key => $group) {
                        $job_label = $group['label'];
                        $users_in_job = $group['users'];
                        ?>
                        <div class="pm-people-group" data-job-group="<?php echo esc($job_key); ?>">
                            <div class="pm-people-group-head">
                                <button type="button" class="pm-people-group-toggle js-pm-select-group" data-check="1" title="Выбрать группу">
                                    <strong><?php echo esc($job_label); ?></strong>
                                    <em><?php echo count($users_in_job); ?></em>
                                </button>
                            </div>
                            <?php foreach ($users_in_job as $user) {
                                $job = trim((string) ($user->job_title ?? ''));
                                if ($job === 'Untitled') {
                                    $job = '';
                                }
                                ?>
                                <label class="prime-messenger-item"
                                    data-job="<?php echo esc($job_key); ?>"
                                    data-title="<?php echo esc(mb_strtolower($user->first_name . ' ' . $user->last_name . ' ' . $job)); ?>">
                                    <div class="prime-messenger-avatar">
                                        <img src="<?php echo get_avatar($user->image); ?>" alt="">
                                    </div>
                                    <div class="prime-messenger-item-body">
                                        <strong><?php echo esc($user->first_name . ' ' . $user->last_name); ?></strong>
                                    </div>
                                    <input type="checkbox" class="js-pm-group-member form-check-input" value="<?php echo $user->id; ?>">
                                </label>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
                <button type="button" class="btn btn-primary" id="pm-create-group"><?php echo app_lang('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pm-profile-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content pm-profile-modal-content">
            <button type="button" class="btn-close pm-profile-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p0" id="pm-profile-body">
                <div class="prime-messenger-loading p20">Загрузка…</div>
            </div>
        </div>
    </div>
</div>

<div id="pm-list-ctx" class="pm-list-ctx hide" role="menu" aria-hidden="true">
    <button type="button" class="pm-list-ctx-item" data-action="open" role="menuitem">Открыть</button>
    <button type="button" class="pm-list-ctx-item js-pm-ctx-profile" data-action="profile" role="menuitem">Профиль</button>
    <button type="button" class="pm-list-ctx-item js-pm-ctx-read" data-action="read" role="menuitem">Отметить прочитанным</button>
    <button type="button" class="pm-list-ctx-item js-pm-ctx-star" data-action="star" role="menuitem">В избранное</button>
    <div class="pm-list-ctx-sep" role="separator"></div>
    <button type="button" class="pm-list-ctx-item" data-action="clear" role="menuitem">Очистить историю</button>
    <button type="button" class="pm-list-ctx-item is-danger js-pm-ctx-delete" data-action="delete" role="menuitem">Удалить чат</button>
</div>

<div class="modal fade" id="pm-group-info-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Группа</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="pm-group-info-body">
                <div class="prime-messenger-loading p20">Загрузка…</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pm-delete-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="pm-delete-modal-title">Удалить чат?</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb0" id="pm-delete-modal-text">Чат исчезнет из вашего списка. История останется у собеседника.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('cancel'); ?></button>
                <button type="button" class="btn btn-danger" id="pm-delete-modal-confirm">Удалить</button>
            </div>
        </div>
    </div>
</div>

<?php echo view('chat/emoji_picker'); ?>

<script>
$(document).ready(function () {
    window.primeFeatherReplace = function (root) {
        if (typeof feather === 'undefined' || !feather || !feather.icons) {
            return;
        }
        try {
            var $scope = root ? $(root) : $(document);
            $scope.find('[data-feather]').each(function () {
                var name = this.getAttribute('data-feather');
                if (!name || !feather.icons[name]) {
                    this.removeAttribute('data-feather');
                }
            });
            feather.replace();
        } catch (e) {
            console.warn('primeFeatherReplace failed', e);
        }
    };

    window.primeFeatherReplace();

    var openId = <?php echo (int) $open_conversation_id; ?>;
    var activeFilters = {
        unread: false,
        dm: false,
        group: false
    };
    var pmSortMode = 'recent';
    var pmSortKey = 'prime_messenger_sort';
    try {
        var savedSort = localStorage.getItem(pmSortKey);
        if (savedSort === 'recent' || savedSort === 'unread' || savedSort === 'name') {
            pmSortMode = savedSort;
        }
    } catch (e) {}

    var pmSendKeyMode = 'enter';
    var pmSendKeyStorage = 'prime_messenger_send_key';
    var pmIsMac = /Mac|iPhone|iPad|iPod/i.test(navigator.platform || '') || /Mac OS X/i.test(navigator.userAgent || '');
    try {
        var savedSendKey = localStorage.getItem(pmSendKeyStorage);
        if (savedSendKey === 'enter' || savedSendKey === 'mod_enter') {
            pmSendKeyMode = savedSendKey;
        }
    } catch (e) {}
    window.primeChatGetSendKeyMode = function () {
        return pmSendKeyMode;
    };

    var pmAutoCleanupDays = <?php echo (int) ($auto_cleanup_days ?? 0); ?>;
    if ([0, 30, 60, 90, 180, 365].indexOf(pmAutoCleanupDays) === -1) {
        pmAutoCleanupDays = 0;
    }

    // EN <-> RU keyboard layout (same physical keys)
    var pmEn = "`qwertyuiop[]asdfghjkl;'zxcvbnm,./~QWERTYUIOP{}ASDFGHJKL:\"ZXCVBNM<>?";
    var pmRu = "ёйцукенгшщзхъфывапролджэячсмитьбю.ЁЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ,";

    function pmSwitchLayout(text) {
        text = String(text || '');
        var out = '';
        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i);
            var enIdx = pmEn.indexOf(ch);
            if (enIdx !== -1) {
                out += pmRu.charAt(enIdx);
                continue;
            }
            var ruIdx = pmRu.indexOf(ch);
            if (ruIdx !== -1) {
                out += pmEn.charAt(ruIdx);
                continue;
            }
            out += ch;
        }
        return out;
    }

    function pmMatchText(haystack, needle) {
        if (!needle) {
            return true;
        }
        haystack = String(haystack || '').toLowerCase();
        needle = String(needle || '').toLowerCase();
        if (haystack.indexOf(needle) !== -1) {
            return true;
        }
        var alt = pmSwitchLayout(needle).toLowerCase();
        return alt !== needle && haystack.indexOf(alt) !== -1;
    }

    function hasAnyTypeFilter() {
        return activeFilters.dm || activeFilters.group;
    }

    function hasAnyFilter() {
        return activeFilters.unread || activeFilters.dm || activeFilters.group;
    }

    function syncFilterChips() {
        var any = hasAnyFilter();
        $('.js-pm-type-filter').each(function () {
            var key = $(this).attr('data-filter');
            if (key === 'all') {
                $(this).toggleClass('active', !any);
            } else {
                $(this).toggleClass('active', !!activeFilters[key]);
            }
        });
    }

    function filterList() {
        var q = ($('#pm-filter').val() || '');
        var typeFiltered = hasAnyTypeFilter();

        $('#pm-list .js-pm-open').each(function () {
            var $el = $(this);
            var title = ($el.attr('data-title') || '');
            var type = $el.attr('data-type');
            var unread = $el.attr('data-unread') === '1';

            var matchText = pmMatchText(title, q);
            var matchUnread = !activeFilters.unread || unread;
            var matchType = true;
            if (typeFiltered) {
                matchType = (activeFilters.dm && type === 'dm') || (activeFilters.group && type === 'group');
            }

            $el.toggle(matchText && matchUnread && matchType);
        });
    }

    function syncSortRadios() {
        $('.js-pm-sort').prop('checked', false);
        $('.js-pm-sort[value="' + pmSortMode + '"]').prop('checked', true);
    }

    function syncSendKeyRadios() {
        $('.js-pm-send-key').prop('checked', false);
        $('.js-pm-send-key[value="' + pmSendKeyMode + '"]').prop('checked', true);
        $('.js-pm-mod-enter-label').text(pmIsMac ? '⌘+Enter' : 'Ctrl+Enter');
    }

    function syncAutoCleanupRadios() {
        $('.js-pm-auto-cleanup').prop('checked', false);
        $('.js-pm-auto-cleanup[value="' + pmAutoCleanupDays + '"]').prop('checked', true);
    }

    function setPmSendKey(mode) {
        if (mode !== 'enter' && mode !== 'mod_enter') {
            mode = 'enter';
        }
        pmSendKeyMode = mode;
        try {
            localStorage.setItem(pmSendKeyStorage, pmSendKeyMode);
        } catch (e) {}
        syncSendKeyRadios();
    }

    function sortList() {
        var $items = $('#pm-list .js-pm-open').get();
        $items.sort(function (a, b) {
            var $a = $(a);
            var $b = $(b);
            var starA = $a.attr('data-starred') === '1' ? 1 : 0;
            var starB = $b.attr('data-starred') === '1' ? 1 : 0;
            if (starA !== starB) {
                return starB - starA;
            }

            if (pmSortMode === 'name') {
                var nameA = $a.attr('data-sort-name') || '';
                var nameB = $b.attr('data-sort-name') || '';
                if (nameA < nameB) return -1;
                if (nameA > nameB) return 1;
                return 0;
            }

            if (pmSortMode === 'unread') {
                var unreadA = $a.attr('data-unread') === '1' ? 1 : 0;
                var unreadB = $b.attr('data-unread') === '1' ? 1 : 0;
                if (unreadA !== unreadB) {
                    return unreadB - unreadA;
                }
            }

            var timeA = parseInt($a.attr('data-sort-time'), 10) || 0;
            var timeB = parseInt($b.attr('data-sort-time'), 10) || 0;
            return timeB - timeA;
        });
        $('#pm-list').append($items);
    }

    function setPmSort(mode) {
        if (mode !== 'recent' && mode !== 'unread' && mode !== 'name') {
            mode = 'recent';
        }
        pmSortMode = mode;
        try {
            localStorage.setItem(pmSortKey, pmSortMode);
        } catch (e) {}
        syncSortRadios();
        sortList();
        filterList();
    }

    function showPmSettings() {
        if (window.primeChatPollTimer) {
            clearInterval(window.primeChatPollTimer);
            window.primeChatPollTimer = null;
        }
        $('.js-pm-open').removeClass('active');
        $('#pm-settings-btn').addClass('is-active');
        $('#pm-placeholder').addClass('hide');
        $('#pm-thread').addClass('hide').empty();
        $('#pm-settings').removeClass('hide').attr('aria-hidden', 'false');
        syncSortRadios();
        syncSendKeyRadios();
        syncAutoCleanupRadios();
        history.replaceState(null, '', '<?php echo get_uri('messages/inbox'); ?>');
        if (typeof window.primeFeatherReplace === 'function') {
            window.primeFeatherReplace('#pm-settings');
        }
    }

    function hidePmSettings() {
        $('#pm-settings-btn').removeClass('is-active');
        $('#pm-settings').addClass('hide').attr('aria-hidden', 'true');
    }

    syncSortRadios();
    syncSendKeyRadios();
    syncAutoCleanupRadios();
    sortList();

    $(document).on('click', '#pm-settings-btn', function (e) {
        e.preventDefault();
        if (!$('#pm-settings').hasClass('hide')) {
            hidePmSettings();
            $('#pm-placeholder').removeClass('hide');
            return;
        }
        showPmSettings();
    });

    $(document).on('click', '#pm-settings-close', function (e) {
        e.preventDefault();
        hidePmSettings();
        $('#pm-placeholder').removeClass('hide');
    });

    $(document).on('change', '.js-pm-sort', function () {
        setPmSort($(this).val());
    });

    $(document).on('change', '.js-pm-send-key', function () {
        setPmSendKey($(this).val());
    });

    $(document).on('change', '.js-pm-auto-cleanup', function () {
        var days = parseInt($(this).val(), 10) || 0;
        var $opts = $('.js-pm-auto-cleanup');
        $opts.prop('disabled', true);
        $.ajax({
            url: '<?php echo get_uri('chat/save_auto_cleanup'); ?>',
            type: 'POST',
            dataType: 'json',
            data: { days: days },
            success: function (res) {
                $opts.prop('disabled', false);
                if (!res || !res.success) {
                    syncAutoCleanupRadios();
                    appAlert.error((res && res.message) ? res.message : 'Не удалось сохранить');
                    return;
                }
                pmAutoCleanupDays = days;
                syncAutoCleanupRadios();
                appAlert.success(res.message || 'Сохранено');
                if (res.deleted > 0) {
                    window.location.reload();
                }
            },
            error: function () {
                $opts.prop('disabled', false);
                syncAutoCleanupRadios();
                appAlert.error('Не удалось сохранить');
            }
        });
    });

    function toggleFilter(filter) {
        if (filter === 'all') {
            activeFilters.unread = false;
            activeFilters.dm = false;
            activeFilters.group = false;
        } else if (activeFilters.hasOwnProperty(filter)) {
            activeFilters[filter] = !activeFilters[filter];
        }
        syncFilterChips();
        filterList();
    }

    $('#pm-filter').on('input', filterList);
    $(document).on('click', '.js-pm-type-filter', function (e) {
        e.preventDefault();
        toggleFilter($(this).attr('data-filter'));
    });

    $(document).on('input', '.js-pm-people-filter', function () {
        filterPeopleModal($(this).closest('.modal-content'));
    });

    function closeJobPickers($except) {
        $('.pm-job-picker').each(function () {
            if ($except && this === $except.get(0)) {
                return;
            }
            $(this).removeClass('is-open');
            $(this).find('.pm-job-picker-menu').addClass('hide');
            $(this).find('.js-pm-job-picker-toggle').attr('aria-expanded', 'false');
        });
    }

    function syncJobPickerLabel($picker) {
        var $checks = $picker.find('.js-pm-job-option[data-job!=""] .js-pm-job-check:checked');
        var $all = $picker.find('.js-pm-job-option[data-job=""]');
        var jobs = [];
        var labels = [];
        $checks.each(function () {
            var $opt = $(this).closest('.js-pm-job-option');
            jobs.push(String($opt.attr('data-job') || '').toLowerCase());
            labels.push(String($opt.attr('data-label') || ''));
        });
        jobs = jobs.filter(Boolean);

        if (!jobs.length) {
            $all.addClass('is-active').find('.js-pm-job-check').prop('checked', true);
            $picker.find('.js-pm-job-filter').val('');
            $picker.find('.js-pm-job-picker-label').text('Все должности');
            return [];
        }

        $all.removeClass('is-active').find('.js-pm-job-check').prop('checked', false);
        $picker.find('.js-pm-job-filter').val(jobs.join('|'));
        var label = labels.length <= 2
            ? labels.join(', ')
            : (labels[0] + ', ' + labels[1] + ' +' + (labels.length - 2));
        $picker.find('.js-pm-job-picker-label').text(label);
        return jobs;
    }

    $(document).on('click', '.js-pm-job-picker-toggle', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $picker = $(this).closest('.pm-job-picker');
        var willOpen = !$picker.hasClass('is-open');
        closeJobPickers(willOpen ? $picker : null);
        $picker.toggleClass('is-open', willOpen);
        $picker.find('.pm-job-picker-menu').toggleClass('hide', !willOpen);
        $(this).attr('aria-expanded', willOpen ? 'true' : 'false');
    });

    $(document).on('click', '.js-pm-job-option', function (e) {
        e.stopPropagation();
    });

    $(document).on('change', '.js-pm-job-check', function (e) {
        e.stopPropagation();
        var $check = $(this);
        var $opt = $check.closest('.js-pm-job-option');
        var $picker = $opt.closest('.pm-job-picker');
        var job = String($opt.attr('data-job') || '');

        if (!job) {
            // "Все" — снять остальные
            $picker.find('.js-pm-job-option[data-job!=""]').removeClass('is-active')
                .find('.js-pm-job-check').prop('checked', false);
            $opt.addClass('is-active');
            $check.prop('checked', true);
        } else {
            $opt.toggleClass('is-active', $check.is(':checked'));
            var any = $picker.find('.js-pm-job-option[data-job!=""] .js-pm-job-check:checked').length > 0;
            if (!any) {
                $picker.find('.js-pm-job-option[data-job=""]').addClass('is-active')
                    .find('.js-pm-job-check').prop('checked', true);
            } else {
                $picker.find('.js-pm-job-option[data-job=""]').removeClass('is-active')
                    .find('.js-pm-job-check').prop('checked', false);
            }
        }

        syncJobPickerLabel($picker);
        var $modal = $picker.closest('.modal-content');
        var $invite = $picker.closest('.pm-group-invite-panel');
        if ($invite.length) {
            filterInvitePanel($invite);
        } else {
            filterPeopleModal($modal);
        }
    });

    $(document).on('click', '.js-pm-job-clear', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $picker = $(this).closest('.pm-job-picker');
        $picker.find('.js-pm-job-option[data-job!=""]').removeClass('is-active')
            .find('.js-pm-job-check').prop('checked', false);
        $picker.find('.js-pm-job-option[data-job=""]').addClass('is-active')
            .find('.js-pm-job-check').prop('checked', true);
        syncJobPickerLabel($picker);
        var $invite = $picker.closest('.pm-group-invite-panel');
        if ($invite.length) {
            filterInvitePanel($invite);
        } else {
            filterPeopleModal($picker.closest('.modal-content'));
        }
    });

    $(document).on('click', '.js-pm-job-apply', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeJobPickers();
    });

    $(document).on('click', function () {
        closeJobPickers();
    });

    $(document).on('click', '.pm-job-picker-menu', function (e) {
        e.stopPropagation();
    });

    function getSelectedJobs($scope) {
        var raw = String($scope.find('.js-pm-job-filter').val() || '');
        if (!raw) {
            return [];
        }
        return raw.split('|').map(function (j) { return j.toLowerCase(); }).filter(Boolean);
    }

    function filterPeopleModal($modal) {
        if (!$modal || !$modal.length) {
            return;
        }
        var value = $modal.find('.js-pm-people-filter').first().val() || '';
        var jobs = getSelectedJobs($modal.find('.pm-people-filters').first().length
            ? $modal.find('.pm-people-filters').first()
            : $modal);

        // Count matches in a variable — never use :visible here.
        // Hidden parent groups make :visible always false and the list sticks empty.
        $modal.find('.pm-people-group').each(function () {
            var $group = $(this);
            var visibleCount = 0;
            $group.find('.prime-messenger-item[data-title]').each(function () {
                var $item = $(this);
                var title = ($item.attr('data-title') || '');
                var itemJob = ($item.attr('data-job') || '').toLowerCase();
                var matchText = pmMatchText(title, value);
                var matchJob = !jobs.length || jobs.indexOf(itemJob) !== -1;
                var show = matchText && matchJob;
                $item.toggle(show);
                if (show) {
                    visibleCount++;
                }
            });
            $group.toggle(visibleCount > 0);
            $group.find('.pm-people-group-head em').text(visibleCount);
        });
    }

    $(document).on('click', '.js-pm-select-visible', function () {
        var check = $(this).attr('data-check') === '1';
        $('#pm-group-modal .prime-messenger-people .prime-messenger-item:visible .js-pm-group-member')
            .prop('checked', check);
        syncTeamChips();
    });

    $(document).on('click', '.js-pm-select-group', function (e) {
        e.preventDefault();
        var $group = $(this).closest('.pm-people-group');
        var $boxes = $group.find('.prime-messenger-item:visible .js-pm-group-member');
        var allChecked = $boxes.length && $boxes.filter(':checked').length === $boxes.length;
        $boxes.prop('checked', !allChecked);
        syncTeamChips();
    });

    $(document).on('click', '.js-pm-pick-team', function (e) {
        e.preventDefault();
        var $chip = $(this);
        var ids = String($chip.attr('data-members') || '').split(',').filter(Boolean);
        if (!ids.length) {
            return;
        }
        var selectAll = !$chip.hasClass('is-active');
        ids.forEach(function (id) {
            $('#pm-group-modal .js-pm-group-member[value="' + id + '"]').prop('checked', selectAll);
        });
        if (selectAll && !$('#pm-group-title').val()) {
            $('#pm-group-title').val($chip.find('.pm-team-chip-name').text());
        }
        syncTeamChips();
    });

    $(document).on('change', '#pm-group-modal .js-pm-group-member', syncTeamChips);

    function syncTeamChips() {
        $('#pm-group-modal .js-pm-pick-team').each(function () {
            var $chip = $(this);
            var ids = String($chip.attr('data-members') || '').split(',').filter(Boolean);
            if (!ids.length) {
                $chip.removeClass('is-active');
                return;
            }
            var allOn = ids.every(function (id) {
                return $('#pm-group-modal .js-pm-group-member[value="' + id + '"]').is(':checked');
            });
            $chip.toggleClass('is-active', allOn);
        });
    }

    function ensureModalOnBody(id) {
        var el = document.getElementById(id);
        if (el && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
        return el;
    }

    function showModal(id) {
        var el = ensureModalOnBody(id);
        if (!el || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return;
        }
        bootstrap.Modal.getOrCreateInstance(el).show();
    }

    function hideModal(id) {
        var el = document.getElementById(id);
        if (!el || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return;
        }
        var inst = bootstrap.Modal.getInstance(el);
        if (inst) {
            inst.hide();
        }
    }

    // + and "Написать" → choice personal/group
    $(document).on('click', '#pm-new-menu, #pm-empty-start, #pm-placeholder-start', function (e) {
        e.preventDefault();
        showModal('pm-new-modal');
    });

    $(document).on('click', '.js-pm-open-dm-modal', function (e) {
        e.preventDefault();
        hideModal('pm-new-modal');
        resetPeopleModalFilters('#pm-dm-modal');
        showModal('pm-dm-modal');
    });

    $(document).on('click', '.js-pm-open-group-modal', function (e) {
        e.preventDefault();
        hideModal('pm-new-modal');
        resetPeopleModalFilters('#pm-group-modal');
        showModal('pm-group-modal');
    });

    $(document).on('click', '#pm-contacts', function (e) {
        e.preventDefault();
        resetPeopleModalFilters('#pm-dm-modal');
        showModal('pm-dm-modal');
    });

    function resetPeopleModalFilters(modalId) {
        var $modal = $(modalId);
        if (!$modal.length) {
            return;
        }
        $modal.find('.js-pm-people-filter').val('');
        $modal.find('.pm-job-picker').each(function () {
            var $picker = $(this);
            $picker.find('.js-pm-job-option[data-job!=""]').removeClass('is-active')
                .find('.js-pm-job-check').prop('checked', false);
            $picker.find('.js-pm-job-option[data-job=""]').addClass('is-active')
                .find('.js-pm-job-check').prop('checked', true);
            syncJobPickerLabel($picker);
        });
        filterPeopleModal($modal.find('.modal-content').first().length ? $modal.find('.modal-content').first() : $modal);
    }

    // prepare modals once (avoid being clipped by overflow:hidden page shell)
    ['pm-new-modal', 'pm-dm-modal', 'pm-group-modal', 'pm-profile-modal', 'pm-delete-modal', 'pm-group-info-modal'].forEach(ensureModalOnBody);

    window.confirmPrimeChatDelete = function (options) {
        options = options || {};
        var isGroup = !!options.isGroup;
        var $modal = $('#pm-delete-modal');
        if (!$modal.length || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            if (window.confirm(isGroup ? 'Покинуть этот чат?' : 'Удалить чат из списка?')) {
                if (typeof options.onConfirm === 'function') {
                    options.onConfirm();
                }
            }
            return;
        }

        ensureModalOnBody('pm-delete-modal');
        $('#pm-delete-modal-title').text(isGroup ? 'Покинуть чат?' : 'Удалить чат?');
        $('#pm-delete-modal-text').text(
            isGroup
                ? 'Вы точно хотите покинуть этот групповой чат?'
                : 'Чат исчезнет из вашего списка. История останется у собеседника.'
        );
        $('#pm-delete-modal-confirm').text(isGroup ? 'Покинуть' : 'Удалить');

        $('#pm-delete-modal-confirm').off('click.pmDelete').on('click.pmDelete', function () {
            var inst = bootstrap.Modal.getInstance(document.getElementById('pm-delete-modal'));
            if (inst) {
                inst.hide();
            }
            if (typeof options.onConfirm === 'function') {
                options.onConfirm();
            }
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('pm-delete-modal')).show();
    };

    window.openPrimeStaffProfile = function (userId) {
        userId = parseInt(userId, 10) || 0;
        if (!userId) {
            return;
        }
        $('#pm-profile-body').html('<div class="prime-messenger-loading p20">Загрузка…</div>');
        showModal('pm-profile-modal');
        $.ajax({
            url: '<?php echo get_uri('chat/staff_profile'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {user_id: userId},
            success: function (result) {
                if (result && result.success && result.html) {
                    $('#pm-profile-body').html(result.html);
                    if (typeof window.primeFeatherReplace === 'function') {
                        window.primeFeatherReplace('#pm-profile-body');
                    } else {
                        feather.replace();
                    }
                } else {
                    hideModal('pm-profile-modal');
                    appAlert.error((result && result.message) ? result.message : 'Не удалось открыть профиль');
                }
            },
            error: function () {
                hideModal('pm-profile-modal');
                appAlert.error('Не удалось открыть профиль');
            }
        });
    };

    $(document).on('click', '.js-pm-open-profile', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        window.openPrimeStaffProfile($(this).attr('data-user-id'));
    });

    $(document).on('keydown', '.prime-chat-header-user.js-pm-open-profile', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            window.openPrimeStaffProfile($(this).attr('data-user-id'));
        }
    });

    window.openPrimeGroupInfo = function (conversationId) {
        conversationId = parseInt(conversationId, 10) || 0;
        if (!conversationId) {
            return;
        }
        ensureModalOnBody('pm-group-info-modal');
        $('#pm-group-info-body').html('<div class="prime-messenger-loading p20">Загрузка…</div>');
        showModal('pm-group-info-modal');
        $.ajax({
            url: '<?php echo get_uri('chat/group_info'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {conversation_id: conversationId},
            success: function (result) {
                if (!result || !result.success || !result.html) {
                    hideModal('pm-group-info-modal');
                    appAlert.error((result && result.message) || 'Не удалось открыть группу');
                    return;
                }
                $('#pm-group-info-body').html(result.html);
                if (typeof window.primeFeatherReplace === 'function') {
                    window.primeFeatherReplace('#pm-group-info-body');
                }
                if (typeof result.members_count !== 'undefined') {
                    $('.prime-chat-thread[data-conversation-id="' + conversationId + '"] .js-pm-group-members-count')
                        .text(result.members_count + ' <?php echo app_lang('chat_members'); ?>');
                }
            },
            error: function () {
                hideModal('pm-group-info-modal');
                appAlert.error('Не удалось открыть группу');
            }
        });
    };

    function applyGroupAvatarToUi(conversationId, headerHtml, listHtml) {
        conversationId = parseInt(conversationId, 10) || 0;
        if (!conversationId) {
            return;
        }
        var $headerSlot = $('.js-pm-group-avatar-slot[data-conversation-id="' + conversationId + '"]');
        if ($headerSlot.length && headerHtml) {
            $headerSlot.html(headerHtml);
        }
        var $listSlot = $('#pm-list .js-pm-open[data-id="' + conversationId + '"] .js-pm-group-avatar-slot');
        if ($listSlot.length && listHtml) {
            $listSlot.html(listHtml);
        }
        if (typeof window.primeFeatherReplace === 'function') {
            window.primeFeatherReplace('.js-pm-group-avatar-slot[data-conversation-id="' + conversationId + '"]');
            window.primeFeatherReplace('#pm-list .js-pm-open[data-id="' + conversationId + '"]');
            window.primeFeatherReplace('#pm-group-info-body');
        }
    }

    $(document).on('click', '.js-pm-open-group-info', function (e) {
        e.preventDefault();
        e.stopPropagation();
        window.openPrimeGroupInfo($(this).attr('data-conversation-id') || $('.prime-chat-thread').attr('data-conversation-id'));
    });

    $(document).on('keydown', '.prime-chat-header-user.js-pm-open-group-info', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            window.openPrimeGroupInfo($(this).attr('data-conversation-id'));
        }
    });

    $(document).on('click', '.js-pm-group-icon', function (e) {
        e.preventDefault();
        var icon = $(this).attr('data-icon');
        var conversationId = $(this).closest('.pm-group-info').attr('data-conversation-id');
        if (!icon || !conversationId) {
            return;
        }
        var $btn = $(this);
        $.ajax({
            url: '<?php echo get_uri('chat/set_group_avatar'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {conversation_id: conversationId, icon: icon},
            success: function (result) {
                if (!result || !result.success) {
                    appAlert.error((result && result.message) || 'Error');
                    return;
                }
                $('.js-pm-group-icon').removeClass('is-active');
                $btn.addClass('is-active');
                applyGroupAvatarToUi(conversationId, result.avatar_html, result.avatar_html_list);
                if (result.avatar_html) {
                    $('.js-pm-group-avatar-preview').html(result.avatar_html);
                    if (typeof window.primeFeatherReplace === 'function') {
                        window.primeFeatherReplace('.js-pm-group-avatar-preview');
                    }
                }
                appAlert.success(result.message || 'Иконка обновлена');
            }
        });
    });

    $(document).on('change', '.js-pm-group-avatar-file', function () {
        var file = this.files && this.files[0];
        var conversationId = $(this).closest('.pm-group-info').attr('data-conversation-id');
        this.value = '';
        if (!file || !conversationId) {
            return;
        }
        var fd = new FormData();
        fd.append('conversation_id', conversationId);
        fd.append('avatar_file', file);
        $.ajax({
            url: '<?php echo get_uri('chat/set_group_avatar'); ?>',
            type: 'POST',
            dataType: 'json',
            data: fd,
            processData: false,
            contentType: false,
            success: function (result) {
                if (!result || !result.success) {
                    appAlert.error((result && result.message) || 'Error');
                    return;
                }
                $('.js-pm-group-icon').removeClass('is-active');
                applyGroupAvatarToUi(conversationId, result.avatar_html, result.avatar_html_list);
                if (result.avatar_html) {
                    $('.js-pm-group-avatar-preview').html(result.avatar_html);
                }
                appAlert.success(result.message || 'Иконка обновлена');
            },
            error: function () {
                appAlert.error('Не удалось загрузить изображение');
            }
        });
    });

    $(document).on('click', '.js-pm-group-avatar-clear', function (e) {
        e.preventDefault();
        var conversationId = $(this).closest('.pm-group-info').attr('data-conversation-id');
        if (!conversationId) {
            return;
        }
        $.ajax({
            url: '<?php echo get_uri('chat/set_group_avatar'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {conversation_id: conversationId, clear: 1},
            success: function (result) {
                if (!result || !result.success) {
                    appAlert.error((result && result.message) || 'Error');
                    return;
                }
                $('.js-pm-group-icon').removeClass('is-active');
                applyGroupAvatarToUi(conversationId, result.avatar_html, result.avatar_html_list);
                if (result.avatar_html) {
                    $('.js-pm-group-avatar-preview').html(result.avatar_html);
                    if (typeof window.primeFeatherReplace === 'function') {
                        window.primeFeatherReplace('.js-pm-group-avatar-preview');
                    }
                }
                appAlert.success(result.message || 'Иконка сброшена');
            }
        });
    });

    $(document).on('click', '.js-pm-toggle-invite', function (e) {
        e.preventDefault();
        var $panel = $('#pm-group-invite-panel');
        if (!$panel.length) {
            $panel = $(this).closest('.pm-group-info-section').find('.pm-group-invite-panel');
        }
        $panel.toggleClass('hide');
        if (!$panel.hasClass('hide') && typeof window.primeFeatherReplace === 'function') {
            window.primeFeatherReplace($panel);
        }
    });

    $(document).on('input', '.js-pm-invite-filter', function () {
        filterInvitePanel($(this).closest('.pm-group-invite-panel'));
    });

    function filterInvitePanel($panel) {
        if (!$panel || !$panel.length) {
            return;
        }
        var q = $.trim($panel.find('.js-pm-invite-filter').val() || '').toLowerCase();
        var jobs = getSelectedJobs($panel);

        $panel.find('.pm-invite-group').each(function () {
            var $group = $(this);
            var visibleCount = 0;
            $group.find('.pm-group-invite-item').each(function () {
                var $item = $(this);
                var title = String($item.attr('data-title') || '');
                var itemJob = String($item.attr('data-job') || '').toLowerCase();
                var matchText = !q || title.indexOf(q) !== -1;
                var matchJob = !jobs.length || jobs.indexOf(itemJob) !== -1;
                var show = matchText && matchJob;
                $item.toggle(show);
                if (show) {
                    visibleCount++;
                }
            });
            $group.toggle(visibleCount > 0);
            $group.find('.pm-people-group-head em').text(visibleCount);
        });
    }

    function appendChatSystemHtml(conversationId, html, messageId) {
        var $thread = $('.prime-chat-thread[data-conversation-id="' + conversationId + '"]');
        if (!$thread.length || !html) {
            return;
        }
        var $messages = $thread.find('#prime-chat-messages');
        if (!$messages.length) {
            return;
        }
        $messages.append(html);
        if (messageId && typeof $thread.attr === 'function') {
            var cur = Number($thread.attr('data-last-id')) || 0;
            if (messageId > cur) {
                $thread.attr('data-last-id', messageId);
            }
        }
        if (typeof window.primeFeatherReplace === 'function') {
            window.primeFeatherReplace($messages);
        }
        var el = $messages.get(0);
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }

    $(document).on('click', '.js-pm-add-members', function (e) {
        e.preventDefault();
        var $info = $(this).closest('.pm-group-info');
        var conversationId = $info.attr('data-conversation-id');
        var ids = [];
        $info.find('.js-pm-invite-member:checked').each(function () {
            ids.push($(this).val());
        });
        if (!conversationId || !ids.length) {
            appAlert.error('Выберите хотя бы одного сотрудника');
            return;
        }
        $.ajax({
            url: '<?php echo get_uri('chat/add_members'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {conversation_id: conversationId, member_ids: ids},
            success: function (result) {
                if (!result || !result.success) {
                    appAlert.error((result && result.message) || 'Error');
                    return;
                }
                if (result.info_html) {
                    $('#pm-group-info-body').html(result.info_html);
                    if (typeof window.primeFeatherReplace === 'function') {
                        window.primeFeatherReplace('#pm-group-info-body');
                    }
                }
                if (typeof result.members_count !== 'undefined') {
                    $('.prime-chat-thread[data-conversation-id="' + conversationId + '"] .js-pm-group-members-count')
                        .text(result.members_count + ' <?php echo app_lang('chat_members'); ?>');
                }
                appendChatSystemHtml(conversationId, result.html, result.message_id);
                appAlert.success(result.message || 'Участники добавлены');
            }
        });
    });

    $(document).on('click', '.js-pm-remove-member', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        var userId = $btn.attr('data-user-id');
        var conversationId = $btn.closest('.pm-group-info').attr('data-conversation-id');
        if (!userId || !conversationId) {
            return;
        }
        if (!window.confirm('Исключить участника из группы?')) {
            return;
        }
        $.ajax({
            url: '<?php echo get_uri('chat/remove_member'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {conversation_id: conversationId, user_id: userId},
            success: function (result) {
                if (!result || !result.success) {
                    appAlert.error((result && result.message) || 'Error');
                    return;
                }
                $btn.closest('.pm-group-member').remove();
                if (typeof result.members_count !== 'undefined') {
                    $('.prime-chat-thread[data-conversation-id="' + conversationId + '"] .js-pm-group-members-count')
                        .text(result.members_count + ' <?php echo app_lang('chat_members'); ?>');
                    $('.pm-group-info-sub').text(result.members_count + ' <?php echo app_lang('chat_members'); ?>');
                }
                appendChatSystemHtml(conversationId, result.html, result.message_id);
                appAlert.success(result.message || 'Участник исключён');
            }
        });
    });

    $(document).on('click', '.js-pm-profile-message', function (e) {
        e.preventDefault();
        var userId = $(this).attr('data-user-id');
        hideModal('pm-profile-modal');
        if (!userId) {
            return;
        }
        $.ajax({
            url: '<?php echo get_uri('chat/start_dm'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {user_id: userId},
            success: function (result) {
                if (result && result.success) {
                    window.openPrimeConversation(result.conversation_id);
                } else {
                    appAlert.error((result && result.message) ? result.message : 'Не удалось открыть чат');
                }
            }
        });
    });

    // hide floating chat bubble on full messenger page
    $('#js-init-chat-icon, #js-rise-chat-wrapper').addClass('hide');

    function setActive(id) {
        $('.js-pm-open').removeClass('active');
        $('.js-pm-open[data-id="' + id + '"]').addClass('active').removeClass('is-unread').attr('data-unread', '0').find('em').remove();
    }

    window.openPrimeConversation = function (conversationId) {
        conversationId = parseInt(conversationId, 10) || 0;
        if (!conversationId) {
            return;
        }

        if (window.primeChatPollTimer) {
            clearInterval(window.primeChatPollTimer);
            window.primeChatPollTimer = null;
        }

        setActive(conversationId);
        hidePmSettings();
        $('#pm-placeholder').addClass('hide');
        $('#pm-thread').removeClass('hide').html('<div class="prime-messenger-loading">Загрузка…</div>');

        $.ajax({
            url: '<?php echo get_uri('chat/conversation'); ?>',
            type: 'POST',
            data: {conversation_id: conversationId, layout: 'page'},
            success: function (response) {
                // API errors come as JSON with HTTP 200
                if (typeof response === 'object' && response && response.success === false) {
                    appAlert.error(response.message || 'Не удалось открыть чат');
                    $('#pm-thread').addClass('hide').empty();
                    $('#pm-placeholder').removeClass('hide');
                    $('.js-pm-open').removeClass('active');
                    history.replaceState(null, '', '<?php echo get_uri('messages/inbox'); ?>');
                    return;
                }
                if (typeof response === 'string') {
                    var trimmed = $.trim(response);
                    if (trimmed.charAt(0) === '{') {
                        try {
                            var parsed = JSON.parse(trimmed);
                            if (parsed && parsed.success === false) {
                                appAlert.error(parsed.message || 'Не удалось открыть чат');
                                $('#pm-thread').addClass('hide').empty();
                                $('#pm-placeholder').removeClass('hide');
                                $('.js-pm-open').removeClass('active');
                                history.replaceState(null, '', '<?php echo get_uri('messages/inbox'); ?>');
                                return;
                            }
                        } catch (e) {}
                    }
                }

                $('#pm-thread').html(response);
                if (typeof window.primeCloseEmojiPicker === 'function') {
                    window.primeCloseEmojiPicker();
                }
                if (!$('#pm-list .js-pm-open[data-id="' + conversationId + '"]').length) {
                    // restored after leave — reload list so chat appears
                    window.location.href = '<?php echo get_uri('messages/inbox'); ?>/' + conversationId;
                    return;
                }
                if (typeof window.primeFeatherReplace === 'function') {
                    window.primeFeatherReplace('#pm-thread');
                    setTimeout(function () { window.primeFeatherReplace('#pm-thread'); }, 50);
                } else if (typeof feather !== 'undefined') {
                    feather.replace();
                    setTimeout(function () { feather.replace(); }, 50);
                }
                history.replaceState(null, '', '<?php echo get_uri('messages/inbox'); ?>/' + conversationId);
                if (typeof window.primeChatScrollToStart === 'function') {
                    window.primeChatScrollToStart();
                } else {
                    var $msgs = $('#prime-chat-messages');
                    if ($msgs.length && $msgs[0]) {
                        $msgs.scrollTop($msgs[0].scrollHeight);
                    }
                }
            },
            error: function () {
                appAlert.error('Не удалось открыть чат');
                $('#pm-thread').addClass('hide').empty();
                $('#pm-placeholder').removeClass('hide');
                $('.js-pm-open').removeClass('active');
                history.replaceState(null, '', '<?php echo get_uri('messages/inbox'); ?>');
            }
        });
    };

    $(document).on('click', '#pm-list .js-pm-open', function (e) {
        if ($(e.target).closest('.js-pm-star, a, button').length) {
            return;
        }
        window.openPrimeConversation($(this).data('id'));
    });

    $(document).on('keydown', '#pm-list .js-pm-open', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            if ($(e.target).closest('.js-pm-star, button').length) {
                return;
            }
            e.preventDefault();
            window.openPrimeConversation($(this).data('id'));
        }
    });

    ensureModalOnBody('pm-list-ctx');
    var $pmCtx = $('#pm-list-ctx');
    var pmCtxTarget = null;

    function hidePmListCtx() {
        $pmCtx.addClass('hide').attr('aria-hidden', 'true');
        pmCtxTarget = null;
        $('#pm-list .js-pm-open').removeClass('is-ctx-open');
    }

    function showPmListCtx($item, clientX, clientY) {
        pmCtxTarget = $item;
        $('#pm-list .js-pm-open').removeClass('is-ctx-open');
        $item.addClass('is-ctx-open');

        var isGroup = $item.attr('data-type') === 'group';
        var peerId = $item.attr('data-peer-id') || '';
        var unread = $item.attr('data-unread') === '1';
        var starred = $item.attr('data-starred') === '1';

        $pmCtx.find('.js-pm-ctx-profile').toggleClass('hide', isGroup || !peerId);
        $pmCtx.find('.js-pm-ctx-read').toggleClass('hide', !unread);
        $pmCtx.find('.js-pm-ctx-star').text(starred ? 'Убрать из избранного' : 'В избранное');
        $pmCtx.find('.js-pm-ctx-delete').text(isGroup ? 'Покинуть чат' : 'Удалить чат');

        $pmCtx.removeClass('hide').attr('aria-hidden', 'false');

        var menuW = $pmCtx.outerWidth() || 200;
        var menuH = $pmCtx.outerHeight() || 180;
        var left = clientX;
        var top = clientY;
        if (left + menuW > window.innerWidth - 8) {
            left = window.innerWidth - menuW - 8;
        }
        if (top + menuH > window.innerHeight - 8) {
            top = window.innerHeight - menuH - 8;
        }
        if (left < 8) {
            left = 8;
        }
        if (top < 8) {
            top = 8;
        }
        $pmCtx.css({ left: left + 'px', top: top + 'px' });
    }

    function closeThreadIfOpen(conversationId) {
        var openId = parseInt($('.prime-chat-thread').attr('data-conversation-id'), 10) || 0;
        if (openId !== conversationId) {
            return;
        }
        if (window.primeChatPollTimer) {
            clearInterval(window.primeChatPollTimer);
            window.primeChatPollTimer = null;
        }
        $('#pm-thread').addClass('hide').empty();
        hidePmSettings();
        $('#pm-placeholder').removeClass('hide');
        history.replaceState(null, '', '<?php echo get_uri('messages/inbox'); ?>');
    }

    function markListItemRead($item) {
        $item.removeClass('is-unread').attr('data-unread', '0').find('.prime-messenger-item-preview em').remove();
        filterList();
    }

    function placeStarredItem($item) {
        sortList();
        filterList();
    }

    function applyStarState($item, starred) {
        starred = !!starred;
        $item.toggleClass('is-starred', starred).attr('data-starred', starred ? '1' : '0');
        $item.find('.js-pm-star')
            .toggleClass('is-on', starred)
            .attr('title', starred ? 'Убрать из избранного' : 'В избранное')
            .attr('aria-label', starred ? 'Убрать из избранного' : 'В избранное');
        placeStarredItem($item);
    }

    function toggleConversationStar(conversationId, $item) {
        conversationId = parseInt(conversationId, 10) || 0;
        if (!conversationId) {
            return;
        }
        if (!$item || !$item.length) {
            $item = $('#pm-list .js-pm-open[data-id="' + conversationId + '"]');
        }
        $.ajax({
            url: '<?php echo get_uri('chat/toggle_star'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {conversation_id: conversationId},
            success: function (result) {
                if (!result || !result.success) {
                    appAlert.error((result && result.message) || 'Error');
                    return;
                }
                if ($item.length) {
                    applyStarState($item, !!result.starred);
                }
            },
            error: function () {
                appAlert.error('Не удалось обновить избранное');
            }
        });
    }

    $(document).on('click', '.js-pm-star', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        var $btn = $(this);
        toggleConversationStar($btn.attr('data-id') || $btn.closest('.js-pm-open').attr('data-id'), $btn.closest('.js-pm-open'));
    });

    $(document).on('contextmenu', '#pm-list .js-pm-open', function (e) {
        e.preventDefault();
        e.stopPropagation();
        showPmListCtx($(this), e.clientX, e.clientY);
    });

    $(document).on('mousedown', function (e) {
        if (!$pmCtx.hasClass('hide') && !$(e.target).closest('#pm-list-ctx').length) {
            hidePmListCtx();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            hidePmListCtx();
        }
    });

    $(window).on('scroll blur resize', hidePmListCtx);
    $('#pm-list').on('scroll', hidePmListCtx);

    $(document).on('click', '#pm-list-ctx .pm-list-ctx-item', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!pmCtxTarget || !pmCtxTarget.length) {
            hidePmListCtx();
            return;
        }

        var action = $(this).attr('data-action');
        var $item = pmCtxTarget;
        var conversationId = parseInt($item.attr('data-id'), 10) || 0;
        var peerId = $item.attr('data-peer-id') || '';
        var isGroup = $item.attr('data-type') === 'group';
        hidePmListCtx();

        if (!conversationId) {
            return;
        }

        if (action === 'open') {
            window.openPrimeConversation(conversationId);
            return;
        }

        if (action === 'profile' && peerId) {
            window.openPrimeStaffProfile(peerId);
            return;
        }

        if (action === 'read') {
            $.ajax({
                url: '<?php echo get_uri('chat/mark_read'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {conversation_id: conversationId},
                success: function (result) {
                    if (result && result.success) {
                        markListItemRead($item);
                    }
                }
            });
            return;
        }

        if (action === 'star') {
            toggleConversationStar(conversationId, $item);
            return;
        }

        if (action === 'clear') {
            if (!window.confirm('Очистить всю историю этого чата? Сообщения исчезнут у всех участников.')) {
                return;
            }
            $.ajax({
                url: '<?php echo get_uri('chat/clear_history'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {conversation_id: conversationId},
                success: function (result) {
                    if (!result || !result.success) {
                        appAlert.error((result && result.message) || 'Error');
                        return;
                    }
                    $item.find('.prime-messenger-item-preview > span').text('Нет сообщений');
                    var openId = parseInt($('.prime-chat-thread').attr('data-conversation-id'), 10) || 0;
                    if (openId === conversationId) {
                        $('#prime-chat-messages').empty();
                        $('#prime-chat-pins-wrap').empty();
                        $('.prime-chat-thread').attr('data-last-id', '0');
                    }
                    appAlert.success(result.message || 'История очищена');
                },
                error: function () {
                    appAlert.error('Не удалось очистить историю');
                }
            });
            return;
        }

        if (action === 'delete') {
            window.confirmPrimeChatDelete({
                isGroup: isGroup,
                onConfirm: function () {
                    $.ajax({
                        url: '<?php echo get_uri('chat/delete_conversation'); ?>',
                        type: 'POST',
                        dataType: 'json',
                        data: {conversation_id: conversationId},
                        success: function (result) {
                            if (!result || !result.success) {
                                appAlert.error((result && result.message) || 'Error');
                                return;
                            }
                            closeThreadIfOpen(conversationId);
                            $item.remove();
                            if (!$('#pm-list .js-pm-open').length) {
                                $('#pm-list').html(
                                    '<div class="prime-messenger-empty-list">' +
                                    '<div class="pm-empty-title"><?php echo app_lang('no_chats_yet'); ?></div>' +
                                    '</div>'
                                );
                            }
                            appAlert.success(result.message || 'Чат удалён');
                        },
                        error: function () {
                            appAlert.error('Не удалось удалить чат');
                        }
                    });
                }
            });
        }
    });

    $(document).on('click', '.js-pm-start-dm', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var userId = $(this).attr('data-user-id');
        if (!userId) {
            appAlert.error('Не выбран сотрудник');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: '<?php echo get_uri('chat/start_dm'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {user_id: userId},
            success: function (result) {
                if (result && result.success) {
                    window.location.href = '<?php echo get_uri('messages/inbox'); ?>/' + result.conversation_id;
                } else {
                    $btn.prop('disabled', false);
                    appAlert.error((result && result.message) ? result.message : 'Не удалось открыть чат');
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                var msg = 'Ошибка запроса';
                try {
                    var parsed = JSON.parse(xhr.responseText);
                    if (parsed && parsed.message) {
                        msg = parsed.message;
                    }
                } catch (e) {}
                if (xhr.status) {
                    msg += ' (' + xhr.status + ')';
                }
                appAlert.error(msg);
            }
        });
    });

    $('#pm-create-group').on('click', function () {
        var title = $.trim($('#pm-group-title').val());
        var memberIds = [];
        $('.js-pm-group-member:checked').each(function () {
            memberIds.push($(this).val());
        });

        if (!title || !memberIds.length) {
            appAlert.error('Укажите название и участников');
            return;
        }

        $.ajax({
            url: '<?php echo get_uri('chat/create_group'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {title: title, member_ids: memberIds},
            success: function (result) {
                if (result.success) {
                    window.location.href = '<?php echo get_uri('messages/inbox'); ?>/' + result.conversation_id;
                } else {
                    appAlert.error(result.message || 'Error');
                }
            }
        });
    });

    if (openId) {
        window.openPrimeConversation(openId);
    }
});
</script>
