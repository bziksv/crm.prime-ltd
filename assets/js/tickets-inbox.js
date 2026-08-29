(function ($) {
    "use strict";

    var PAGE_SIZE = 30;
    var searchTimer = null;
    var listXhr = null;

    var state = {
        skip: 0,
        hasMore: true,
        loading: false,
        status: "open",
        search: "",
        lastDateGroup: null
    };

    function getListEl() {
        return $("#tickets-inbox-list");
    }

    function getLoadMoreEl() {
        return $("#tickets-inbox-load-more");
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
            return '<div class="tickets-inbox-avatar"><img src="' + item.avatar_url + '" alt=""></div>';
        }

        var initial = item.avatar_initial || "?";
        return '<div class="tickets-inbox-avatar" data-hue="' + avatarHue(initial) + '">' + $('<div>').text(initial).html() + "</div>";
    }

    function buildItemHtml(item) {
        var unreadClass = item.is_unread ? " is-unread" : "";
        var preview = item.preview ? $('<div>').text(item.preview).html() : "";
        var badge = item.is_unread ? '<span class="tickets-inbox-badge">!</span>' : "";
        var title = $('<div>').text(item.title || "").html();
        var client = $('<div>').text(item.client_name || "").html();
        var project = $('<div>').text(item.project_title || "").html();
        var ticketId = $('<div>').text(item.ticket_id || "").html();
        var statusLabel = $('<span>').text(item.status_label || "").html();

        var projectHtml = project
            ? '<div class="tickets-inbox-project">' + project + "</div>"
            : "";
        var clientHtml = client
            ? '<div class="tickets-inbox-client">' + client + "</div>"
            : "";
        var previewHtml = preview
            ? '<div class="tickets-inbox-item-preview">' + preview + "</div>"
            : "";

        return (
            '<button type="button" class="tickets-inbox-item js-ticket-inbox-item' + unreadClass + '" data-id="' + item.id + '" data-date-group="' + getDateGroupLabel(item.last_activity_at) + '">' +
                buildAvatar(item) +
                '<div class="tickets-inbox-item-body">' +
                    '<div class="tickets-inbox-item-top">' +
                        '<div class="tickets-inbox-item-title">' + title + "</div>" +
                        '<span class="tickets-inbox-item-meta">' + badge + '<span class="tickets-inbox-time">' + (item.time_label || "") + "</span></span>" +
                    "</div>" +
                    projectHtml +
                    clientHtml +
                    previewHtml +
                    '<div class="tickets-inbox-item-footer">' +
                        '<span class="tickets-inbox-status">' +
                            '<i class="tickets-inbox-status-dot status-' + (item.status || "open") + '"></i>' +
                            statusLabel +
                        "</span>" +
                        (ticketId ? '<span class="tickets-inbox-ticket-id">' + ticketId + "</span>" : "") +
                    "</div>" +
                "</div>" +
            "</button>"
        );
    }

    function appendItems(items, reset) {
        var $list = getListEl();

        if (reset) {
            $list.empty();
            state.lastDateGroup = null;
        }

        if (!items || !items.length) {
            if (reset) {
                $list.html('<div class="tickets-inbox-empty">Заявок не найдено</div>');
            }
            return;
        }

        var html = "";

        items.forEach(function (item) {
            var group = getDateGroupLabel(item.last_activity_at);
            if (group && group !== state.lastDateGroup) {
                html += '<div class="tickets-inbox-date-group">' + group + "</div>";
                state.lastDateGroup = group;
            }
            html += buildItemHtml(item);
        });

        $list.append(html);
    }

    function setLoading(isLoading) {
        state.loading = isLoading;
        var $loadMore = getLoadMoreEl();

        if (isLoading && state.skip === 0) {
            getListEl().html('<div class="tickets-inbox-loading"><span class="spinning-btn spinning"></span></div>');
        }

        if (isLoading && state.skip > 0) {
            $loadMore.html('<span class="spinning-btn spinning"></span>');
        }
    }

    function updateLoadMore(hasMore) {
        state.hasMore = !!hasMore;
        var $loadMore = getLoadMoreEl();

        if (!hasMore) {
            $loadMore.empty();
            return;
        }

        $loadMore.html('<button type="button" class="btn btn-default btn-sm" id="tickets-inbox-load-more-btn">Загрузить ещё</button>');
    }

    function getFilterPayload() {
        var payload = {
            skip: state.skip,
            limit: PAGE_SIZE,
            status: state.status,
            search_by: state.search
        };

        if ($("#ticket-table").length && $("#ticket-table").closest(".tickets-table-view").hasClass("is-visible")) {
            return payload;
        }

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

        if (listXhr && listXhr.readyState !== 4) {
            listXhr.abort();
        }

        listXhr = $.ajax({
            url: window.ticketInboxListUrl || (AppHelper.baseUrl + "index.php/tickets/inbox_list_data"),
            type: "POST",
            dataType: "json",
            data: getFilterPayload(),
            success: function (response) {
                setLoading(false);

                if (!response || !response.success) {
                    if (reset) {
                        getListEl().html('<div class="tickets-inbox-empty">Не удалось загрузить список</div>');
                    }
                    return;
                }

                appendItems(response.data, reset);
                state.skip += (response.data || []).length;
                updateLoadMore(response.hasMore);

                if (window.TicketSidePanel && TicketSidePanel.refreshActiveItem) {
                    TicketSidePanel.refreshActiveItem();
                }
            },
            error: function (_, status) {
                setLoading(false);
                if (status === "abort") {
                    return;
                }

                if (reset) {
                    getListEl().html('<div class="tickets-inbox-empty">Не удалось загрузить список</div>');
                }
            }
        });
    }

    function adjustTicketTable() {
        var $table = $("#ticket-table");

        if (!$table.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable($table[0])) {
            return;
        }

        var dt = $table.DataTable();
        dt.columns.adjust();

        if (dt.responsive && typeof dt.responsive.recalc === "function") {
            dt.responsive.recalc();
        }

        $(window).trigger("resize");
    }

    function setTicketsView(mode) {
        mode = mode === "table" ? "table" : "inbox";

        var $page = $("#tickets-page-content");

        if (mode === "table") {
            $page.removeClass("view-inbox has-ticket-open").addClass("view-table");
            window.setTimeout(function () {
                adjustTicketTable();
            }, 40);
            return;
        }

        $page.removeClass("view-table").addClass("view-inbox");

        var $list = getListEl();
        if (!$list.children().length || $list.find(".tickets-inbox-empty, .tickets-inbox-loading").length) {
            loadInbox(true);
        }

        if (window.TicketSidePanel && TicketSidePanel.refreshActiveItem) {
            TicketSidePanel.refreshActiveItem();
        }
    }

    function bindInboxEvents() {
        $(document).on("click", ".js-ticket-inbox-item", function (event) {
            if (!(event.ctrlKey || event.metaKey || event.shiftKey || event.which === 2)) {
                event.preventDefault();
                if (window.TicketSidePanel) {
                    TicketSidePanel.open($(this).attr("data-id"));
                }
                return;
            }

            var ticketId = $(this).attr("data-id");
            if (ticketId) {
                window.open(AppHelper.baseUrl + "index.php/tickets/view/" + ticketId, "_blank");
            }
        });

        $(document).on("input", "#tickets-inbox-search", function () {
            var value = $(this).val();

            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                state.search = $.trim(value);
                loadInbox(true);
            }, 350);
        });

        $(document).on("click", ".tickets-inbox-status-tabs button", function () {
            var $btn = $(this);
            if ($btn.hasClass("is-active")) {
                return;
            }

            $(".tickets-inbox-status-tabs button").removeClass("is-active");
            $btn.addClass("is-active");
            state.status = $btn.attr("data-status") || "open";
            loadInbox(true);
        });

        $(document).on("click", "#tickets-inbox-load-more-btn", function () {
            loadInbox(false);
        });

        getListEl().on("scroll", function () {
            if (!state.hasMore || state.loading) {
                return;
            }

            var el = this;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 80) {
                loadInbox(false);
            }
        });
    }

    window.TicketInbox = {
        load: loadInbox,
        reload: function () {
            loadInbox(true);
        },
        getStatus: function () {
            return state.status;
        },
        setView: setTicketsView,
        adjustTable: adjustTicketTable
    };

    function lockTicketsViewport() {
        window.scrollTo(0, 0);
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;

        var mask = document.getElementById("left-menu-toggle-mask");
        if (mask) {
            mask.scrollTop = 0;
            mask.style.setProperty("overflow", "hidden", "important");
            mask.style.setProperty("overflow-y", "hidden", "important");
            mask.style.setProperty("height", "100vh", "important");
            mask.style.setProperty("max-height", "100vh", "important");
        }

        var pageContainer = document.querySelector(".page-container");
        if (pageContainer) {
            pageContainer.style.setProperty("overflow", "hidden", "important");
            pageContainer.style.setProperty("height", "calc(100vh - 65px)", "important");
            pageContainer.style.setProperty("max-height", "calc(100vh - 65px)", "important");
        }
    }

    $(document).ready(function () {
        if (!$("#tickets-page-content").length) {
            return;
        }

        lockTicketsViewport();
        setTimeout(lockTicketsViewport, 0);
        setTimeout(lockTicketsViewport, 300);

        state.status = $(".tickets-inbox-status-tabs button.is-active").attr("data-status") || "open";

        bindInboxEvents();

        // Preload inbox data so opening a ticket from table is instant.
        loadInbox(true);
    });
})(jQuery);
