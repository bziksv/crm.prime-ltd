<?php
$layout = $layout ?? 'widget';
$is_page = $layout === 'page';
$last_id = 0;
foreach ($messages as $m) {
    $last_id = max($last_id, (int) $m->id);
}
$online = false;
if ($conversation->type === 'dm' && !empty($conversation->peer->last_online)) {
    $online = is_online_user($conversation->peer->last_online);
}
?>
<div class="prime-chat-thread <?php echo $is_page ? 'is-page' : ''; ?>"
    data-conversation-id="<?php echo $conversation->id; ?>"
    data-last-id="<?php echo $last_id; ?>"
    data-last-read-id="<?php echo (int) ($last_read_message_id ?? ($conversation->last_read_message_id ?? 0)); ?>">
    <div class="prime-chat-thread-header">
        <?php if (!$is_page) { ?>
            <button type="button" class="prime-chat-icon-btn" id="prime-chat-back">
                <i data-feather="arrow-left" class="icon-16"></i>
            </button>
        <?php } ?>
        <div class="prime-chat-header-user <?php echo ($conversation->type === 'dm' && !empty($conversation->peer->id)) ? 'js-pm-open-profile is-clickable' : ''; ?> <?php echo $conversation->type === 'group' ? 'js-pm-open-group-info is-clickable' : ''; ?>"
            <?php if ($conversation->type === 'dm' && !empty($conversation->peer->id)) { ?>
                data-user-id="<?php echo (int) $conversation->peer->id; ?>"
                title="Открыть профиль"
                role="button"
                tabindex="0"
            <?php } else if ($conversation->type === 'group') { ?>
                data-conversation-id="<?php echo (int) $conversation->id; ?>"
                title="Участники и настройки группы"
                role="button"
                tabindex="0"
            <?php } ?>>
            <?php if ($conversation->type === 'group') { ?>
                <span class="js-pm-group-avatar-slot" data-conversation-id="<?php echo (int) $conversation->id; ?>">
                    <?php echo prime_chat_group_avatar_html($conversation->display_image ?? '', 'sm'); ?>
                </span>
            <?php } else { ?>
                <span class="avatar avatar-xs"><img src="<?php echo get_avatar($conversation->display_image); ?>" alt=""></span>
            <?php } ?>
            <div>
                <div class="prime-chat-header-title"><?php echo esc($conversation->display_title); ?></div>
                <div class="prime-chat-header-sub text-off js-pm-group-members-count">
                    <?php if ($conversation->type === 'group') { ?>
                        <?php echo count($members); ?> <?php echo app_lang('chat_members'); ?>
                    <?php } else { ?>
                        <?php
                        $job = trim((string) ($conversation->peer->job_title ?? ''));
                        if ($job === 'Untitled') {
                            $job = '';
                        }
                        if ($online) {
                            echo 'online';
                        } else if ($job) {
                            echo esc($job);
                        }
                        ?>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="prime-chat-header-actions">
            <button type="button" class="prime-chat-icon-btn js-pm-filter" data-mode="files" title="Файлы">
                <i data-feather="folder" class="icon-16"></i>
            </button>
            <button type="button" class="prime-chat-icon-btn js-pm-filter" data-mode="pinned" title="Список закрепов">
                <i data-feather="bookmark" class="icon-16"></i>
            </button>
            <button type="button" class="prime-chat-icon-btn" id="prime-chat-search-toggle" title="Поиск по чату">
                <i data-feather="search" class="icon-16"></i>
            </button>
            <div class="dropdown">
                <button type="button" class="prime-chat-icon-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Ещё">
                    <i data-feather="more-vertical" class="icon-16"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end prime-chat-more-menu">
                    <li>
                        <a class="dropdown-item" href="#" id="prime-chat-clear-history">
                            <i data-feather="trash" class="icon-14"></i> Очистить историю
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" id="prime-chat-delete">
                            <i data-feather="x-circle" class="icon-14"></i>
                            <?php echo $conversation->type === 'group' ? 'Покинуть чат' : 'Удалить чат'; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="prime-chat-search-bar hide" id="prime-chat-search-bar">
        <i data-feather="search" class="icon-14 prime-chat-search-ico"></i>
        <input type="text" id="prime-chat-search" class="form-control" placeholder="Поиск по сообщениям…" autocomplete="off">
        <button type="button" class="prime-chat-icon-btn" id="prime-chat-search-clear" title="Сбросить">
            <i data-feather="x" class="icon-16"></i>
        </button>
    </div>

    <div id="prime-chat-pins-wrap">
        <?php echo view('chat/pinned_bar', array('pinned_messages' => $pinned_messages ?? array())); ?>
    </div>

    <div class="prime-chat-messages" id="prime-chat-messages">
        <?php echo view('chat/message_items', array(
            'messages' => $messages,
            'login_user' => $login_user,
            'last_read_message_id' => (int) ($last_read_message_id ?? ($conversation->last_read_message_id ?? 0)),
        )); ?>
    </div>

    <div class="prime-chat-footer">
        <div id="prime-chat-dropzone" class="post-dropzone prime-chat-composer">
            <?php echo form_open(get_uri("chat/send"), array("id" => "prime-chat-form", "class" => "general-form", "role" => "form")); ?>
            <input type="hidden" name="conversation_id" value="<?php echo (int) $conversation->id; ?>">
            <input type="hidden" name="reply_to_message_id" id="prime-chat-reply-to" value="">

            <div id="prime-chat-reply-bar" class="prime-chat-reply-bar hide">
                <div class="prime-chat-reply-bar-body">
                    <div class="prime-chat-reply-bar-label">Ответ</div>
                    <div class="prime-chat-reply-bar-author" id="prime-chat-reply-author"></div>
                    <div class="prime-chat-reply-bar-text" id="prime-chat-reply-text"></div>
                </div>
                <button type="button" class="prime-chat-reply-cancel" id="prime-chat-reply-cancel" title="Отменить">
                    <i data-feather="x" class="icon-16"></i>
                </button>
            </div>

            <div id="prime-chat-edit-bar" class="prime-chat-reply-bar prime-chat-edit-bar hide">
                <div class="prime-chat-reply-bar-body">
                    <div class="prime-chat-reply-bar-label">Редактирование</div>
                    <div class="prime-chat-reply-bar-text" id="prime-chat-edit-preview"></div>
                </div>
                <button type="button" class="prime-chat-reply-cancel" id="prime-chat-edit-cancel" title="Отменить">
                    <i data-feather="x" class="icon-16"></i>
                </button>
            </div>

            <?php echo view("includes/dropzone_preview"); ?>

            <div class="prime-chat-composer-row">
                <div class="prime-chat-composer-shell">
                    <textarea id="prime-chat-input" name="message" class="form-control" rows="3" placeholder="<?php echo app_lang('write_a_message'); ?>"></textarea>
                    <div class="prime-chat-composer-tools" id="prime-chat-composer-tools">
                        <?php echo view("includes/upload_button", array(
                            "upload_button_text" => "",
                            "upload_button_icon" => "paperclip",
                            "force_show_recording" => true,
                            "inline_svg_icons" => true,
                        )); ?>
                        <button type="button" class="prime-chat-tool-btn" id="prime-chat-emoji" title="Смайлики" aria-label="Смайлики" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
                        </button>
                        <button type="button" class="prime-chat-tool-btn" id="prime-chat-code" title="Вставить как код" aria-label="Код">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" id="prime-chat-send" title="<?php echo app_lang('send'); ?>" aria-label="<?php echo app_lang('send'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.4 20.4l17.45-7.48a1 1 0 0 0 0-1.84L3.4 3.6a.993.993 0 0 0-1.39.91L2 9.12c0 .5.37.93.87.99L17 12 2.87 13.88c-.5.07-.87.5-.87 1l.01 4.61c0 .71.73 1.2 1.39.91z"/></svg>
                </button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        if (typeof window.primeFeatherReplace !== 'function') {
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
                } catch (e) {}
            };
        }
        window.primeFeatherReplace();

        // Voice message player (play / pause / seek / speed)
        (function initPrimeVoicePlayers() {
            if (window.__primeVoicePlayersBound) {
                return;
            }
            window.__primeVoicePlayersBound = true;

            var rates = [1, 1.5, 2, 0.75];
            var tickTimer = null;

            function fmt(sec) {
                sec = Math.max(0, Math.floor(Number(sec) || 0));
                var m = Math.floor(sec / 60);
                var s = sec % 60;
                return m + ':' + (s < 10 ? '0' : '') + s;
            }

            function updateTime($voice) {
                var audio = $voice.find('.pm-voice-audio').get(0);
                if (!audio) {
                    return;
                }
                var cur = Number(audio.currentTime) || 0;
                var dur = Number(audio.duration);
                if (!isFinite(dur) || dur <= 0) {
                    dur = parseFloat($voice.attr('data-duration') || '0') || 0;
                }
                $voice.find('.js-pm-voice-time').text(fmt(cur) + ' / ' + fmt(dur));
                if (!$voice.data('seeking') && dur > 0) {
                    $voice.find('.js-pm-voice-seek').val(String(Math.round((cur / dur) * 1000)));
                }
            }

            function setPlaying($voice, on) {
                $voice.toggleClass('is-playing', !!on);
                $voice.find('.js-pm-voice-play').toggleClass('is-playing', !!on);
            }

            function stopTick() {
                if (tickTimer) {
                    clearInterval(tickTimer);
                    tickTimer = null;
                }
            }

            function startTick(audio) {
                stopTick();
                tickTimer = setInterval(function () {
                    if (!audio || audio.paused || audio.ended) {
                        stopTick();
                        return;
                    }
                    updateTime($(audio).closest('.pm-voice'));
                }, 100);
            }

            function onAudioEvent(e) {
                var audio = e.target;
                var $voice = $(audio).closest('.pm-voice');
                if (!$voice.length) {
                    return;
                }
                updateTime($voice);
                if (e.type === 'ended') {
                    stopTick();
                    try { audio.currentTime = 0; } catch (err) {}
                    setPlaying($voice, false);
                    updateTime($voice);
                } else if (e.type === 'pause') {
                    if (!audio.ended) {
                        stopTick();
                        setPlaying($voice, false);
                    }
                } else if (e.type === 'play') {
                    setPlaying($voice, true);
                    startTick(audio);
                }
                if ((e.type === 'loadedmetadata' || e.type === 'durationchange') && isFinite(audio.duration) && audio.duration > 0) {
                    $voice.attr('data-duration', String(Math.round(audio.duration)));
                    updateTime($voice);
                }
            }

            // Media events do not bubble — bind directly on each <audio>
            function bindVoiceAudio(audio) {
                if (!audio || audio.__pmVoiceBound) {
                    return;
                }
                audio.__pmVoiceBound = true;
                ['timeupdate', 'loadedmetadata', 'durationchange', 'ended', 'pause', 'play'].forEach(function (ev) {
                    audio.addEventListener(ev, onAudioEvent);
                });
            }

            window.primeBindVoicePlayers = function (root) {
                $(root || document).find('.pm-voice-audio').each(function () {
                    bindVoiceAudio(this);
                });
            };

            window.primeBindVoicePlayers(document);

            function pauseOthers(except) {
                $('.pm-voice-audio').each(function () {
                    if (this !== except && !this.paused) {
                        this.pause();
                        setPlaying($(this).closest('.pm-voice'), false);
                    }
                });
            }

            $(document).on('click', '.js-pm-voice-play', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $voice = $(this).closest('.pm-voice');
                var audio = $voice.find('.pm-voice-audio').get(0);
                if (!audio) {
                    return;
                }
                bindVoiceAudio(audio);
                if (audio.paused) {
                    pauseOthers(audio);
                    var rate = parseFloat($voice.find('.js-pm-voice-speed').attr('data-rate') || '1') || 1;
                    audio.playbackRate = rate;
                    setPlaying($voice, true);
                    startTick(audio);
                    var p = audio.play();
                    if (p && typeof p.catch === 'function') {
                        p.catch(function () {
                            stopTick();
                            setPlaying($voice, false);
                            appAlert.error('Не удалось воспроизвести голосовое. Проверьте доступ к файлу.');
                        });
                    }
                } else {
                    audio.pause();
                    stopTick();
                    setPlaying($voice, false);
                }
            });

            $(document).on('click', '.js-pm-voice-speed', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $btn = $(this);
                var $voice = $btn.closest('.pm-voice');
                var audio = $voice.find('.pm-voice-audio').get(0);
                var cur = parseFloat($btn.attr('data-rate') || '1') || 1;
                var idx = rates.indexOf(cur);
                var next = rates[(idx + 1) % rates.length];
                $btn.attr('data-rate', String(next)).text(next + '×');
                if (audio) {
                    audio.playbackRate = next;
                }
            });

            $(document).on('input', '.js-pm-voice-seek', function () {
                var $voice = $(this).closest('.pm-voice');
                $voice.data('seeking', true);
                var audio = $voice.find('.pm-voice-audio').get(0);
                if (!audio) {
                    return;
                }
                bindVoiceAudio(audio);
                var dur = audio.duration;
                if (!isFinite(dur) || dur <= 0) {
                    dur = parseFloat($voice.attr('data-duration') || '0') || 0;
                }
                if (dur > 0) {
                    audio.currentTime = (parseInt(this.value, 10) / 1000) * dur;
                    updateTime($voice);
                }
            });

            $(document).on('change', '.js-pm-voice-seek', function () {
                $(this).closest('.pm-voice').data('seeking', false);
            });
        })();

        if (typeof window.openPrimeStaffProfile !== 'function') {
            window.openPrimeStaffProfile = function (userId) {
                userId = parseInt(userId, 10) || 0;
                if (!userId) {
                    return;
                }
                if (!$('#pm-profile-modal').length) {
                    $('body').append(
                        '<div class="modal fade" id="pm-profile-modal" tabindex="-1" aria-hidden="true">' +
                        '<div class="modal-dialog modal-dialog-centered modal-sm">' +
                        '<div class="modal-content pm-profile-modal-content">' +
                        '<button type="button" class="btn-close pm-profile-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                        '<div class="modal-body p0" id="pm-profile-body"></div>' +
                        '</div></div></div>'
                    );
                }
                $('#pm-profile-body').html('<div class="prime-messenger-loading p20">Загрузка…</div>');
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('pm-profile-modal')).show();
                }
                $.ajax({
                    url: '<?php echo get_uri('chat/staff_profile'); ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {user_id: userId},
                    success: function (result) {
                        if (result && result.success && result.html) {
                            $('#pm-profile-body').html(result.html);
                            window.primeFeatherReplace();
                        } else if (typeof appAlert !== 'undefined') {
                            appAlert.error((result && result.message) ? result.message : 'Не удалось открыть профиль');
                        }
                    }
                });
            };

            $(document).off('click.pmProfile').on('click.pmProfile', '.js-pm-open-profile', function (e) {
                e.preventDefault();
                e.stopPropagation();
                window.openPrimeStaffProfile($(this).attr('data-user-id'));
            });
        }

        var $thread = $('.prime-chat-thread');
        var conversationId = $thread.data('conversation-id');
        var $messages = $('#prime-chat-messages');
        var $input = $('#prime-chat-input');
        var $form = $('#prime-chat-form');
        var $replyTo = $('#prime-chat-reply-to');
        var $replyBar = $('#prime-chat-reply-bar');
        var $editBar = $('#prime-chat-edit-bar');
        var editingMessageId = 0;
        var isSending = false;
        var draftKey = 'prime_chat_draft_' + (window.AppHelper && AppHelper.userId ? AppHelper.userId : 'u') + '_' + conversationId;
        var draftTimer = null;

        function readDraft() {
            try {
                var raw = localStorage.getItem(draftKey);
                return raw ? JSON.parse(raw) : null;
            } catch (e) {
                return null;
            }
        }

        function writeDraft() {
            var text = $input.val() || '';
            var replyId = $replyTo.val() || '';
            if (!$.trim(text) && !replyId) {
                try { localStorage.removeItem(draftKey); } catch (e) {}
                return;
            }
            try {
                localStorage.setItem(draftKey, JSON.stringify({
                    text: text,
                    replyId: replyId,
                    replyAuthor: $('#prime-chat-reply-author').text() || '',
                    replyText: $('#prime-chat-reply-text').text() || '',
                    updatedAt: Date.now()
                }));
            } catch (e) {}
        }

        function clearDraft() {
            clearTimeout(draftTimer);
            try { localStorage.removeItem(draftKey); } catch (e) {}
        }

        function scheduleDraftSave() {
            if (editingMessageId) {
                return;
            }
            clearTimeout(draftTimer);
            draftTimer = setTimeout(writeDraft, 250);
        }

        function restoreDraft() {
            var draft = readDraft();
            if (!draft) {
                return;
            }
            if (draft.text) {
                $input.val(draft.text).trigger('input');
            }
            if (draft.replyId) {
                $replyTo.val(draft.replyId);
                $('#prime-chat-reply-author').text(draft.replyAuthor || '');
                $('#prime-chat-reply-text').text(draft.replyText || '');
                $replyBar.removeClass('hide');
                window.primeFeatherReplace();
            }
        }

        restoreDraft();

        function scrollBottom() {
            if ($messages.length && $messages[0]) {
                $messages.scrollTop($messages[0].scrollHeight);
            }
        }

        function scrollTargetIntoMessages($target, offset) {
            var el = $messages[0];
            var target = $target && $target[0] ? $target[0] : null;
            if (!el || !target) {
                return false;
            }
            offset = typeof offset === 'number' ? offset : 20;
            var top = target.getBoundingClientRect().top - el.getBoundingClientRect().top + el.scrollTop - offset;
            el.scrollTop = Math.max(0, top);
            return true;
        }

        function scrollToInitialPosition() {
            if (!$messages.length || !$messages[0]) {
                return;
            }
            var $start = $messages.find('.js-pm-unread-start').first();
            if (!$start.length) {
                $start = $messages.find('.prime-chat-bubble.is-unread-msg').first();
            }
            if ($start.length) {
                scrollTargetIntoMessages($start, 12);
            } else {
                scrollBottom();
            }
        }

        window.primeChatScrollToStart = function () {
            scrollToInitialPosition();
            // layout/images can change height after first paint
            requestAnimationFrame(function () {
                scrollToInitialPosition();
                setTimeout(scrollToInitialPosition, 60);
                setTimeout(scrollToInitialPosition, 200);
                setTimeout(scrollToInitialPosition, 500);
            });
            $messages.find('img').one('load', function () {
                scrollToInitialPosition();
            });
        };

        window.primeChatScrollToStart();

        function getLastId() {
            return Number($thread.attr('data-last-id')) || 0;
        }

        function setLastId(id) {
            if (id > getLastId()) {
                $thread.attr('data-last-id', id);
            }
        }

        function appendHtml(html) {
            if (!html) return;
            $messages.append(html);
            scrollBottom();
            window.primeFeatherReplace();
            if (typeof window.primeBindVoicePlayers === 'function') {
                window.primeBindVoicePlayers($messages);
            }
        }

        function clearReply() {
            $replyTo.val('');
            $replyBar.addClass('hide');
            $('#prime-chat-reply-author').text('');
            $('#prime-chat-reply-text').text('');
            scheduleDraftSave();
        }

        function clearEdit() {
            editingMessageId = 0;
            $editBar.addClass('hide');
            $('#prime-chat-edit-preview').text('');
            $thread.removeClass('is-editing');
            $form.find('.prime-chat-composer-tools').removeClass('hide');
        }

        function setReply($bubble) {
            clearEdit();
            var id = $bubble.attr('data-message-id');
            var author = $bubble.attr('data-author') || '';
            var preview = $bubble.attr('data-preview') || '';
            if (!id) return;

            $replyTo.val(id);
            $('#prime-chat-reply-author').text(author);
            $('#prime-chat-reply-text').text(preview);
            $replyBar.removeClass('hide');
            window.primeFeatherReplace();
            scheduleDraftSave();
            $input.focus();
        }

        function setEdit($bubble) {
            if ($bubble.attr('data-editable') !== '1') {
                return;
            }
            clearReply();
            var id = $bubble.attr('data-message-id');
            var raw = $bubble.attr('data-raw') || '';
            var preview = $bubble.attr('data-preview') || '';
            if (!id) return;

            editingMessageId = id;
            $('#prime-chat-edit-preview').text(preview || raw);
            $editBar.removeClass('hide');
            $thread.addClass('is-editing');
            $form.find('.prime-chat-composer-tools').addClass('hide');
            $input.val(raw).trigger('input').focus();
            window.primeFeatherReplace();
        }

        function hasAttachedFiles() {
            return $form.find('input[name="file_names[]"]').length > 0;
        }

        function saveEdit() {
            if (isSending || !editingMessageId) return;

            var text = $.trim($input.val());
            var $bubble = $messages.find('.prime-chat-bubble[data-message-id="' + editingMessageId + '"]');
            var hasFiles = $bubble.attr('data-has-files') === '1';
            if (!text && !hasFiles) {
                return;
            }

            isSending = true;
            var messageId = editingMessageId;

            $.ajax({
                url: '<?php echo get_uri('chat/edit_message'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    conversation_id: conversationId,
                    message_id: messageId,
                    message: text
                },
                success: function (result) {
                    if (!result.success) {
                        appAlert.error(result.message || 'Error');
                        return;
                    }
                    if (result.html) {
                        var $old = $messages.find('.prime-chat-bubble[data-message-id="' + messageId + '"]');
                        $old.replaceWith(result.html);
                        window.primeFeatherReplace();
                    }
                    if (result.preview !== undefined) {
                        var listPreview = result.preview || 'Нет сообщений';
                        $('.js-pm-open[data-id="' + conversationId + '"] .prime-messenger-item-preview > span').text(listPreview);
                    }
                    $input.val('').trigger('input');
                    clearEdit();
                    clearDraft();
                },
                error: function () {
                    appAlert.error('Не удалось сохранить изменения');
                },
                complete: function () {
                    isSending = false;
                }
            });
        }

        function sendMessage() {
            if (editingMessageId) {
                saveEdit();
                return;
            }
            if (isSending) return;

            var text = $.trim($input.val());
            if (!text && !hasAttachedFiles()) {
                return;
            }

            isSending = true;
            var payload = $form.serialize();
            var replyBackup = $replyTo.val();
            var authorBackup = $('#prime-chat-reply-author').text();
            var textBackup = $('#prime-chat-reply-text').text();

            $input.val('').trigger('input');
            clearReply();
            clearDraft();

            $.ajax({
                url: '<?php echo get_uri('chat/send'); ?>',
                type: 'POST',
                dataType: 'json',
                data: payload,
                success: function (result) {
                    if (result.success) {
                        appendHtml(result.html);
                        setLastId(result.message_id);
                        clearDraft();
                        if (window.formDropzone && window.formDropzone['prime-chat-dropzone']) {
                            window.formDropzone['prime-chat-dropzone'].removeAllFiles();
                        }
                    } else {
                        appAlert.error(result.message || 'Error');
                        if (text) {
                            $input.val(text);
                        }
                        if (replyBackup) {
                            $replyTo.val(replyBackup);
                            $('#prime-chat-reply-author').text(authorBackup);
                            $('#prime-chat-reply-text').text(textBackup);
                            $replyBar.removeClass('hide');
                        }
                        scheduleDraftSave();
                    }
                },
                error: function () {
                    appAlert.error('Error');
                    if (text) {
                        $input.val(text);
                    }
                    if (replyBackup) {
                        $replyTo.val(replyBackup);
                        $('#prime-chat-reply-author').text(authorBackup);
                        $('#prime-chat-reply-text').text(textBackup);
                        $replyBar.removeClass('hide');
                    }
                    scheduleDraftSave();
                },
                complete: function () {
                    isSending = false;
                }
            });
        }

        $('#prime-chat-back').on('click', function () {
            if (window.primeChatPollTimer) {
                clearInterval(window.primeChatPollTimer);
                window.primeChatPollTimer = null;
            }
            if (typeof window.loadPrimeChatPanel === 'function') {
                window.loadPrimeChatPanel();
            }
        });

        $('#prime-chat-send').on('click', sendMessage);
        function getSendKeyMode() {
            if (typeof window.primeChatGetSendKeyMode === 'function') {
                return window.primeChatGetSendKeyMode();
            }
            try {
                var v = localStorage.getItem('prime_messenger_send_key');
                if (v === 'enter' || v === 'mod_enter') {
                    return v;
                }
            } catch (err) {}
            return 'enter';
        }
        $input.on('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                var mode = getSendKeyMode();
                var withMod = !!(e.ctrlKey || e.metaKey);
                if (mode === 'mod_enter') {
                    if (withMod) {
                        e.preventDefault();
                        sendMessage();
                    }
                    return;
                }
                e.preventDefault();
                sendMessage();
            }
            if (e.key === 'Escape') {
                if (editingMessageId) {
                    clearEdit();
                    $input.val('').trigger('input');
                } else {
                    clearReply();
                }
            }
        });

        $input.on('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(Math.max(this.scrollHeight, 74), 160) + 'px';
            scheduleDraftSave();
        });
        $input.trigger('input');

        $(window).on('beforeunload.primeChatDraft', writeDraft);

        $('#prime-chat-reply-cancel').on('click', clearReply);
        $('#prime-chat-edit-cancel').on('click', function () {
            clearEdit();
            $input.val('').trigger('input');
        });

        function wrapAsCode() {
            var el = $input.get(0);
            var value = $input.val() || '';
            var start = el.selectionStart || 0;
            var end = el.selectionEnd || 0;
            var selected = value.substring(start, end);

            if (selected) {
                var wrapped = '```\n' + selected.replace(/^\n+|\n+$/g, '') + '\n```';
                $input.val(value.substring(0, start) + wrapped + value.substring(end));
                var caret = start + wrapped.length;
                el.setSelectionRange(caret, caret);
            } else if ($.trim(value)) {
                if (/^```[\s\S]*```$/.test($.trim(value))) {
                    $input.focus();
                    return;
                }
                $input.val('```\n' + value.replace(/^\n+|\n+$/g, '') + '\n```');
            } else {
                $input.val('```\n\n```');
                el.setSelectionRange(4, 4);
            }
            $input.trigger('input').focus();
        }

        $('#prime-chat-code').on('click', wrapAsCode);

        // Paste multi-line code-looking text → wrap in fences if not already
        $input.on('paste', function (e) {
            var clip = (e.originalEvent && e.originalEvent.clipboardData)
                ? e.originalEvent.clipboardData.getData('text/plain')
                : '';
            if (!clip || clip.indexOf('\n') === -1) {
                return;
            }
            if (/```/.test(clip)) {
                return;
            }
            var looksCode = /(^\s{2,}|\t|[{};]$|function\s|const\s|let\s|var\s|SELECT\s|def\s|class\s)/m.test(clip)
                || clip.indexOf('<' + '?php') !== -1
                || (clip.split('\n').length >= 3 && /[{}\[\]();=]/.test(clip));
            if (!looksCode) {
                return;
            }
            var el = this;
            var start = el.selectionStart || 0;
            var end = el.selectionEnd || 0;
            var value = $input.val() || '';
            if ($.trim(value) && start === 0 && end === value.length) {
                // replacing all — wrap
            } else if ($.trim(value)) {
                return; // mixed paste into existing text — leave as is
            }
            e.preventDefault();
            var wrapped = '```\n' + clip.replace(/^\n+|\n+$/g, '') + '\n```';
            $input.val(value.substring(0, start) + wrapped + value.substring(end));
            $input.trigger('input');
        });

        $messages.on('click', '.js-pm-reply', function (e) {
            e.preventDefault();
            e.stopPropagation();
            setReply($(this).closest('.prime-chat-bubble'));
        });

        $messages.on('click', '.js-pm-edit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            setEdit($(this).closest('.prime-chat-bubble'));
        });

        function jumpToMessage(id) {
            id = String(id || '');
            var $target = $messages.find('.prime-chat-bubble[data-message-id="' + id + '"]');
            if (!$target.length) {
                return false;
            }
            var top = $target.offset().top - $messages.offset().top + $messages.scrollTop() - 48;
            $messages.stop(true).animate({ scrollTop: Math.max(0, top) }, 220);
            $target.addClass('is-flash');
            setTimeout(function () {
                $target.removeClass('is-flash');
            }, 1400);
            return true;
        }

        function getPinBar() {
            return $('#prime-chat-pins');
        }

        function parsePinIds($bar) {
            var raw = String(($bar && $bar.attr('data-pin-ids')) || '');
            return raw.split(',').map(function (x) { return $.trim(x); }).filter(Boolean);
        }

        function setPinPreview($bar, messageId) {
            if (!$bar || !$bar.length) {
                return;
            }
            var map = {};
            try {
                map = JSON.parse($bar.attr('data-pin-previews') || '{}') || {};
            } catch (e) {
                map = {};
            }
            var text = map[String(messageId)] || map[messageId] || '';
            if (text) {
                $bar.find('.js-pm-pin-preview').text(text);
            }
        }

        function loadAroundMessage(messageId, done) {
            filterMode = 'all';
            $('.js-pm-filter').removeClass('is-active');
            $searchInput.val('');
            $searchBar.addClass('hide');
            $messages.html('<div class="prime-messenger-loading">Загрузка…</div>');
            $.ajax({
                url: '<?php echo get_uri('chat/filter_messages'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    conversation_id: conversationId,
                    mode: 'all',
                    around_id: messageId,
                    q: ''
                },
                success: function (result) {
                    if (result.success) {
                        $messages.html(result.html || '');
                        window.primeFeatherReplace();
                        if (typeof window.primeBindVoicePlayers === 'function') {
                            window.primeBindVoicePlayers($messages);
                        }
                        if (typeof done === 'function') {
                            setTimeout(done, 40);
                        }
                    }
                }
            });
        }

        function jumpToPinInContext(messageId, pinIndex) {
            messageId = String(messageId || '');
            if (!messageId) {
                return;
            }
            var $bar = getPinBar();
            if ($bar.length && pinIndex !== undefined && pinIndex !== null) {
                $bar.attr('data-pin-index', String(pinIndex));
            }
            setPinPreview($bar, messageId);

            function finish() {
                jumpToMessage(messageId);
            }

            if (filterMode !== 'all' || $.trim($searchInput.val() || '')) {
                loadAroundMessage(messageId, finish);
                return;
            }
            if (jumpToMessage(messageId)) {
                return;
            }
            loadAroundMessage(messageId, finish);
        }

        $messages.on('click', '.js-pm-jump-reply', function (e) {
            e.preventDefault();
            jumpToPinInContext($(this).attr('data-reply-id'));
        });

        var filterMode = 'all';
        var filterTimer = null;
        var $searchBar = $('#prime-chat-search-bar');
        var $searchInput = $('#prime-chat-search');

        function applyFilter(mode, q) {
            filterMode = mode || 'all';
            q = (q === undefined) ? $.trim($searchInput.val() || '') : $.trim(q || '');

            $('.js-pm-filter').removeClass('is-active');
            if (filterMode === 'files' || filterMode === 'pinned') {
                $('.js-pm-filter[data-mode="' + filterMode + '"]').addClass('is-active');
            }

            $messages.html('<div class="prime-messenger-loading">Загрузка…</div>');
            $.ajax({
                url: '<?php echo get_uri('chat/filter_messages'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    conversation_id: conversationId,
                    mode: filterMode,
                    q: q
                },
                success: function (result) {
                    if (result.success) {
                        $messages.html(result.html || '');
                        window.primeFeatherReplace();
                        if (typeof window.primeBindVoicePlayers === 'function') {
                            window.primeBindVoicePlayers($messages);
                        }
                        if (filterMode === 'all' && !q) {
                            scrollBottom();
                        }
                    }
                }
            });
        }

        $('#prime-chat-search-toggle').on('click', function () {
            $searchBar.toggleClass('hide');
            if (!$searchBar.hasClass('hide')) {
                $searchInput.focus();
            } else {
                $searchInput.val('');
                if (filterMode === 'all') {
                    applyFilter('all', '');
                }
            }
            window.primeFeatherReplace();
        });

        $('#prime-chat-search-clear').on('click', function () {
            $searchInput.val('');
            $searchBar.addClass('hide');
            applyFilter(filterMode === 'search' ? 'all' : filterMode, '');
        });

        $searchInput.on('input', function () {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(function () {
                var q = $.trim($searchInput.val() || '');
                applyFilter(q ? 'search' : (filterMode === 'search' ? 'all' : filterMode), q);
            }, 280);
        });

        $thread.on('click', '.js-pm-filter', function () {
            var mode = $(this).attr('data-mode');
            if (filterMode === mode) {
                applyFilter('all', $.trim($searchInput.val() || ''));
            } else {
                applyFilter(mode, $.trim($searchInput.val() || ''));
            }
        });

        $thread.on('click', '.js-pm-cycle-pin', function (e) {
            e.preventDefault();
            var $bar = getPinBar();
            var ids = parsePinIds($bar);
            if (!ids.length) {
                return;
            }
            var idx = parseInt($bar.attr('data-pin-index') || '0', 10);
            if (isNaN(idx) || idx < 0) {
                idx = 0;
            }
            // First click goes to current preview pin; further clicks advance
            var alreadyVisited = $bar.attr('data-pin-cycled') === '1';
            if (alreadyVisited) {
                idx = (idx + 1) % ids.length;
            }
            $bar.attr('data-pin-cycled', '1');
            $bar.attr('data-pin-index', String(idx));
            jumpToPinInContext(ids[idx], idx);
        });

        $thread.on('click', '.js-pm-toggle-pin-list', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $list = $('#prime-chat-pins-list');
            var open = $list.hasClass('hide');
            $list.toggleClass('hide', !open);
            $(this).attr('aria-expanded', open ? 'true' : 'false');
            $(this).toggleClass('is-open', open);
        });

        $thread.on('click', '.js-pm-jump-msg', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).attr('data-message-id');
            var idx = $(this).attr('data-pin-index');
            $('#prime-chat-pins-list').addClass('hide');
            $('.js-pm-toggle-pin-list').removeClass('is-open').attr('aria-expanded', 'false');
            var $bar = getPinBar();
            if ($bar.length) {
                $bar.attr('data-pin-cycled', '1');
            }
            jumpToPinInContext(id, idx !== undefined ? parseInt(idx, 10) : undefined);
        });

        $messages.on('click', '.js-pm-pin', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $bubble = $(this).closest('.prime-chat-bubble');
            var messageId = $bubble.attr('data-message-id');
            $.ajax({
                url: '<?php echo get_uri('chat/toggle_pin'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    conversation_id: conversationId,
                    message_id: messageId
                },
                success: function (result) {
                    if (!result.success) {
                        appAlert.error(result.message || 'Error');
                        return;
                    }
                    $bubble.toggleClass('is-pinned', !!result.pinned);
                    $bubble.attr('data-pinned', result.pinned ? '1' : '0');
                    var $btn = $bubble.find('.js-pm-pin');
                    $btn.toggleClass('is-active', !!result.pinned);
                    $btn.attr('title', result.pinned ? 'Открепить' : 'Закрепить');
                    $btn.find('span').text(result.pinned ? 'Открепить' : 'Закрепить');
                    $('#prime-chat-pins-wrap').html(result.bar_html || '');
                    window.primeFeatherReplace();
                    if (filterMode === 'pinned') {
                        applyFilter('pinned', '');
                    }
                }
            });
        });

        $messages.on('click', '.js-pm-copy-code', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var $block = $btn.closest('.pm-code-block');
            var codeEl = $block.find('pre code').get(0);
            var text = codeEl ? (codeEl.innerText || codeEl.textContent || '') : '';
            if (!text) {
                return;
            }

            function markCopied() {
                var $label = $btn.find('.pm-code-copy-label');
                var prev = $label.text();
                $btn.addClass('is-copied');
                $label.text('Скопировано');
                setTimeout(function () {
                    $btn.removeClass('is-copied');
                    $label.text(prev || 'Копировать');
                }, 1400);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(markCopied).catch(function () {
                    fallbackCopy(text, markCopied);
                });
            } else {
                fallbackCopy(text, markCopied);
            }
        });

        function fallbackCopy(text, done) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                if (typeof done === 'function') {
                    done();
                }
            } catch (err) {
                if (typeof appAlert !== 'undefined') {
                    appAlert.error('Не удалось скопировать');
                }
            }
            document.body.removeChild(ta);
        }

        function closeThreadAfterDelete() {
            try { localStorage.removeItem(draftKey); } catch (e) {}
            if (window.primeChatPollTimer) {
                clearInterval(window.primeChatPollTimer);
                window.primeChatPollTimer = null;
            }
            $('.js-pm-open[data-id="' + conversationId + '"]').remove();
            $('.js-prime-chat-open[data-id="' + conversationId + '"]').remove();
            if ($('#pm-thread').length) {
                $('#pm-thread').addClass('hide').empty();
                $('#pm-placeholder').removeClass('hide');
                history.replaceState(null, '', '<?php echo get_uri('messages/inbox'); ?>');
            } else if (typeof window.loadPrimeChatPanel === 'function') {
                window.loadPrimeChatPanel();
            } else {
                $('#prime-chat-back').trigger('click');
            }
        }

        $thread.on('click', '#prime-chat-clear-history', function (e) {
            e.preventDefault();
            if (!window.confirm('Очистить всю историю этого чата? Сообщения исчезнут у всех участников.')) {
                return;
            }
            $.ajax({
                url: '<?php echo get_uri('chat/clear_history'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {conversation_id: conversationId},
                success: function (result) {
                    if (!result.success) {
                        appAlert.error(result.message || 'Error');
                        return;
                    }
                    $messages.empty();
                    $('#prime-chat-pins-wrap').empty();
                    setLastId(0);
                    filterMode = 'all';
                    $searchInput.val('');
                    $searchBar.addClass('hide');
                    $('.js-pm-filter').removeClass('is-active');
                    $('.js-pm-open[data-id="' + conversationId + '"] .prime-messenger-item-preview > span').text('Нет сообщений');
                    $('.js-prime-chat-open[data-id="' + conversationId + '"] .prime-chat-row-preview > span').text('—');
                    appAlert.success(result.message || 'История очищена');
                },
                error: function () {
                    appAlert.error('Не удалось очистить историю');
                }
            });
        });

        $thread.on('click', '#prime-chat-delete', function (e) {
            e.preventDefault();
            var isGroup = <?php echo json_encode($conversation->type === 'group'); ?>;
            var doDelete = function () {
                $.ajax({
                    url: '<?php echo get_uri('chat/delete_conversation'); ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {conversation_id: conversationId},
                    success: function (result) {
                        if (!result.success) {
                            appAlert.error(result.message || 'Error');
                            return;
                        }
                        closeThreadAfterDelete();
                        appAlert.success(result.message || 'Чат удалён');
                    },
                    error: function () {
                        appAlert.error('Не удалось удалить чат');
                    }
                });
            };

            if (typeof window.confirmPrimeChatDelete === 'function') {
                window.confirmPrimeChatDelete({ isGroup: isGroup, onConfirm: doDelete });
                return;
            }

            var $modal = $('#confirmationModal');
            if ($modal.length && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                $('#confirmationModalTitle').text(isGroup ? 'Покинуть чат?' : 'Удалить чат?');
                $('#confirmationModalContent .container-fluid').text(
                    isGroup
                        ? 'Вы точно хотите покинуть этот групповой чат?'
                        : 'Чат исчезнет из вашего списка. История останется у собеседника.'
                );
                $('#confirmDeleteButton')
                    .html((isGroup ? 'Покинуть' : 'Удалить'))
                    .off('click.pmDelete')
                    .on('click.pmDelete', doDelete);
                bootstrap.Modal.getOrCreateInstance($modal[0]).show();
                return;
            }

            if (window.confirm(isGroup ? 'Покинуть этот чат?' : 'Удалить чат из списка?')) {
                doDelete();
            }
        });

        if (window.primeChatPollTimer) {
            clearInterval(window.primeChatPollTimer);
        }
        window.primeChatPollTimer = setInterval(function () {
            if (!$('.prime-chat-thread').length) {
                clearInterval(window.primeChatPollTimer);
                return;
            }
            if (filterMode !== 'all' || $.trim($searchInput.val() || '')) {
                return;
            }
            $.ajax({
                url: '<?php echo get_uri('chat/poll'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {conversation_id: conversationId, after_id: getLastId()},
                success: function (result) {
                    if (result.success && result.count) {
                        appendHtml(result.html);
                        setLastId(result.last_id);
                    }
                }
            });
        }, 4000);

        $input.focus();

        if (typeof feather !== 'undefined') {
            window.primeFeatherReplace();
            setTimeout(function () { window.primeFeatherReplace(); }, 30);
        }
    });
</script>
