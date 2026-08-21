<?php
// Floating chat UI — staff use new Chat (DM + groups), clients keep classic Rise messages.
$can_chat = can_access_messages_module();

if (get_setting("module_chat") && $can_chat) {
    $is_staff_chat = isset($login_user) && $login_user->user_type === 'staff';
    $chat_list_url = $is_staff_chat ? get_uri('chat/panel') : get_uri('messages/chat_list');
    $inbox_uri = get_uri('messages/inbox');
    ?>
    <div id="js-init-chat-icon" class="init-chat-icon">
        <!-- data-type= open/close/unread -->
        <span id="js-chat-min-icon" data-type="open" class="chat-min-icon"><i data-feather="message-circle" class="icon-18"></i></span>
    </div>

    <div id="js-rise-chat-wrapper" class="rise-chat-wrapper hide <?php echo $is_staff_chat ? 'is-prime-chat' : ''; ?>"></div>

    <script type="text/javascript">
        $(document).ready(function () {

            var isStaffChat = <?php echo $is_staff_chat ? 'true' : 'false'; ?>;
            window.primeChatListUrl = <?php echo json_encode($chat_list_url); ?>;
            var inboxUri = <?php echo json_encode($inbox_uri); ?>;

            if (typeof window.openPrimeConversation !== 'function') {
                window.openPrimeConversation = function (conversationId) {
                    conversationId = parseInt(conversationId, 10) || 0;
                    if (!conversationId) {
                        window.location.href = inboxUri;
                        return;
                    }
                    window.location.href = inboxUri.replace(/\/$/, '') + '/' + conversationId;
                };
            }

            chatIconContent = {
                "open": "<i data-feather='message-circle' class='icon-18'></i>",
                "close": "<span class='chat-close'>&times;</span>",
                "unread": ""
            };

            //we'll wait for 15 sec after clicking on the unread icon to see more notifications again.

            setChatIcon = function (type, count) {

                //don't show count if the data-prevent-notification-count is 1
                if ($("#js-chat-min-icon").attr("data-prevent-notification-count") === "1" && type === "unread") {
                    return false;
                }


                $("#js-chat-min-icon").attr("data-type", type).html(count ? count : chatIconContent[type]);

                if (type === "open") {
                    $("#js-rise-chat-wrapper").addClass("hide"); //hide chat box
                    $("#js-init-chat-icon").removeClass("has-message");
                } else if (type === "close") {
                    $("#js-rise-chat-wrapper").removeClass("hide"); //show chat box
                    $("#js-init-chat-icon").removeClass("has-message");
                } else if (type === "unread") {
                    $("#js-init-chat-icon").addClass("has-message");
                }

            };

            changeChatIconPosition = function (type) {
                if (type === "close") {
                    $("#js-init-chat-icon").addClass("move-chat-icon");
                } else if (type === "open") {
                    $("#js-init-chat-icon").removeClass("move-chat-icon");
                }
            };

            //is there any active chat? open the popup
            //otherwise show the chat icon only
            var activeChatId = getCookie("active_chat_id"),
                    isChatBoxOpen = getCookie("chatbox_open"),
                    $chatIcon = $("#js-init-chat-icon");


            $chatIcon.click(function () {
                $("#js-rise-chat-wrapper").html("");

                if (typeof window.updateLastMessageCheckingStatus === "function") {
                    window.updateLastMessageCheckingStatus();
                }
                if (isStaffChat) {
                    pollPrimeChatUnread();
                }

                var $chatIcon = $("#js-chat-min-icon");

                if ($chatIcon.attr("data-type") === "unread") {
                    $chatIcon.attr("data-prevent-notification-count", "1");

                    //after clicking on the unread icon, we'll wait 11 sec to show more notifications again.
                    setTimeout(function () {
                        $chatIcon.attr("data-prevent-notification-count", "0");
                    }, 11000);
                }

                var windowSize = window.matchMedia("(max-width: 767px)");

                if ($chatIcon.attr("data-type") !== "close") {
                    //have to reload
                    setTimeout(function () {
                        loadChatTabs();
                    }, 200);
                    setChatIcon("close"); //show close icon
                    setCookie("chatbox_open", "1");
                    if (windowSize.matches) {
                        changeChatIconPosition("close");
                    }
                } else {
                    //have to close the chat box
                    setChatIcon("open"); //show open icon
                    setCookie("chatbox_open", "");
                    setCookie("active_chat_id", "");
                    if (windowSize.matches) {
                        changeChatIconPosition("open");
                    }
                }

                if (window.activeChatChecker) {
                    window.clearInterval(window.activeChatChecker);
                }

                if (typeof window.placeCartBox === "function") {
                    window.placeCartBox();
                }

                if (typeof feather !== 'undefined') {
                    feather.replace();
                }

            });

            //open chat box
            if (isChatBoxOpen) {
                if (isStaffChat) {
                    loadChatTabs();
                } else if (activeChatId) {
                    getActiveChat(activeChatId);
                } else {
                    loadChatTabs();
                }
            }

            var windowSize = window.matchMedia("(max-width: 767px)");
            if (windowSize.matches) {
                if (isChatBoxOpen) {
                    $("#js-init-chat-icon").addClass("move-chat-icon");
                }
            }

            $('body #js-rise-chat-wrapper').on('click', '.js-message-row', function () {
                getActiveChat($(this).attr("data-id"));
            });

            $('body #js-rise-chat-wrapper').on('click', '.js-message-row-of-team-members-tab', function () {
                getChatlistOfUser($(this).attr("data-id"), "team_members");
            });

            $('body #js-rise-chat-wrapper').on('click', '.js-message-row-of-clients-tab', function () {
                getChatlistOfUser($(this).attr("data-id"), "clients");
            });

            function pollPrimeChatUnread() {
                if (!isStaffChat) {
                    return;
                }
                $.ajax({
                    url: "<?php echo get_uri('chat/count_unread'); ?>",
                    type: "POST",
                    dataType: "json",
                    success: function (result) {
                        if (!result || !result.success) {
                            return;
                        }
                        var total = parseInt(result.total_notifications, 10) || 0;
                        if (total > 0 && $("#js-chat-min-icon").attr("data-type") === "open") {
                            window.prepareUnreadMessageChatBox(total);
                        }
                    }
                });
            }

            if (isStaffChat) {
                pollPrimeChatUnread();
                setInterval(pollPrimeChatUnread, 30000);
            }

        });

        function getChatlistOfUser(user_id, tab_type) {

            setChatIcon("close"); //show close icon

            appLoader.show({container: "#js-rise-chat-wrapper", css: "bottom: 40%; right: 35%;"});
            $.ajax({
                url: "<?php echo get_uri("messages/get_chatlist_of_user"); ?>",
                type: "POST",
                data: {user_id: user_id, tab_type: tab_type},
                success: function (response) {
                    $("#js-rise-chat-wrapper").html(response);
                    appLoader.hide();
                }
            });
        }

        function loadChatTabs(trigger_from_user_chat) {

            setChatIcon("close"); //show close icon

            setCookie("active_chat_id", "");
            appLoader.show({container: "#js-rise-chat-wrapper", css: "bottom: 40%; right: 35%;"});
            $.ajax({
                url: window.primeChatListUrl || <?php echo json_encode($chat_list_url); ?>,
                data: {
                    type: "inbox"
                },
                success: function (response) {
                    $("#js-rise-chat-wrapper").html(response);

                    if (!<?php echo $is_staff_chat ? 'true' : 'false'; ?>) {
                        if (!trigger_from_user_chat) {
                            $("#chat-inbox-tab-button a").trigger("click");
                        } else if (trigger_from_user_chat === "team_members") {
                            $("#chat-users-tab-button").find("a").trigger("click");
                        } else if (trigger_from_user_chat === "clients") {
                            $("#chat-clients-tab-button").find("a").trigger("click");
                        }
                    }
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                    appLoader.hide();
                },
                error: function () {
                    appLoader.hide();
                }
            });

        }


        function getActiveChat(message_id) {
            setChatIcon("close"); //show close icon

            appLoader.show({container: "#js-rise-chat-wrapper", css: "bottom: 40%; right: 35%;"});
            $.ajax({
                url: "<?php echo get_uri('messages/get_active_chat'); ?>",
                type: "POST",
                data: {
                    message_id: message_id
                },
                success: function (response) {
                    $("#js-rise-chat-wrapper").html(response);
                    appLoader.hide();
                    setCookie("active_chat_id", message_id);
                    $("#js-chat-message-textarea").focus();
                }
            });
        }

        window.prepareUnreadMessageChatBox = function (totalMessages) {
            setChatIcon("unread", totalMessages); //show close icon
        };


        window.triggerActiveChat = function (message_id) {
            if (<?php echo $is_staff_chat ? 'true' : 'false'; ?>) {
                window.openPrimeConversation(message_id);
                return;
            }
            getActiveChat(message_id);
        }

    </script>


<?php } ?>
