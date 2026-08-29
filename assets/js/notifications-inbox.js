(function ($) {
    "use strict";

    var PAGE_SIZE = 40;
    var listXhr = null;
    var panelXhr = null;
    var activeNotificationId = null;
    var lastDateGroup = null;

    var state = {
        skip: 0,
        hasMore: true,
        loading: false,
        tab: "unread"
    };

    function getListEl() {
        return $("#notifications-inbox-list");
    }

    function getLoadMoreEl() {
        return $("#notifications-inbox-load-more");
    }

    function getDetailBody() {
        return $("#notification-detail-body");
    }

    function avatarHue(initial) {
        if (!initial) {
            return "0";
        }

        var code = initial.toUpperCase().charCodeAt(0) || 65;
        return String((code % 5) + 1);
    }

    function getDateGroupLabel(dateStr) {
        if (!dateStr) {
            return "";
        }

        var parts = dateStr.split(/[\sT]/);
        var datePart = parts[0];
        var today = new Date();
        var todayStr = today.getFullYear() + "-" + String(today.getMonth() + 1).padStart(2, "0") + "-" + String(today.getDate()).padStart(2, "0");

        var yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        var yesterdayStr = yesterday.getFullYear() + "-" + String(yesterday.getMonth() + 1).padStart(2, "0") + "-" + String(yesterday.getDate()).padStart(2, "0");

        if (datePart === todayStr) {
            return "Сегодня";
        }

        if (datePart === yesterdayStr) {
            return "Вчера";
        }

        var d = new Date(datePart.replace(/-/g, "/"));
        if (isNaN(d.getTime())) {
            return datePart;
        }

        var months = ["января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря"];
        return d.getDate() + " " + months[d.getMonth()];
    }

    function buildAvatar(item) {
        if (item.avatar_url) {
            return '<div class="notifications-inbox-avatar"><img src="' + item.avatar_url + '" alt=""></div>';
        }

        var initial = item.avatar_initial || "?";
        return '<div class="notifications-inbox-avatar" data-hue="' + avatarHue(initial) + '">' + $("<div>").text(initial).html() + "</div>";
    }

    function escapeHtml(text) {
        return $("<div>").text(text || "").html();
    }

    function linkifyPreview(text) {
        var escaped = escapeHtml(text);
        if (!escaped) {
            return "";
        }

        return escaped.replace(/(https?:\/\/[^\s<]+)/g, function (url) {
            var clean = url.replace(/[.,;:!?)]+$/g, "");
            var trailing = url.slice(clean.length);
            return '<a href="' + clean + '" class="notifications-inbox-preview-link" target="_blank" rel="noopener noreferrer">' + clean + "</a>" + trailing;
        });
    }

    function buildItemHtml(item) {
        var unreadClass = item.is_unread ? " is-unread" : "";
        var count = parseInt(item.group_count, 10) || 1;
        var badge = item.is_unread
            ? '<span class="notifications-inbox-badge">' + count + "</span>"
            : "";
        var title = escapeHtml(item.actor_name || "");
        var eventLabel = escapeHtml(item.event_label || "");
        var entity = escapeHtml(item.entity_title || "");
        var preview = linkifyPreview(item.preview || "");
        var project = escapeHtml(item.project_title || "");

        var entityHtml = entity ? '<div class="notifications-inbox-entity">' + entity + "</div>" : "";
        var projectHtml = project && project !== entity ? '<div class="notifications-inbox-entity">' + project + "</div>" : "";
        var previewHtml = preview ? '<div class="notifications-inbox-item-preview">' + preview + "</div>" : "";

        return (
            '<div role="button" tabindex="0" class="notifications-inbox-item js-notification-inbox-item' + unreadClass + '" data-id="' + item.id + '" data-ids="' + (item.ids || [item.id]).join(",") + '" data-ticket-id="' + (item.ticket_id || "") + '" data-task-id="' + (item.task_id || "") + '" data-date-group="' + getDateGroupLabel(item.created_at) + '">' +
                buildAvatar(item) +
                '<div class="notifications-inbox-item-body">' +
                    '<div class="notifications-inbox-item-top">' +
                        '<div class="notifications-inbox-item-title">' + title + "</div>" +
                        '<span class="notifications-inbox-item-meta">' + badge + '<span class="notifications-inbox-time">' + (item.time_label || "") + "</span></span>" +
                    "</div>" +
                    '<div class="notifications-inbox-event">' + eventLabel + "</div>" +
                    entityHtml +
                    projectHtml +
                    previewHtml +
                "</div>" +
            "</div>"
        );
    }

    function appendItems(items, reset) {
        var $list = getListEl();

        if (reset) {
            $list.empty();
            lastDateGroup = null;
        }

        if (!items || !items.length) {
            if (reset) {
                $list.html('<div class="notifications-inbox-empty">Нет уведомлений</div>');
            }
            return;
        }

        var html = "";
        items.forEach(function (item) {
            var group = getDateGroupLabel(item.created_at);
            if (group && group !== lastDateGroup) {
                html += '<div class="notifications-inbox-date-group">' + group + "</div>";
                lastDateGroup = group;
            }
            html += buildItemHtml(item);
        });

        $list.append(html);
    }

    function setLoading(isLoading) {
        state.loading = isLoading;
        var $more = getLoadMoreEl();

        if (isLoading && state.skip === 0) {
            getListEl().html('<div class="notifications-inbox-loading"><span class="spinning-btn spinning"></span> Загрузка...</div>');
            $more.empty();
            return;
        }

        if (isLoading) {
            $more.html('<div class="notifications-inbox-loading"><span class="spinning-btn spinning"></span></div>');
        }
    }

    function updateLoadMore(hasMore) {
        state.hasMore = !!hasMore;
        var $more = getLoadMoreEl();

        if (!hasMore) {
            $more.empty();
            return;
        }

        $more.html('<button type="button" class="btn btn-default btn-sm js-notifications-load-more">Ещё</button>');
    }

    function getFilterPayload() {
        var payload = {
            skip: state.skip,
            limit: PAGE_SIZE,
            tab: state.tab
        };

        var filters = window.notificationInboxFilters || {};
        Object.keys(filters).forEach(function (key) {
            if (filters[key] !== null && filters[key] !== undefined && filters[key] !== "") {
                payload[key] = filters[key];
            }
        });

        return payload;
    }

    function loadInbox(reset) {
        if (state.loading) {
            return;
        }

        if (reset) {
            state.skip = 0;
            state.hasMore = true;
        }

        if (!state.hasMore && !reset) {
            return;
        }

        setLoading(true);
        syncCsrf();

        if (listXhr && listXhr.readyState !== 4) {
            listXhr.abort();
        }

        listXhr = $.ajax({
            url: window.notificationInboxListUrl || (AppHelper.baseUrl + "index.php/notifications/inbox_list_data"),
            type: "POST",
            dataType: "json",
            data: getFilterPayload(),
            success: function (response) {
                setLoading(false);

                if (!response || !response.success) {
                    if (reset) {
                        getListEl().html('<div class="notifications-inbox-empty">Не удалось загрузить список</div>');
                    }
                    return;
                }

                appendItems(response.data, reset);
                state.skip += (response.data || []).length;
                var hasMore = !!(response.hasMore && (response.data || []).length);
                updateLoadMore(hasMore);
                setActiveItem(activeNotificationId);
            },
            error: function (_, status) {
                setLoading(false);
                if (status === "abort") {
                    return;
                }

                if (reset) {
                    getListEl().html('<div class="notifications-inbox-empty">Не удалось загрузить список</div>');
                }
            }
        });
    }

    function updateNotificationUrl(notificationId) {
        if (!window.history || !window.history.replaceState) {
            return;
        }

        var url = new URL(window.location.href);

        if (notificationId) {
            url.searchParams.set("notification", notificationId);
        } else {
            url.searchParams.delete("notification");
        }

        window.history.replaceState({}, "", url.toString());
    }

    function setActiveItem(notificationId) {
        $(".js-notification-inbox-item").removeClass("is-active");

        if (!notificationId) {
            return;
        }

        $(".js-notification-inbox-item[data-id='" + notificationId + "']").addClass("is-active");
    }

    function setPanelState(hasOpen) {
        var $page = $("#notifications-page-content");

        if (hasOpen) {
            $page.addClass("has-notification-open");
        } else {
            $page.removeClass("has-notification-open");
        }
    }

    function showPanelLoading() {
        getDetailBody().html('<div class="notification-side-panel-loading"><span class="spinning-btn spinning"></span> Загрузка...</div>');
    }

    function markItemRead(notificationId) {
        var $item = $(".js-notification-inbox-item[data-id='" + notificationId + "']");
        $item.removeClass("is-unread");
        $item.find(".notifications-inbox-badge").remove();
    }

    function markItemUnread(notificationId) {
        var $item = $(".js-notification-inbox-item[data-id='" + notificationId + "']");
        $item.addClass("is-unread");
        if (!$item.find(".notifications-inbox-badge").length) {
            $item.find(".notifications-inbox-item-meta").prepend('<span class="notifications-inbox-badge">1</span>');
        }
    }

    function setNotificationUnread(notificationId) {
        notificationId = parseInt(notificationId, 10);
        if (!notificationId) {
            return;
        }

        markItemUnread(notificationId);

        $.ajax({
            url: AppHelper.baseUrl + "index.php/notifications/set_notification_status_as_unread/" + notificationId
        });
    }

    function ensureMarkUnreadControl($root) {
        var $toolbar = $root.find(".notification-panel-toolbar, .ticket-panel-view-toolbar, .task-panel-view-toolbar").first();
        if (!$toolbar.length || $toolbar.find(".js-notification-mark-unread").length) {
            return;
        }

        $toolbar.prepend(
            '<button type="button" class="btn btn-default btn-sm js-notification-mark-unread" title="Отметить как непрочитанное">Отметить как непрочитанное</button>'
        );
    }

    function initEmbeddedPanelContent() {
        if (typeof setSummernoteToAll === "function") {
            setSummernoteToAll(true);
        }

        if (typeof feather !== "undefined") {
            feather.replace();
        }

        if (typeof selectLastlySelectedTab === "function") {
            selectLastlySelectedTab("#notification-detail-body");
        }
    }

    function markNotificationReadOnServer(notificationId) {
        $.ajax({
            url: AppHelper.baseUrl + "index.php/notifications/set_notification_status_as_read/" + notificationId
        });
    }

    function syncCsrf() {
        if (typeof window.primeSyncCsrf === "function") {
            window.primeSyncCsrf();
        }
    }

    function responseLooksLikeAppShell(html) {
        if (typeof html !== "string" || !html) {
            return false;
        }
        return /<html[\s>]/i.test(html)
            || /id=["']left-menu-toggle-mask["']/i.test(html)
            || /name=["']email["'][^>]*signin|signin.*name=["']email["']/i.test(html)
            || /Войти в систему/i.test(html);
    }

    function showPanelLoadError(notificationId) {
        getDetailBody().html(
            '<div class="notification-panel-placeholder">' +
                'Не удалось загрузить уведомление. ' +
                '<button type="button" class="btn btn-default btn-sm js-notification-panel-retry" data-id="' + notificationId + '">Повторить</button>' +
            '</div>'
        );
    }

    function renderPanelHtml(response) {
        if (responseLooksLikeAppShell(response)) {
            if (!window._primeSessionReloadScheduled) {
                window._primeSessionReloadScheduled = true;
                window.location.reload();
            }
            return false;
        }
        getDetailBody().html(response);
        ensureMarkUnreadControl(getDetailBody());
        return true;
    }

    function openNotification(notificationId, ticketId, taskId, isRetry) {
        notificationId = parseInt(notificationId, 10);
        ticketId = parseInt(ticketId, 10) || 0;
        taskId = parseInt(taskId, 10) || 0;
        isRetry = !!isRetry;

        if (!notificationId) {
            return;
        }

        if (!isRetry && activeNotificationId === notificationId && getDetailBody().children().length && !getDetailBody().find(".notification-panel-placeholder, .notification-side-panel-loading").length) {
            setActiveItem(notificationId);
            setPanelState(true);
            return;
        }

        activeNotificationId = notificationId;
        setActiveItem(notificationId);
        setPanelState(true);
        updateNotificationUrl(notificationId);
        showPanelLoading();
        markItemRead(notificationId);
        syncCsrf();

        if (panelXhr && panelXhr.readyState !== 4) {
            panelXhr.abort();
        }

        var onPanelError = function (status) {
            if (status === "abort") {
                return;
            }
            if (!isRetry) {
                syncCsrf();
                setTimeout(function () {
                    openNotification(notificationId, ticketId, taskId, true);
                }, 400);
                return;
            }
            if (ticketId || taskId) {
                loadNotificationPanel(notificationId, false, true);
                return;
            }
            showPanelLoadError(notificationId);
        };

        if (ticketId) {
            panelXhr = $.ajax({
                url: AppHelper.baseUrl + "index.php/tickets/view",
                type: "POST",
                data: {
                    id: ticketId,
                    view_type: "panel_view"
                },
                success: function (response) {
                    if (!renderPanelHtml(response)) {
                        return;
                    }
                    initEmbeddedPanelContent();
                    markNotificationReadOnServer(notificationId);
                },
                error: function (_, status) {
                    onPanelError(status);
                }
            });
            return;
        }

        if (taskId) {
            panelXhr = $.ajax({
                url: AppHelper.baseUrl + "index.php/tasks/view",
                type: "POST",
                data: {
                    id: taskId,
                    view_type: "panel_view"
                },
                success: function (response) {
                    if (!renderPanelHtml(response)) {
                        return;
                    }
                    initEmbeddedPanelContent();
                    markNotificationReadOnServer(notificationId);
                },
                error: function (_, status) {
                    onPanelError(status);
                }
            });
            return;
        }

        loadNotificationPanel(notificationId, true, isRetry);
    }

    function loadNotificationPanel(notificationId, allowUpgrade, isRetry) {
        if (typeof allowUpgrade === "undefined") {
            allowUpgrade = true;
        }
        isRetry = !!isRetry;
        syncCsrf();

        panelXhr = $.ajax({
            url: window.notificationPanelUrl || (AppHelper.baseUrl + "index.php/notifications/view_panel"),
            type: "POST",
            data: { id: notificationId },
            success: function (response) {
                if (responseLooksLikeAppShell(response)) {
                    if (!window._primeSessionReloadScheduled) {
                        window._primeSessionReloadScheduled = true;
                        window.location.reload();
                    }
                    return;
                }

                var $parsed = $("<div>").html(response);
                var $view = $parsed.find(".notification-panel-view").first();
                var taskId = parseInt($view.attr("data-task-id"), 10) || 0;
                var ticketId = parseInt($view.attr("data-ticket-id"), 10) || 0;

                // Prefer full task/ticket panel when notification points to one
                if (allowUpgrade && (taskId || ticketId)) {
                    activeNotificationId = null;
                    openNotification(notificationId, ticketId, taskId, isRetry);
                    return;
                }

                getDetailBody().html(response);
                ensureMarkUnreadControl(getDetailBody());
                if (typeof feather !== "undefined") {
                    feather.replace();
                }
            },
            error: function (_, status) {
                if (status === "abort") {
                    return;
                }
                if (!isRetry) {
                    syncCsrf();
                    setTimeout(function () {
                        loadNotificationPanel(notificationId, allowUpgrade, true);
                    }, 400);
                    return;
                }
                showPanelLoadError(notificationId);
            }
        });
    }

    function closeNotificationPanel() {
        activeNotificationId = null;

        if (panelXhr && panelXhr.readyState !== 4) {
            panelXhr.abort();
        }

        setPanelState(false);
        getDetailBody().html('<div class="notification-panel-placeholder">Выберите событие из списка</div>');
        setActiveItem(null);
        updateNotificationUrl(null);
    }

    function bindEvents() {
        $(document).on("click", ".js-notification-inbox-item", function (event) {
            if ($(event.target).closest("a").length) {
                return;
            }
            event.preventDefault();
            openNotification($(this).attr("data-id"), $(this).attr("data-ticket-id"), $(this).attr("data-task-id"));
        });

        $(document).on("keydown", ".js-notification-inbox-item", function (event) {
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }
            if ($(event.target).closest("a").length) {
                return;
            }
            event.preventDefault();
            openNotification($(this).attr("data-id"), $(this).attr("data-ticket-id"), $(this).attr("data-task-id"));
        });

        $(document).on("click", ".notifications-inbox-preview-link", function (event) {
            event.stopPropagation();
        });

        $(document).on("click", ".js-notifications-load-more", function () {
            loadInbox(false);
        });

        $(document).on("click", ".notifications-list-page .js-notification-panel-close, .notifications-list-page .js-ticket-panel-close, .notifications-list-page .js-task-panel-close", function () {
            closeNotificationPanel();
        });

        $(document).on("click", ".notifications-list-page .js-notification-panel-retry", function (event) {
            event.preventDefault();
            event.stopPropagation();
            var notificationId = $(this).attr("data-id") || activeNotificationId;
            var $item = $(".js-notification-inbox-item[data-id='" + notificationId + "']");
            if (!$item.length) {
                $item = $(".js-notification-inbox-item.is-active").first();
            }
            openNotification(
                notificationId || $item.attr("data-id"),
                $item.attr("data-ticket-id"),
                $item.attr("data-task-id"),
                true
            );
        });

        $(document).on("click", ".notifications-list-page .js-notification-mark-unread", function (event) {
            event.preventDefault();
            event.stopPropagation();

            var notificationId = activeNotificationId;
            if (!notificationId) {
                notificationId = $(this).closest("[data-notification-id]").attr("data-notification-id");
            }

            setNotificationUnread(notificationId);
        });

        $(document).on("click", ".notifications-list-page .js-mark-all-notifications-read", function (event) {
            event.preventDefault();

            var url = $(this).attr("data-action-url");
            var $modal = $("#confirmationModal");
            if (!$modal.length) {
                return;
            }

            var $confirm = $("#confirmDeleteButton");
            var originalTitle = $("#confirmationModalTitle").html();
            var originalContent = $("#confirmationModalContent .container-fluid").html();
            var originalConfirmHtml = $confirm.html();
            var originalConfirmClass = $confirm.attr("class");

            $("#confirmationModalTitle").text("Отметить все как прочтенное?");
            $("#confirmationModalContent .container-fluid").text("Все непрочитанные события хроники будут отмечены как прочитанные.");
            $confirm
                .html('<i data-feather="check-circle" class="icon-16"></i> Подтвердить')
                .removeClass("btn-danger")
                .addClass("btn-primary")
                .off("click.markAllRead")
                .on("click.markAllRead", function () {
                    appLoader.show();
                    $.ajax({
                        url: url,
                        type: "POST",
                        dataType: "json",
                        success: function (result) {
                            appLoader.hide();
                            if (result && result.success) {
                                if (window.NotificationInbox) {
                                    window.NotificationInbox.close();
                                    window.NotificationInbox.reload();
                                } else {
                                    location.reload();
                                }
                                if (result.message && typeof appAlert !== "undefined") {
                                    appAlert.success(result.message, { duration: 3000 });
                                }
                            } else if (typeof appAlert !== "undefined") {
                                appAlert.error((result && result.message) || "Не удалось отметить уведомления");
                            }
                        },
                        error: function () {
                            appLoader.hide();
                            if (typeof appAlert !== "undefined") {
                                appAlert.error("Не удалось отметить уведомления");
                            }
                        }
                    });
                });

            if (typeof feather !== "undefined") {
                feather.replace();
            }

            $modal.off("hidden.bs.modal.markAllRead").on("hidden.bs.modal.markAllRead", function () {
                $("#confirmationModalTitle").html(originalTitle);
                $("#confirmationModalContent .container-fluid").html(originalContent);
                $confirm.attr("class", originalConfirmClass).html(originalConfirmHtml).off("click.markAllRead");
                $modal.off("hidden.bs.modal.markAllRead");
            });

            $modal.modal("show");
        });

        $(".notifications-inbox-status-tabs").on("click", "button[data-tab]", function () {
            var tab = $(this).attr("data-tab");
            if (tab === state.tab) {
                return;
            }

            state.tab = tab;
            $(".notifications-inbox-status-tabs button").removeClass("is-active");
            $(this).addClass("is-active");
            closeNotificationPanel();
            loadInbox(true);
        });

        getListEl().on("scroll", function () {
            var el = this;
            if (!state.hasMore || state.loading) {
                return;
            }

            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 80) {
                loadInbox(false);
            }
        });

        $(document).on("keydown", function (event) {
            if (event.key !== "Escape" || !activeNotificationId) {
                return;
            }
            // Let image lightbox / modals handle Esc first (Magnific uses keyup)
            if ($(".mfp-ready, .mfp-wrap, .app-modal").length) {
                return;
            }
            if ($.magnificPopup && $.magnificPopup.instance && $.magnificPopup.instance.isOpen) {
                return;
            }
            if ($(".modal.show").length) {
                return;
            }
            closeNotificationPanel();
        });

        // Pasted screenshots without <a> (legacy / preview=false) — open Magnific on img click
        $(document).on("click", ".notifications-list-page .timeline-images:not(.app-modal-view) img.pasted-image", function (event) {
            if ($(this).closest("a").length) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();

            if (typeof $.magnificPopup !== "object" || typeof $.magnificPopup.open !== "function") {
                return;
            }

            var $imgs = $(this).closest(".notification-panel-description, .notification-panel-body, .comment, .mb-5, #notification-detail-body")
                .find(".timeline-images:not(.app-modal-view) img.pasted-image");
            if (!$imgs.length) {
                $imgs = $(this);
            }

            var items = [];
            var index = 0;
            var clickedSrc = $(this).attr("src");
            $imgs.each(function (i) {
                var src = $(this).attr("src");
                if (!src) {
                    return;
                }
                if (src === clickedSrc) {
                    index = items.length;
                }
                items.push({
                    src: src,
                    title: $(this).attr("alt") || ""
                });
            });

            if (!items.length) {
                return;
            }

            $.magnificPopup.open({
                items: items,
                type: "image",
                index: index,
                closeOnContentClick: false,
                closeBtnInside: false,
                mainClass: "mfp-with-zoom mfp-img-mobile",
                gallery: { enabled: items.length > 1 }
            });
        });
    }

    function openFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var notificationId = params.get("notification");

        if (!notificationId) {
            return;
        }

        // Prefer "all" when deep-linking, so read items are still listed
        if (state.tab === "unread") {
            state.tab = "all";
            $(".notifications-inbox-status-tabs button").removeClass("is-active");
            $(".notifications-inbox-status-tabs button[data-tab='all']").addClass("is-active");
        }

        // Wait for list load, then open; ticket_id comes from list item if present
        var tryOpen = function () {
            var $item = $(".js-notification-inbox-item[data-id='" + notificationId + "']");
            if (!$item.length) {
                $item = $(".js-notification-inbox-item").filter(function () {
                    var ids = String($(this).attr("data-ids") || "").split(",");
                    return ids.indexOf(String(notificationId)) !== -1;
                }).first();
            }
            if ($item.length) {
                openNotification(notificationId, $item.attr("data-ticket-id"), $item.attr("data-task-id"));
                return;
            }
            openNotification(notificationId, 0, 0);
        };

        // List load is async; open after first load completes via short poll
        var attempts = 0;
        var timer = setInterval(function () {
            attempts += 1;
            if (!state.loading || attempts > 40) {
                clearInterval(timer);
                tryOpen();
            }
        }, 100);
    }

    $(function () {
        if (!$(".notifications-list-page").length) {
            return;
        }

        bindEvents();
        loadInbox(true);
        openFromUrl();
    });

    window.NotificationInbox = {
        reload: function () {
            loadInbox(true);
        },
        open: openNotification,
        close: closeNotificationPanel
    };
})(jQuery);
