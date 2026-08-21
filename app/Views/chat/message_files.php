<?php
$files = $files ?? array();
$message_id = (int) ($message_id ?? 0);
$file_path = $file_path ?? get_setting("timeline_file_path");
$group_id = make_random_string();

$images = array();
$audios = array();
$videos = array();
$others = array();

if (!function_exists('prime_chat_parse_recording_duration')) {
    function prime_chat_parse_recording_duration($file_name) {
        if (preg_match('/(\d+)h(\d+)m(\d+)s/i', (string) $file_name, $m)) {
            return ((int) $m[1]) * 3600 + ((int) $m[2]) * 60 + ((int) $m[3]);
        }
        return 0;
    }
}

if (!function_exists('prime_chat_is_voice_file')) {
    function prime_chat_is_voice_file($file_name) {
        $name = (string) $file_name;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $audio_exts = array('mp3', 'm4a', 'wav', 'ogg', 'opus', 'aac', 'weba', 'oga');
        if (in_array($ext, $audio_exts, true)) {
            return true;
        }
        if ($ext === 'webm' && stripos($name, 'recording') !== false) {
            return true;
        }
        return false;
    }
}

if (!function_exists('prime_chat_is_inline_video_file')) {
    function prime_chat_is_inline_video_file($file_name) {
        $ext = strtolower(pathinfo((string) $file_name, PATHINFO_EXTENSION));
        return in_array($ext, array('mp4', 'webm', 'ogv', 'mov', 'm4v'), true)
            && !prime_chat_is_voice_file($file_name);
    }
}

if (!function_exists('prime_chat_file_download_uri')) {
    function prime_chat_file_download_uri($message_id, $index) {
        $message_id = (int) $message_id;
        $index = (int) $index;
        if ($message_id < 1 || $index < 0) {
            return '';
        }
        return get_uri("chat/download_message_file/" . $message_id . "/" . $index);
    }
}

if (!function_exists('prime_chat_file_kind_icon')) {
    function prime_chat_file_kind_icon($kind) {
        $icons = array(
            'image' => 'image',
            'video' => 'film',
            'voice' => 'mic',
            'file' => 'paperclip',
        );
        $name = isset($icons[$kind]) ? $icons[$kind] : 'paperclip';
        return '<i data-feather="' . $name . '" class="icon-14"></i>';
    }
}

/**
 * Unified download row for every attachment type.
 */
if (!function_exists('prime_chat_render_download_row')) {
    function prime_chat_render_download_row($file, $message_id, $kind = 'file', $preview = null) {
        $title = remove_file_prefix($file['file_name'] ?? '');
        $dl = prime_chat_file_download_uri($message_id, isset($file['_index']) ? $file['_index'] : -1);
        $size = !empty($file['file_size']) ? format_file_size($file['file_size']) : '';

        $main_open = '<div class="pm-file-row-main">';
        $main_close = '</div>';
        if (is_array($preview) && !empty($preview['url'])) {
            $main_open = '<a href="#" class="pm-file-row-main"'
                . ' title="' . esc($title) . '"'
                . ' data-sidebar="0"'
                . ' data-toggle="app-modal"'
                . ' data-type="' . esc($preview['type'] ?? 'not_viewable') . '"'
                . ' data-group="' . esc($preview['group'] ?? '') . '"'
                . ' data-content_url="' . esc($preview['url']) . '"'
                . ' data-title="' . esc($title) . '">';
            $main_close = '</a>';
        }

        $html = '<div class="pm-file-row">';
        $html .= $main_open;
        $html .= prime_chat_file_kind_icon($kind);
        $html .= '<span class="pm-file-name" title="' . esc($title) . '">' . esc($title) . '</span>';
        if ($size !== '') {
            $html .= '<em class="pm-file-size">' . esc($size) . '</em>';
        }
        $html .= $main_close;
        if ($dl) {
            $html .= '<a class="pm-file-download" href="' . esc($dl) . '" title="Скачать" download>'
                . '<i data-feather="download" class="icon-14"></i>'
                . '<span>Скачать</span>'
                . '</a>';
        }
        $html .= '</div>';
        return $html;
    }
}

foreach ($files as $index => $file) {
    if (!is_array($file) || empty($file['file_name'])) {
        continue;
    }
    $file['_index'] = (int) $index;
    $name = (string) $file['file_name'];
    if (is_viewable_image_file($name)) {
        $images[] = $file;
    } else if (prime_chat_is_voice_file($name)) {
        $audios[] = $file;
    } else if (prime_chat_is_inline_video_file($name)) {
        $videos[] = $file;
    } else {
        $others[] = $file;
    }
}

$file_count = count($files);
$show_download_all = $message_id && $file_count > 1;
?>
<div class="pm-attachments">
    <?php foreach ($images as $file) {
        $url = get_source_url_of_file($file, $file_path);
        $title = remove_file_prefix($file['file_name']);
        ?>
        <div class="pm-file">
            <a href="#"
               class="pm-attachment-image"
               title="<?php echo esc($title); ?>"
               data-sidebar="0"
               data-toggle="app-modal"
               data-type="image"
               data-group="<?php echo esc($group_id); ?>"
               data-content_url="<?php echo esc($url); ?>"
               data-title="<?php echo esc($title); ?>">
                <img src="<?php echo esc($url); ?>" alt="<?php echo esc($title); ?>" loading="lazy">
            </a>
            <?php echo prime_chat_render_download_row($file, $message_id, 'image'); ?>
        </div>
    <?php } ?>

    <?php foreach ($videos as $file) {
        $url = get_source_url_of_file($file, $file_path);
        $title = remove_file_prefix($file['file_name']);
        $ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
        $mime = array(
            'mp4' => 'video/mp4',
            'm4v' => 'video/mp4',
            'webm' => 'video/webm',
            'ogv' => 'video/ogg',
            'mov' => 'video/quicktime',
        );
        $type = isset($mime[$ext]) ? $mime[$ext] : 'video/mp4';
        ?>
        <div class="pm-file pm-file-video">
            <div class="pm-video">
                <video class="pm-video-player" controls playsinline preload="metadata"
                       title="<?php echo esc($title); ?>">
                    <source src="<?php echo esc($url); ?>" type="<?php echo esc($type); ?>">
                </video>
            </div>
            <?php echo prime_chat_render_download_row($file, $message_id, 'video'); ?>
        </div>
    <?php } ?>

    <?php foreach ($audios as $file) {
        $url = get_source_url_of_file($file, $file_path);
        $guess = prime_chat_parse_recording_duration($file['file_name']);
        $guess_label = $guess > 0
            ? sprintf('%d:%02d', intdiv($guess, 60), $guess % 60)
            : '0:00';
        ?>
        <div class="pm-file pm-file-voice">
            <div class="pm-voice" data-duration="<?php echo (int) $guess; ?>">
                <audio class="pm-voice-audio" preload="metadata" src="<?php echo esc($url); ?>"></audio>
                <button type="button" class="pm-voice-play js-pm-voice-play" title="Слушать" aria-label="Слушать">
                    <span class="pm-voice-ico pm-voice-ico-play" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    <span class="pm-voice-ico pm-voice-ico-pause hide" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6 5h4v14H6zm8 0h4v14h-4z"/></svg>
                    </span>
                </button>
                <div class="pm-voice-main">
                    <div class="pm-voice-wave" aria-hidden="true">
                        <i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
                    </div>
                    <input type="range" class="pm-voice-seek js-pm-voice-seek" min="0" max="1000" value="0" step="1" aria-label="Позиция">
                    <div class="pm-voice-row">
                        <span class="pm-voice-time js-pm-voice-time">0:00 / <?php echo esc($guess_label); ?></span>
                        <button type="button" class="pm-voice-speed js-pm-voice-speed" title="Скорость" data-rate="1">1×</button>
                    </div>
                </div>
            </div>
            <?php echo prime_chat_render_download_row($file, $message_id, 'voice'); ?>
        </div>
    <?php } ?>

    <?php foreach ($others as $file) {
        $url = get_source_url_of_file($file, $file_path);
        $preview_type = is_viewable_video_file($file['file_name']) ? 'iframe' : 'not_viewable';
        ?>
        <div class="pm-file">
            <?php echo prime_chat_render_download_row($file, $message_id, 'file', array(
                'url' => $url,
                'type' => $preview_type,
                'group' => $group_id,
            )); ?>
        </div>
    <?php } ?>

    <?php if ($show_download_all) { ?>
        <div class="pm-file-row pm-file-row-all">
            <div class="pm-file-row-main">
                <i data-feather="download" class="icon-14"></i>
                <span class="pm-file-name">Все файлы сообщения</span>
                <em class="pm-file-size"><?php echo (int) $file_count; ?></em>
            </div>
            <a class="pm-file-download" href="<?php echo esc(get_uri("chat/download_message_files/" . $message_id)); ?>" title="Скачать все архивом">
                <i data-feather="download" class="icon-14"></i>
                <span>Скачать все</span>
            </a>
        </div>
    <?php } ?>
</div>
