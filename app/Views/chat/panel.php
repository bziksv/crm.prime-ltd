<?php
$login_user = $login_user ?? null;
$conversations = $conversations ?? array();
$staff_users = $staff_users ?? array();
$inbox_uri = get_uri('messages/inbox');
?>
<div class="prime-chat prime-chat-widget">
    <div class="prime-chat-header">
        <div class="prime-chat-header-title"><?php echo app_lang('chat'); ?></div>
        <div class="prime-chat-header-actions">
            <a href="<?php echo $inbox_uri; ?>" class="prime-chat-icon-btn" id="prime-chat-open-full" title="Открыть мессенджер" aria-label="Открыть мессенджер">
                <i data-feather="maximize-2" class="icon-16"></i>
            </a>
            <button type="button" class="prime-chat-icon-btn" id="prime-chat-new-dm" title="<?php echo app_lang('start_chat'); ?>">
                <i data-feather="edit" class="icon-16"></i>
            </button>
            <button type="button" class="prime-chat-icon-btn" id="prime-chat-new-group" title="<?php echo app_lang('new_group'); ?>">
                <i data-feather="users" class="icon-16"></i>
            </button>
        </div>
    </div>

    <div class="prime-chat-search">
        <input type="text" id="prime-chat-filter" class="form-control" placeholder="Найти сотрудника или чат" autocomplete="off">
    </div>

    <div class="prime-chat-chips">
        <button type="button" class="pm-chip active js-prime-chat-type-filter" data-filter="all">Все</button>
        <button type="button" class="pm-chip js-prime-chat-type-filter" data-filter="unread">Непрочитанные</button>
        <button type="button" class="pm-chip js-prime-chat-type-filter" data-filter="dm">Личные</button>
        <button type="button" class="pm-chip js-prime-chat-type-filter" data-filter="group">Группы</button>
    </div>

    <div class="prime-chat-list" id="prime-chat-list">
        <?php if ($conversations) { ?>
            <?php foreach ($conversations as $item) { ?>
                <?php
                $unread = (int) ($item->unread_count ?? 0);
                $time = $item->last_message_at ?: $item->updated_at;
                $is_group = $item->type === 'group';
                $sender = '';
                if ($is_group && !empty($item->last_sender_first_name)) {
                    $sender = $item->last_sender_first_name . ': ';
                }
                ?>
                <div class="prime-chat-row js-prime-chat-open <?php echo $unread ? 'is-unread' : ''; ?>"
                     role="button" tabindex="0"
                     data-id="<?php echo (int) $item->id; ?>"
                     data-type="<?php echo esc($item->type); ?>"
                     data-unread="<?php echo $unread ? '1' : '0'; ?>"
                     data-title="<?php echo esc(mb_strtolower($item->display_title . ' ' . ($item->preview ?? ''))); ?>">
                    <div class="prime-chat-avatar">
                        <?php if ($is_group) {
                            echo prime_chat_group_avatar_html($item->display_image ?? '', 'sm');
                        } else { ?>
                            <img src="<?php echo get_avatar($item->display_image); ?>" alt="">
                            <?php if (!empty($item->peer_last_online) && is_online_user($item->peer_last_online)) { ?>
                                <i class="online"></i>
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <div class="prime-chat-row-body">
                        <div class="prime-chat-row-top">
                            <strong><?php echo esc($item->display_title); ?></strong>
                            <span class="prime-chat-time"><?php echo $time ? format_to_relative_time($time) : ''; ?></span>
                        </div>
                        <div class="prime-chat-row-preview">
                            <span><?php echo $item->preview ? esc($sender . $item->preview) : '—'; ?></span>
                            <?php if ($unread) { ?>
                                <span class="prime-chat-badge"><?php echo $unread; ?></span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="prime-chat-empty">
                <i data-feather="message-circle" class="icon-24"></i>
                <div><?php echo app_lang('no_chats_yet'); ?></div>
                <button type="button" class="btn btn-primary btn-sm mt10" id="prime-chat-empty-start">
                    <?php echo app_lang('start_chat'); ?>
                </button>
                <a href="<?php echo $inbox_uri; ?>" class="btn btn-default btn-sm mt10">Открыть мессенджер</a>
            </div>
        <?php } ?>
    </div>

    <div class="prime-chat-widget-foot">
        <a href="<?php echo $inbox_uri; ?>" class="btn btn-default btn-sm w-100" id="prime-chat-goto-inbox">
            <i data-feather="external-link" class="icon-14"></i>
            Открыть в полном окне
        </a>
    </div>

    <div class="prime-chat-composer-sheet hide" id="prime-chat-dm-sheet">
        <div class="prime-chat-sheet-header">
            <button type="button" class="prime-chat-icon-btn js-prime-chat-sheet-close"><i data-feather="arrow-left" class="icon-16"></i></button>
            <strong><?php echo app_lang('start_chat'); ?></strong>
        </div>
        <div class="prime-chat-search">
            <input type="text" class="form-control js-prime-people-filter" placeholder="<?php echo app_lang('search_people'); ?>…" autocomplete="off">
        </div>
        <div class="prime-chat-people">
            <?php foreach ($staff_users as $user) { ?>
                <div class="prime-chat-row js-prime-start-dm"
                     data-user-id="<?php echo (int) $user->id; ?>"
                     data-title="<?php echo esc(mb_strtolower($user->first_name . ' ' . $user->last_name)); ?>">
                    <div class="prime-chat-avatar">
                        <img src="<?php echo get_avatar($user->image); ?>" alt="">
                        <?php if ($user->last_online && is_online_user($user->last_online)) { ?>
                            <i class="online"></i>
                        <?php } ?>
                    </div>
                    <div class="prime-chat-row-body">
                        <strong><?php echo esc($user->first_name . ' ' . $user->last_name); ?></strong>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="prime-chat-composer-sheet hide" id="prime-chat-group-sheet">
        <div class="prime-chat-sheet-header">
            <button type="button" class="prime-chat-icon-btn js-prime-chat-sheet-close"><i data-feather="arrow-left" class="icon-16"></i></button>
            <strong><?php echo app_lang('new_group'); ?></strong>
        </div>
        <div class="p10">
            <input type="text" id="prime-chat-group-title" class="form-control mb10" placeholder="<?php echo app_lang('chat_group_name'); ?>" autocomplete="off">
            <div class="text-off mb10"><?php echo app_lang('chat_members'); ?></div>
            <input type="text" class="form-control mb10 js-prime-people-filter" placeholder="<?php echo app_lang('search_people'); ?>…" autocomplete="off">
            <div class="prime-chat-people prime-chat-people-check">
                <?php foreach ($staff_users as $user) { ?>
                    <label class="prime-chat-row"
                           data-title="<?php echo esc(mb_strtolower($user->first_name . ' ' . $user->last_name)); ?>">
                        <div class="prime-chat-avatar">
                            <img src="<?php echo get_avatar($user->image); ?>" alt="">
                        </div>
                        <div class="prime-chat-row-body">
                            <strong><?php echo esc($user->first_name . ' ' . $user->last_name); ?></strong>
                        </div>
                        <input type="checkbox" class="js-prime-group-member" value="<?php echo (int) $user->id; ?>">
                    </label>
                <?php } ?>
            </div>
            <button type="button" class="btn btn-primary w-100 mt10" id="prime-chat-create-group"><?php echo app_lang('save'); ?></button>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        var inboxBase = <?php echo json_encode($inbox_uri); ?>;
        var activeFilters = { unread: false, dm: false, group: false };

        if (typeof window.openPrimeConversation !== 'function') {
            window.openPrimeConversation = function (conversationId) {
                conversationId = parseInt(conversationId, 10) || 0;
                if (!conversationId) {
                    window.location.href = inboxBase;
                    return;
                }
                window.location.href = inboxBase.replace(/\/$/, '') + '/' + conversationId;
            };
        }

        function filterPeople($root, value) {
            value = (value || '').toLowerCase();
            $root.find('[data-title]').each(function () {
                var title = ($(this).attr('data-title') || '');
                $(this).toggle(!value || title.indexOf(value) !== -1);
            });
        }

        function hasAnyTypeFilter() {
            return activeFilters.dm || activeFilters.group;
        }

        function hasAnyFilter() {
            return activeFilters.unread || activeFilters.dm || activeFilters.group;
        }

        function syncFilterChips() {
            var any = hasAnyFilter();
            $('.js-prime-chat-type-filter').each(function () {
                var key = $(this).attr('data-filter');
                if (key === 'all') {
                    $(this).toggleClass('active', !any);
                } else {
                    $(this).toggleClass('active', !!activeFilters[key]);
                }
            });
        }

        function filterList() {
            var q = ($('#prime-chat-filter').val() || '').toLowerCase();
            var typeFiltered = hasAnyTypeFilter();

            $('#prime-chat-list .js-prime-chat-open').each(function () {
                var $el = $(this);
                var title = ($el.attr('data-title') || '');
                var type = $el.attr('data-type');
                var unread = $el.attr('data-unread') === '1';
                var matchText = !q || title.indexOf(q) !== -1;
                var matchUnread = !activeFilters.unread || unread;
                var matchType = true;
                if (typeFiltered) {
                    matchType = (activeFilters.dm && type === 'dm') || (activeFilters.group && type === 'group');
                }
                $el.toggle(matchText && matchUnread && matchType);
            });
        }

        $('#prime-chat-filter').on('input', filterList);

        $(document).off('click.primeChatWidgetFilter').on('click.primeChatWidgetFilter', '.js-prime-chat-type-filter', function (e) {
            e.preventDefault();
            var filter = $(this).attr('data-filter');
            if (filter === 'all') {
                activeFilters.unread = false;
                activeFilters.dm = false;
                activeFilters.group = false;
            } else if (activeFilters.hasOwnProperty(filter)) {
                activeFilters[filter] = !activeFilters[filter];
            }
            syncFilterChips();
            filterList();
        });

        $('.js-prime-people-filter').on('input', function () {
            filterPeople($(this).closest('.prime-chat-composer-sheet').find('.prime-chat-people'), $(this).val());
        });

        function openSheet(id) {
            $('.prime-chat-composer-sheet').addClass('hide');
            $(id).removeClass('hide');
        }

        function closeSheets() {
            $('.prime-chat-composer-sheet').addClass('hide');
        }

        $('#prime-chat-new-dm, #prime-chat-empty-start').on('click', function () {
            openSheet('#prime-chat-dm-sheet');
        });

        $('#prime-chat-new-group').on('click', function () {
            openSheet('#prime-chat-group-sheet');
        });

        $('.js-prime-chat-sheet-close').on('click', closeSheets);

        $('.js-prime-chat-open').on('click', function () {
            window.openPrimeConversation($(this).data('id'));
        });

        $('.js-prime-start-dm').on('click', function () {
            var userId = $(this).data('user-id');
            appLoader.show({container: '#js-rise-chat-wrapper', css: 'bottom: 40%; right: 35%;'});
            $.ajax({
                url: '<?php echo get_uri('chat/start_dm'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {user_id: userId},
                success: function (result) {
                    appLoader.hide();
                    if (result.success) {
                        window.openPrimeConversation(result.conversation_id);
                    } else {
                        appAlert.error(result.message || 'Error');
                    }
                },
                error: function () {
                    appLoader.hide();
                    appAlert.error('Error');
                }
            });
        });

        $('#prime-chat-create-group').on('click', function () {
            var title = $.trim($('#prime-chat-group-title').val());
            var memberIds = [];
            $('.js-prime-group-member:checked').each(function () {
                memberIds.push($(this).val());
            });

            if (!title || !memberIds.length) {
                appAlert.error('Укажите название и участников');
                return;
            }

            appLoader.show({container: '#js-rise-chat-wrapper', css: 'bottom: 40%; right: 35%;'});
            $.ajax({
                url: '<?php echo get_uri('chat/create_group'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {title: title, member_ids: memberIds},
                success: function (result) {
                    appLoader.hide();
                    if (result.success) {
                        window.openPrimeConversation(result.conversation_id);
                    } else {
                        appAlert.error(result.message || 'Error');
                    }
                },
                error: function () {
                    appLoader.hide();
                    appAlert.error('Error');
                }
            });
        });
    });
</script>
