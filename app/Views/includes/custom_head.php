<?php

load_js(array(
    "assets/js/custom-summernote-autolink.js",
    "assets/js/notification_handler.js",
));
?>
<script type="text/javascript">
    window.primeSyncMessagesSidebarBadge = function (total) {
        total = parseInt(total, 10) || 0;
        var $link = $(".sidebar-menu a[href*='messages']").filter(function () {
            var href = ($(this).attr("href") || "").replace(/\/$/, "");
            return /\/messages$/.test(href) || /\/messages\/inbox$/.test(href) || /messages\/inbox\/?$/.test(href) || /\/messages\/?$/.test(href);
        }).first();
        if (!$link.length) {
            $link = $(".sidebar-menu a[href*='/messages']").first();
        }
        if (!$link.length) {
            return;
        }
        var $badge = $link.find("> .badge, > span.badge").first();
        if (total > 0) {
            if ($badge.length) {
                $badge.text(String(total));
            } else {
                $link.append(" <span class='badge rounded-pill bg-primary'>" + total + "</span>");
            }
        } else if ($badge.length) {
            $badge.remove();
        }
    };
</script>
