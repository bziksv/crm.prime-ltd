<?php

if ($files && count($files)) {

    $group_id = make_random_string();

    $box_class = "";
    $caption_class = "more";
    $caption_lang = " " . app_lang('more');
    if (isset($is_message_row)) {
        $box_class = "message-images mb5 mt5";
        $caption_class .= " message-more";
        $caption_lang = "";
    }

    $view_type = "";
    if (isset($view)) {
        $view_type = $view;
    }
    ?>

    <div class='timeline-images app-modal-view mb-4 <?php echo $box_class;?>'>

    <ul class="files-and-folders-list" data-has_write_permission="" data-has_upload_permission="">

    <?php

    $is_localhost = is_localhost();

    $timeline_file_path = isset($file_path) ? $file_path : get_setting("timeline_file_path");

    // Initialize arrays to collect webm files and other files
    $recording_files = "";
    $other_files = "";
    $preview_image = "";

    foreach ($files as $file) {
        $url = get_source_url_of_file($file, $timeline_file_path);

        if (is_viewable_image_file($file['file_name'])) {
            ?>
            <a href="#" title="<?=$file['file_name']?>" class="text-default file-name item-name d-block mb-2"
               data-sidebar='0'
               data-toggle="app-modal"
               data-type="image"
               data-group="<?php echo make_random_string();?>"
               data-content_url="<?php echo $url?>"
               data-title="<?php echo $timeline_file_path?>"
            >
                <img src="<?php echo $url?>" alt="<?=$file['file_name']?>" class="img-fluid">
            </a>
            <?php
            break;
        }
    }

    // Separate webm files containing "recording" from other files
    foreach ($files as $file) {

        $file_name = $file['file_name'];
        $file_id = get_array_value($file, "file_id");
        $service_type = get_array_value($file, "service_type");

        $is_google_drive_file = ($file_id && $service_type == "google") ? true : false;

        $actual_file_name = remove_file_prefix($file_name);
        $thumbnail = get_source_url_of_file($file, $timeline_file_path, "thumbnail");
        $url = get_source_url_of_file($file, $timeline_file_path);

        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $image = "";

        if ($file_id && $is_google_drive_file) {
            $url = get_uri("uploader/stream_google_drive_file/".$file_id."/".$actual_file_name);
        }

        if (isset($seperate_audio) && $seperate_audio && $extension === "webm" && strpos($file_name, 'recording')) {

            $actual_file_name_without_extension = remove_file_extension($actual_file_name);

            $recording_files .= "<audio src='$url' controls='' class='audio file-highlight-section' id='$actual_file_name_without_extension'></audio>";

        }

        if (is_viewable_image_file($file_name)) {
            $type = "image";
        } else if ($extension === "webm") {
            $type = "audio";
        } elseif ($extension === "txt") {
            $type = "txt";
        } elseif (
                is_iframe_preview_available($file_name) ||
                (is_viewable_video_file($file_name) && !$file_id && $service_type != "google") ||
                (is_viewable_video_file($file_name) && $file_id && $service_type == "google" && !get_setting("disable_google_preview"))
        ) {
            $type = "iframe";
        } else {
            $type = "not_viewable";
        }
        ?>

        <li class="folder-item">
            <div class="folder-item-content show-context-menu file-thumb-area focus">
                <div class="d-flex">
                    <?php echo view("includes/icon_wrapper");?>

                    <div class="w-100">
                        <div class="text-break">
                            <!-- data-type = image, audio, txt, iframe, not_viewable -->
                            <a href="#" title="<?=$file_name?>" class="text-default file-name item-name"
                               data-sidebar='0'
                               data-toggle="app-modal"
                               data-type="<?php echo $type;?>"
                               data-group="<?php echo $group_id;?>"
                               data-content_url="<?php echo $url?>"
                               data-title="<?php echo $actual_file_name?>"
                            >
                                <?=$actual_file_name?>
                            </a>
                        </div>
                        <?php if (isset($file["file_size"])): ?>
                        <small class="text-off file-size"><?php echo format_file_size($file["file_size"]); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </li>

    <?php
    }
    ?>
    </ul>

    <?php
    if ($recording_files) {
        echo $recording_files;
    }
    ?>

    </div>
    <?php
}
?>

<script>
    $(document).ready(function() {
        $(".file-highlight-link").click(function(e) {
            var fileId = $(this).attr('data-file-id');

            e.preventDefault();

            highlightSpecificFile(fileId);
        });

        function highlightSpecificFile(fileId) {
            $(".file-highlight-section").removeClass("file-highlight");
            $("#recording-" + fileId).addClass("file-highlight");
            window.location.hash = ""; //remove first to scroll with main link
            window.location.hash = "recording-" + fileId;
        }

    });
</script>
