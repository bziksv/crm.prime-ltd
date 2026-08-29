(function ($) {
    "use strict";

    var panelXhr = null;
    var activeTicketId = null;

    function getPanelElements() {
        return {
            $page: $("#tickets-page-content"),
            $body: $("#ticket-side-panel-body")
        };
    }

    function getTicketTable() {
        var $table = $("#ticket-table");

        if (!$table.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable($table[0])) {
            return null;
        }

        return $table.DataTable();
    }

    function restoreTicketTableColumnsIfNeeded() {
        var dt = getTicketTable();
        var cfg = window.ticketTableRestoreConfig;

        if (!dt || !cfg) {
            return;
        }

        var visibleCount = 0;

        dt.columns().every(function (index) {
            if (index > 0 && this.visible()) {
                visibleCount++;
            }
        });

        if (visibleCount > 1) {
            return;
        }

        var lastIndex = dt.columns().count() - 1;

        dt.columns().every(function (index) {
            var show = cfg.hiddenIndexes.indexOf(index) === -1;

            if (index === 4 && cfg.hideProjectColumn) {
                show = false;
            }

            if (index === lastIndex && cfg.hideOptionsColumn) {
                show = false;
            }

            this.visible(show);
        });

        dt.columns.adjust().draw(false);
    }

    function updateTicketUrl(ticketId) {
        if (!window.history || !window.history.replaceState) {
            return;
        }

        var url = new URL(window.location.href);

        if (ticketId) {
            url.searchParams.set("ticket", ticketId);
        } else {
            url.searchParams.delete("ticket");
        }

        window.history.replaceState({}, "", url.toString());
    }

    function setActiveItem(ticketId) {
        $(".js-ticket-inbox-item").removeClass("is-active");
        $("#ticket-table tbody tr.ticket-list-row").removeClass("is-active");

        if (!ticketId) {
            return;
        }

        $(".js-ticket-inbox-item[data-id='" + ticketId + "']").addClass("is-active");
        $("#ticket-table tbody tr.ticket-list-row[data-ticket-id='" + ticketId + "']").addClass("is-active");
    }

    function setPanelState(hasTicket) {
        var els = getPanelElements();

        if (hasTicket) {
            els.$page.addClass("has-ticket-open");
        } else {
            els.$page.removeClass("has-ticket-open");
        }
    }

    function positionAsideActionsMenu($dropdown) {
        var $btn = $dropdown.find("[data-bs-toggle='dropdown']").first();
        var $menu = $dropdown.data("floating-menu") || $dropdown.find("> .dropdown-menu").first();

        if (!$btn.length || !$menu.length) {
            return;
        }

        var rect = $btn[0].getBoundingClientRect();
        var width = Math.min(Math.max(Math.round(rect.width), 240), 280);
        var left = Math.min(Math.max(8, Math.round(rect.right) - width), window.innerWidth - width - 8);
        var top = Math.round(rect.bottom) + 4;

        if (!$menu.parent().is("body")) {
            $menu.appendTo("body");
        }

        $dropdown.data("floating-menu", $menu);
        $menu.removeClass("w-100")
            .addClass("ticket-aside-actions-menu-floating show")
            .css({
                top: top + "px",
                left: left + "px",
                width: width + "px",
                minWidth: width + "px",
                maxWidth: width + "px"
            });
    }

    function restoreAsideActionsMenu($dropdown) {
        var $menu = $dropdown.data("floating-menu");
        if (!$menu || !$menu.length) {
            $menu = $("body > .ticket-aside-actions-menu-floating");
        }

        if ($menu.length) {
            $menu.removeClass("ticket-aside-actions-menu-floating show")
                .removeAttr("style")
                .appendTo($dropdown);
        }

        $dropdown.removeData("floating-menu");
    }

    function initAsideActionsDropdown() {
        var $buttons = $("#ticket-side-panel-body .ticket-panel-aside-actions [data-bs-toggle='dropdown']");

        $buttons.each(function () {
            var existing = bootstrap.Dropdown.getInstance(this);
            if (existing) {
                existing.dispose();
            }
            new bootstrap.Dropdown(this, { display: "static", autoClose: true });
        });
    }

    function initPanelContent() {
        if (typeof setSummernoteToAll === "function") {
            setSummernoteToAll(true);
        }

        if (typeof feather !== "undefined") {
            feather.replace();
        }

        if (typeof selectLastlySelectedTab === "function") {
            selectLastlySelectedTab("#ticket-side-panel-body");
        }

        initAsideActionsDropdown();
    }

    function showPanelLoading() {
        var els = getPanelElements();
        els.$body.html('<div class="ticket-side-panel-loading"><span class="spinning-btn spinning"></span> Загрузка...</div>');
    }

    function openTicketPanel(ticketId) {
        ticketId = parseInt(ticketId, 10);

        if (!ticketId) {
            return;
        }

        // Opening a ticket uses list+detail layout.
        if (window.TicketInbox && TicketInbox.setView) {
            TicketInbox.setView("inbox");
        }

        if (activeTicketId === ticketId && getPanelElements().$body.find(".ticket-panel-view").length) {
            setActiveItem(ticketId);
            setPanelState(true);
            return;
        }

        activeTicketId = ticketId;
        setActiveItem(ticketId);
        setPanelState(true);
        updateTicketUrl(ticketId);
        showPanelLoading();

        if (panelXhr && panelXhr.readyState !== 4) {
            panelXhr.abort();
        }

        panelXhr = $.ajax({
            url: AppHelper.baseUrl + "index.php/tickets/view",
            type: "POST",
            data: {
                id: ticketId,
                view_type: "panel_view"
            },
            success: function (response) {
                getPanelElements().$body.html(response);
                initPanelContent();
            },
            error: function (xhr, status) {
                if (status === "abort") {
                    return;
                }

                getPanelElements().$body.html('<div class="ticket-side-panel-placeholder">Не удалось загрузить заявку</div>');
            }
        });
    }

    function closeTicketPanel() {
        activeTicketId = null;

        if (panelXhr && panelXhr.readyState !== 4) {
            panelXhr.abort();
        }

        $("body > .ticket-aside-actions-menu-floating").remove();

        var els = getPanelElements();
        setPanelState(false);
        els.$body.html('<div class="ticket-side-panel-placeholder">Выберите заявку из списка</div>');
        setActiveItem(null);
        updateTicketUrl(null);

        // X / Esc returns to full table view.
        if (window.TicketInbox && TicketInbox.setView) {
            TicketInbox.setView("table");
        }
    }

    function shouldOpenInPanel(event) {
        return !(event.ctrlKey || event.metaKey || event.shiftKey || event.which === 2);
    }

    function bindTicketPanelEvents() {
        $(document).on("click", ".js-ticket-open-panel", function (event) {
            if (!shouldOpenInPanel(event)) {
                return;
            }

            event.preventDefault();
            openTicketPanel($(this).attr("data-id"));
        });

        $("#ticket-table").on("click", "tbody tr.ticket-list-row", function (event) {
            if (!shouldOpenInPanel(event)) {
                return;
            }

            if ($(event.target).closest("a:not(.js-ticket-open-panel), button, .dropdown, .dropdown-menu, input, label, .action-option").length) {
                return;
            }

            var ticketId = $(this).attr("data-ticket-id");
            if (ticketId) {
                event.preventDefault();
                openTicketPanel(ticketId);
            }
        });

        $(document).on("click", ".js-ticket-panel-close", function () {
            closeTicketPanel();
        });

        $(document).on("show.bs.dropdown", ".ticket-panel-aside-actions .dropdown", function () {
            positionAsideActionsMenu($(this));
        });

        $(document).on("hide.bs.dropdown", ".ticket-panel-aside-actions .dropdown", function () {
            restoreAsideActionsMenu($(this));
        });

        $(window).on("resize.ticketAsideActions scroll.ticketAsideActions", function () {
            var $open = $(".ticket-panel-aside-actions .dropdown.show");
            if ($open.length) {
                positionAsideActionsMenu($open);
            }
        });

        $(document).on("keydown", function (event) {
            if (event.key !== "Escape" || !activeTicketId) {
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
            closeTicketPanel();
        });
    }

    window.TicketSidePanel = {
        open: openTicketPanel,
        close: closeTicketPanel,
        restoreColumns: restoreTicketTableColumnsIfNeeded,
        setRowCallback: function (nRow, aData) {
            if (!aData || typeof aData[0] === "undefined") {
                return;
            }

            $(nRow).addClass("ticket-list-row").attr("data-ticket-id", aData[0]);
        },
        openFromUrl: function () {
            var params = new URLSearchParams(window.location.search);
            var ticketId = params.get("ticket");

            if (ticketId) {
                openTicketPanel(ticketId);
            }
        },
        refreshActiveItem: function () {
            if (!activeTicketId) {
                return;
            }

            setActiveItem(activeTicketId);
        }
    };

    $(document).ready(function () {
        bindTicketPanelEvents();

        if ($("#tickets-page-content").length) {
            TicketSidePanel.openFromUrl();
        }
    });
})(jQuery);
