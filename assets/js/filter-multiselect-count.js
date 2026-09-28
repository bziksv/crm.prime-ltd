/**
 * Show selected count on multi-select filter buttons.
 * Works with current app.all.js without rebuilding the bundle.
 */
(function ($, window) {
    "use strict";

    function escapeHtml(text) {
        return $("<div>").text(text == null ? "" : String(text)).html();
    }

    function getBaseLabel($btn) {
        var stored = $btn.attr("data-filter-label");
        if (stored) {
            return stored;
        }

        var clone = $btn.clone();
        clone.find(".filter-selected-count").remove();
        var text = $.trim(clone.text()).replace(/\s*\(\d+\)\s*$/, "");
        $btn.attr("data-filter-label", text);
        return text;
    }

    window.updateFilterMultiSelectCount = function ($dropdown) {
        $dropdown = $($dropdown);
        if (!$dropdown.length || !$dropdown.hasClass("filter-multi-select")) {
            return;
        }

        var $btn = $dropdown.children(".dropdown-toggle").first();
        if (!$btn.length) {
            $btn = $dropdown.find("> button.dropdown-toggle, button.dropdown-toggle").first();
        }
        if (!$btn.length) {
            return;
        }

        var baseLabel = getBaseLabel($btn);
        var count = $dropdown.find("ul[data-act='multiselect'] li.active, ul.list-group li.active").length;
        var $box = $dropdown.closest(".filter-item-box");

        if (count > 0) {
            $btn.html(
                '<span class="filter-label-text">' + escapeHtml(baseLabel) + "</span>"
                + '<span class="filter-selected-count">' + count + "</span>"
            );
            $btn.addClass("has-filter-selection");
            $box.addClass("has-filter-selection");
        } else {
            $btn.html('<span class="filter-label-text">' + escapeHtml(baseLabel) + "</span>");
            $btn.removeClass("has-filter-selection");
            $box.removeClass("has-filter-selection");
        }
    };

    window.refreshFilterMultiSelectCounts = function ($root) {
        ($root && $root.length ? $root : $(document)).find(".filter-multi-select").each(function () {
            window.updateFilterMultiSelectCount(this);
        });
    };

    $(document).on("click", ".filter-multi-select li.clickable", function () {
        var $dropdown = $(this).closest(".filter-multi-select");
        setTimeout(function () {
            window.updateFilterMultiSelectCount($dropdown);
        }, 0);
    });

    function watchFilterContainers() {
        var targets = document.querySelectorAll(
            ".filter-form, .filter-section-container, #js-kanban-filter-container, #kanban-filters, .filter-section-left"
        );
        if (!targets.length) {
            return;
        }

        var scheduled = false;
        var observer = new MutationObserver(function (mutations) {
            var shouldRefresh = false;
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                if (m.type === "childList") {
                    // New filter widgets appeared, or list items rebuilt.
                    if ($(m.target).closest(".filter-multi-select .dropdown-toggle").length) {
                        continue; // ignore our own button label updates
                    }
                    shouldRefresh = true;
                    break;
                }
                if (m.type === "attributes" && m.attributeName === "class") {
                    var el = m.target;
                    if (el && el.tagName === "LI" && el.classList && (el.classList.contains("clickable") || el.classList.contains("list-group-item"))) {
                        shouldRefresh = true;
                        break;
                    }
                }
            }
            if (!shouldRefresh || scheduled) {
                return;
            }
            scheduled = true;
            setTimeout(function () {
                scheduled = false;
                window.refreshFilterMultiSelectCounts();
            }, 30);
        });

        Array.prototype.forEach.call(targets, function (el) {
            if (el.getAttribute("data-filter-count-watched") === "1") {
                return;
            }
            el.setAttribute("data-filter-count-watched", "1");
            observer.observe(el, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ["class"]
            });
        });
    }

    $(function () {
        window.refreshFilterMultiSelectCounts();
        watchFilterContainers();

        // Filters are often built slightly after ready / after smart-filter restore.
        setTimeout(function () {
            window.refreshFilterMultiSelectCounts();
            watchFilterContainers();
        }, 300);
        setTimeout(function () {
            window.refreshFilterMultiSelectCounts();
            watchFilterContainers();
        }, 1000);
    });
})(jQuery, window);
