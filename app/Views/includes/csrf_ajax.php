<?php

if (get_setting("csrf_protection")) {
    ?>
    <script>
        window.primeSyncCsrf = function () {
            if (!AppHelper.csrfTokenName) {
                return;
            }

            var cookieName = AppHelper.csrfCookieName || "rise_csrf_cookie";
            var match = document.cookie.match(new RegExp("(?:^|; )" + cookieName.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + "=([^;]*)"));
            if (match) {
                AppHelper.csrfHash = decodeURIComponent(match[1]);
            }

            var data = {};
            data[AppHelper.csrfTokenName] = AppHelper.csrfHash;
            $.ajaxSetup({ data: data });
        };

        window.primeSyncCsrf();
    </script>
    <?php

}
