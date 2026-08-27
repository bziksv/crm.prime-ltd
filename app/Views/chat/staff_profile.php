<?php
$name = trim($user->first_name . ' ' . $user->last_name);
$job = trim((string) ($user->job_title ?? ''));
if ($job === 'Untitled') {
    $job = '';
}
$email = trim((string) ($user->email ?? ''));
$phone = trim((string) ($user->phone ?? ''));
$is_self = ((int) $login_user->id === (int) $user->id);
?>
<div class="pm-profile-card" data-user-id="<?php echo (int) $user->id; ?>">
    <div class="pm-profile-hero">
        <div class="pm-profile-hero-glow"></div>
        <div class="pm-profile-avatar-wrap">
            <img class="pm-profile-avatar" src="<?php echo get_avatar($user->image); ?>" alt="">
            <span class="pm-profile-online-dot <?php echo $online ? 'is-on' : ''; ?>" title="<?php echo $online ? 'online' : 'offline'; ?>"></span>
        </div>
        <h3 class="pm-profile-name"><?php echo esc($name); ?></h3>
        <?php if ($job) { ?>
            <div class="pm-profile-job"><?php echo esc($job); ?></div>
        <?php } ?>
        <div class="pm-profile-status <?php echo $online ? 'is-on' : ''; ?>">
            <?php echo $online ? 'В сети' : 'Не в сети'; ?>
        </div>
    </div>

    <div class="pm-profile-rows">
        <?php if ($email) { ?>
            <a class="pm-profile-row" href="mailto:<?php echo esc($email); ?>">
                <span class="pm-profile-row-ico"><i data-feather="mail" class="icon-16"></i></span>
                <span class="pm-profile-row-body">
                    <span class="pm-profile-row-label">Email</span>
                    <span class="pm-profile-row-value"><?php echo esc($email); ?></span>
                </span>
            </a>
        <?php } ?>
        <?php if ($phone) { ?>
            <a class="pm-profile-row" href="tel:<?php echo esc(preg_replace('/\s+/', '', $phone)); ?>">
                <span class="pm-profile-row-ico"><i data-feather="phone" class="icon-16"></i></span>
                <span class="pm-profile-row-body">
                    <span class="pm-profile-row-label">Телефон</span>
                    <span class="pm-profile-row-value"><?php echo esc($phone); ?></span>
                </span>
            </a>
        <?php } ?>
        <?php if (!$email && !$phone) { ?>
            <div class="pm-profile-empty text-off">Контакты не указаны</div>
        <?php } ?>
    </div>

    <div class="pm-profile-actions">
        <?php if (!$is_self) { ?>
            <button type="button" class="btn btn-primary w-100 js-pm-profile-message" data-user-id="<?php echo (int) $user->id; ?>">
                <i data-feather="message-circle" class="icon-16"></i> Написать
            </button>
        <?php } ?>
        <?php if (!empty($can_open_full)) { ?>
            <a class="btn btn-default w-100" href="<?php echo get_uri('team_members/view/' . $user->id); ?>">
                <i data-feather="user" class="icon-16"></i> Полный профиль
            </a>
        <?php } ?>
    </div>
</div>
