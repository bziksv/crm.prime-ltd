<?php
$avatar = $conversation->display_image ?? ($conversation->avatar ?? '');
$current_icon = (strpos((string) $avatar, 'icon:') === 0) ? substr($avatar, 5) : '';
$invite_candidates = $invite_candidates ?? array();
$is_admin = !empty($is_admin);

$invite_groups = array();
$invite_job_options = array();
foreach ($invite_candidates as $user) {
    $job = trim((string) ($user->job_title ?? ''));
    if ($job === '' || $job === 'Untitled') {
        $job = 'Без должности';
    }
    $key = mb_strtolower($job === 'Без должности' ? '' : $job);
    if (!isset($invite_groups[$key])) {
        $invite_groups[$key] = array('label' => $job, 'users' => array());
    }
    $invite_groups[$key]['users'][] = $user;
}
uksort($invite_groups, function ($a, $b) use ($invite_groups) {
    return strnatcasecmp($invite_groups[$a]['label'], $invite_groups[$b]['label']);
});
foreach ($invite_groups as $key => $group) {
    if ($key === '') {
        continue;
    }
    $invite_job_options[] = array(
        'key' => $key,
        'label' => $group['label'],
        'count' => count($group['users']),
    );
}
?>
<div class="pm-group-info" data-conversation-id="<?php echo (int) $conversation->id; ?>">
    <div class="pm-group-info-hero">
        <div class="pm-group-info-avatar js-pm-group-avatar-preview">
            <?php echo prime_chat_group_avatar_html($avatar, 'lg'); ?>
        </div>
        <div>
            <div class="pm-group-info-title"><?php echo esc($conversation->display_title); ?></div>
            <div class="pm-group-info-sub"><?php echo count($members); ?> <?php echo app_lang('chat_members'); ?></div>
        </div>
    </div>

    <?php if ($is_admin) { ?>
        <div class="pm-group-info-section">
            <div class="pm-group-info-section-title">Иконка группы</div>
            <div class="pm-group-icon-grid">
                <?php foreach ($system_icons as $key => $meta) { ?>
                    <button type="button"
                        class="pm-group-icon-pick js-pm-group-icon <?php echo $current_icon === $key ? 'is-active' : ''; ?>"
                        data-icon="<?php echo esc($key); ?>"
                        title="<?php echo esc($meta['label']); ?>"
                        style="--pm-icon-color: <?php echo esc($meta['color']); ?>">
                        <i data-feather="<?php echo esc($key); ?>" class="icon-16"></i>
                    </button>
                <?php } ?>
            </div>
            <div class="pm-group-upload-row">
                <label class="btn btn-default btn-sm mb0">
                    Загрузить свою
                    <input type="file" class="hide js-pm-group-avatar-file" accept="image/png,image/jpeg,image/gif,image/webp">
                </label>
                <?php if ($avatar !== '') { ?>
                    <button type="button" class="btn btn-default btn-sm js-pm-group-avatar-clear">Сбросить</button>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <div class="pm-group-info-section">
        <div class="pm-group-info-head-row">
            <div class="pm-group-info-section-title mb0">Участники</div>
            <?php if ($is_admin) { ?>
                <button type="button" class="btn btn-primary btn-sm js-pm-toggle-invite">
                    <i data-feather="user-plus" class="icon-14"></i>
                    Добавить
                </button>
            <?php } ?>
        </div>

        <?php if ($is_admin) { ?>
            <div class="pm-group-invite-panel hide" id="pm-group-invite-panel">
                <?php if ($invite_candidates) { ?>
                    <div class="pm-people-filter-row mb10">
                        <input type="text" class="form-control form-control-sm js-pm-invite-filter" placeholder="Поиск сотрудников…">
                        <?php if ($invite_job_options) {
                            echo view('chat/job_filter', array('job_options' => $invite_job_options));
                        } ?>
                    </div>
                    <div class="pm-group-invite-list pm-people-grouped">
                        <?php foreach ($invite_groups as $job_key => $group) { ?>
                            <div class="pm-people-group pm-invite-group" data-job-group="<?php echo esc($job_key); ?>">
                                <div class="pm-people-group-head">
                                    <div class="pm-people-group-toggle is-static">
                                        <strong><?php echo esc($group['label']); ?></strong>
                                        <em><?php echo count($group['users']); ?></em>
                                    </div>
                                </div>
                                <?php foreach ($group['users'] as $user) {
                                    $name = trim($user->first_name . ' ' . $user->last_name);
                                    $job = trim((string) ($user->job_title ?? ''));
                                    if ($job === 'Untitled') {
                                        $job = '';
                                    }
                                    ?>
                                    <label class="pm-group-invite-item"
                                        data-job="<?php echo esc($job_key); ?>"
                                        data-title="<?php echo esc(mb_strtolower($name . ' ' . $job)); ?>">
                                        <input type="checkbox" class="js-pm-invite-member" value="<?php echo (int) $user->id; ?>">
                                        <img src="<?php echo get_avatar($user->image); ?>" alt="">
                                        <span>
                                            <strong><?php echo esc($name); ?></strong>
                                        </span>
                                    </label>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm mt10 w-100 js-pm-add-members">Добавить выбранных</button>
                <?php } else { ?>
                    <div class="pm-group-invite-empty">Все сотрудники уже в этой группе</div>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="pm-group-members">
            <?php foreach ($members as $member) {
                $name = trim($member->first_name . ' ' . $member->last_name);
                $job = trim((string) ($member->job_title ?? ''));
                if ($job === 'Untitled') {
                    $job = '';
                }
                $can_remove = $is_admin && ((int) $member->id !== (int) $login_user->id);
                ?>
                <div class="pm-group-member" data-user-id="<?php echo (int) $member->id; ?>">
                    <button type="button" class="pm-group-member-main js-pm-open-profile" data-user-id="<?php echo (int) $member->id; ?>">
                        <img src="<?php echo get_avatar($member->image); ?>" alt="">
                        <span>
                            <strong><?php echo esc($name); ?></strong>
                            <em>
                                <?php
                                if (!empty($member->is_admin)) {
                                    echo 'Админ';
                                    if ($job) {
                                        echo ' · ' . esc($job);
                                    }
                                } else if ($job) {
                                    echo esc($job);
                                } else {
                                    echo 'Участник';
                                }
                                ?>
                            </em>
                        </span>
                    </button>
                    <?php if ($can_remove) { ?>
                        <button type="button" class="pm-group-member-remove js-pm-remove-member" data-user-id="<?php echo (int) $member->id; ?>" title="Исключить">
                            <i data-feather="user-minus" class="icon-14"></i>
                        </button>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
