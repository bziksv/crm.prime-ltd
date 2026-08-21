<div class="me-auto">
    <?php
    $upload_button_id = make_random_string();
    if (!isset($upload_url)) {
        $upload_url = get_uri("uploader/upload_file");
    }
    if (!isset($validation_url)) {
        $validation_url = get_uri("uploader/validate_file");
    }
    $inline_svg_icons = !empty($inline_svg_icons);
    $upload_icon_name = $upload_button_icon ?? 'camera';
    $paperclip_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>';
    $mic_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>';
    $upload_icon_html = $inline_svg_icons
        ? ($upload_icon_name === 'paperclip' ? $paperclip_svg : '<i data-feather="' . esc($upload_icon_name) . '" class="icon-16"></i>')
        : '<i data-feather="' . esc($upload_icon_name) . '" class="icon-16"></i>';
    ?>

    <button id="<?php echo $upload_button_id; ?>" class="btn btn-default upload-file-button float-start round" type="button" style="color:#525c69" title="<?php echo app_lang('upload_file'); ?>" aria-label="<?php echo app_lang('upload_file'); ?>"><?php echo $upload_icon_html; ?><?php
        if (isset($upload_button_text)) {
            echo $upload_button_text;
        } else {
            echo app_lang("upload_file");
        }
        ?>
    </button>
    <?php

    $https = !empty($_SERVER['HTTPS']);
    if(!$https){
        $https = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    // Chrome/Firefox allow mic on localhost without HTTPS
    if (!$https && function_exists('is_localhost') && is_localhost()) {
        $https = true;
    }
    // Built-in PHP server / local CRM hostname
    if (!$https) {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || strpos($host, '.local') !== false) {
            $https = true;
        }
    }

    $show_recording = get_setting("enable_audio_recording");
    if (!empty($force_show_recording)) {
        $show_recording = true;
        $https = true;
    }

    if ($show_recording && $https) {
        ?>
        <button type="button" id="record-start-button" class="btn btn-default record-start-btn ml10" style="color:#525c69" title="Голосовое сообщение" aria-label="Голосовое сообщение"><?php echo $inline_svg_icons ? $mic_svg : '<i data-feather="mic" class="icon-16"></i>'; ?></button>
        <button type="button" id="record-stop-button" class="btn btn-default record-end-btn ml10 hide" title="Стоп" aria-label="Стоп"><div class="stop-recording"></div></button>
        <span class="recording-text ml5 hide"><?php echo app_lang('recording'); ?></span>

        <?php
        load_js(array(
            "assets/js/recordrtc/RecordRTC.min.js",
        ));
    } else if ($show_recording && !$https) {
        ?>
        <span class="help" data-bs-toggle="tooltip" title="<?php echo app_lang('https_required'); ?>"><span class="btn btn-default record-start-btn disabled opacity-25 ml10"><?php echo $inline_svg_icons ? $mic_svg : '<i data-feather="mic" class="icon-16"></i>'; ?></span></span>

    <?php }
    ?>
</div>



<script type="text/javascript">
    $(document).ready(function () {

        var $dropzoneElement = $("#<?php echo $upload_button_id; ?>").closest(".post-dropzone");
        var drozoneId = $dropzoneElement.attr("id");
        if (!window.formDropzone) {
            window.formDropzone = [];
        }

        window.formDropzone[drozoneId] = attachDropzoneWithForm("#" + drozoneId, "<?php echo $upload_url; ?>", "<?php echo $validation_url; ?>");

        $('[data-bs-toggle="tooltip"]').tooltip();


        var enableRecording = "<?php echo $https && $show_recording ? '1' : ''; ?>";


        if (enableRecording) {

            //for recording
            var startRecordButton = document.getElementById('record-start-button');
            var stopRecordButton = document.getElementById('record-stop-button');

            var recordOptions = {
                type: 'audio',
                mimeType: 'audio/webm'
            };

            //variables to store the recording blob data
            var recorder, audioBlob,
                    duration = {};

            // Event listener for the start recording button
            startRecordButton.addEventListener('click', function () {
                if (!recorder) {
                    // Start recording
                    duration.start = new Date();
                    navigator.mediaDevices.getUserMedia({audio: true}).then(function (stream) {
                        recorder = RecordRTC(stream, recordOptions);
                        recorder.startRecording();
                        $("#record-button").addClass("btn-success");
                        $(".recording-text").removeClass("hide");
                        $(".record-end-btn").removeClass("hide");
                        $(".record-start-btn").addClass("hide");
                    });
                }
            });

            // Event listener for the stop recording button
            stopRecordButton.addEventListener('click', function () {
                if (recorder) {

                    duration.end = new Date();
                    recorder.stopRecording(function () {
                        // Get the recorded audio blob
                        audioBlob = recorder.getBlob();

                        uploadAudioBlob(audioBlob, duration);

                        // Reset the recorder and button style
                        recorder = null;
                        $("#record-button").removeClass("btn-success");
                        $("#record-button").addClass("btn-default");
                        $(".recording-text").addClass("hide");
                        $(".record-start-btn").removeClass("hide");
                        $(".record-end-btn").addClass("hide");
                        $(".post-file-upload-row").addClass("audio-preview");
                    });
                }
            });

            // Function to upload the audio blob to the specified URL
            function uploadAudioBlob(blob, duration) {

                const timeDifference = calculateTimeDifference(duration);

                var blobName = 'recording-' + timeDifference + new Date().getMilliseconds() + "ms";
                blob.name = blobName + '.webm';

                //Upload the audio using dropzone
                window.formDropzone[drozoneId].addFile(blob);

                // Create an audio element to preview the recording
                var audioElement = document.createElement('audio');
                audioElement.src = URL.createObjectURL(blob);
                audioElement.controls = true;

                // Create a div element to wrap the audio element
                var audioContainer = $('<div class="audio-container">');
                audioContainer.append(audioElement);
                $(".preview:last-child").append(audioContainer);

                var showLinkCopyButton = "<?php echo isset($show_link_copy_button) && $show_link_copy_button ? '1' : ''; ?>";
                if (showLinkCopyButton) {
                    $(".preview:last-child").append('<span class="copy-link copy-file-link-btn" data-file-name="' + blobName + '" data-context="notes"><i data-feather="link" class="icon-14"></i> Copy</span>');
                }

                copyLink();
            }

            copyLink();

            function copyLink() {
                $(".copy-file-link-btn").click(function () {
                    var fileName = $(this).attr('data-file-name');
                    var reference = "<?php echo app_lang('reference'); ?>";
                    var tempInput = document.createElement("input");
                    tempInput.style = "position: absolute; left: -1000px; top: -1000px";
                    tempInput.value = "#[" + fileName + "] (" + reference + ")";
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand("copy");
                    document.body.removeChild(tempInput);

                    var tooltip = $('<div class="tooltip bs-tooltip-auto fade show" style="position: absolute; inset: auto auto 0px 0px; margin: 0px; transform: translate(-20px, -24px);" data-popper-placement="top"><div class="tooltip-arrow" style="position: absolute; left: 0px; transform: translate(27px, 0px);"></div><div class="tooltip-inner"><?php echo app_lang("link_copied"); ?></div></div>');

                    $(this).append(tooltip);

                    setTimeout(function () {
                        tooltip.remove();
                    }, 1500);

                });
            }

            function calculateTimeDifference(duration) {
                var date1 = new Date(duration.start);
                var date2 = new Date(duration.end);

                var timeDifference = Math.abs(date2 - date1);

                var hours = Math.floor(timeDifference / 3600000); // 1 hour = 3600000 milliseconds
                var minutes = Math.floor((timeDifference % 3600000) / 60000); // 1 minute = 60000 milliseconds
                var seconds = Math.floor((timeDifference % 60000) / 1000); // 1 second = 1000 milliseconds

                // Format the output string
                var formattedTime = `${hours}h${minutes}m${seconds}s`;

                return formattedTime;
            }

        }

    });
</script>