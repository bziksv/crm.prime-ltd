<script type="text/javascript">
    "use strict";

    function prepareShareWithClientContactsDropdown(clientId) {
        if (clientId) {
            $("#share-with-client-contact").removeClass("hide");
            $.ajax({
                url: "<?php echo get_uri("password_manager/get_all_contacts_of_client") ?>" + "/" + clientId,
                dataType: "json",
                success: function (result) {

                    if (result.length) {
                        get_specific_dropdown($("#share_with_specific_client_contact"), result);
                    } else {
                        //if no client contact exists, then don't show the share with client contacts option
                        $("#share-with-client-contact").addClass("hide");
                        prepareShareWithClientContactsDropdown();
                    }

                }
            });
        }
    }

    function get_specific_dropdown(container, data) {
        setTimeout(function () {
            container.select2({
                multiple: true,
                formatResult: teamAndMemberSelect2Format,
                formatSelection: teamAndMemberSelect2Format,
                data: data
            }).on('select2-open change', function (e) {
                feather.replace();
            });

            feather.replace();
        }, 100);
    }

    $(".toggle_specific").on("click", function () {
        toggle_specific_dropdown();
    });

    $(".toggle_specific_client").on("click", function () {
        toggle_specific_client_dropdown();
    });

    toggle_specific_dropdown();
    toggle_specific_client_dropdown();

    function toggle_specific_dropdown() {
        $(".specific_dropdown").hide().find("input").removeClass("validate-hidden");

        var $element = $(".toggle_specific:checked");
        if ($element.val() === "specific") {
            var $dropdown = $element.closest("div").find("div.specific_dropdown");
            $dropdown.show().find("input").addClass("validate-hidden");
        }
    }

    function toggle_specific_client_dropdown() {
        $(".specific_dropdown_client").hide().find("input").removeClass("validate-hidden");

        var $element = $(".toggle_specific_client:checked");
        if ($element.val() === "specific") {
            var $dropdown = $element.closest("div").find("div.specific_dropdown_client");
            $dropdown.show().find("input").addClass("validate-hidden");
        }
    }

    $(document).ready(function () {
        $("#share_with_all_team_members").click(function () {
            if ($(this).is(":checked")) {
                $("#share_with_specific_team_members_button").attr('disabled', 'true');
            } else {
                $("#share_with_specific_team_members_button").removeAttr('disabled');
            }
        });

        $("#share_with_all_client_contacts").click(function () {
            if ($(this).is(":checked")) {
                $("#share_with_specific_client_contacts_button").attr('disabled', 'true');
            } else {
                $("#share_with_specific_client_contacts_button").removeAttr('disabled');
            }
        });


        if ("<?php echo $model_info->created_by_client; ?>" === "1") {
            $("#clients_dropdown").select2("readonly", true);
        }
    });
</script>