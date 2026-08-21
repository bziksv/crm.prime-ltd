<?php
/**
 * Multi-select job-title picker.
 * Expects: $job_options = [ ['key' => 'seo...', 'label' => '...', 'count' => N], ... ]
 */
$job_options = $job_options ?? array();
if (!$job_options) {
    return;
}
$total = 0;
foreach ($job_options as $opt) {
    if (($opt['key'] ?? '') !== '') {
        $total += (int) ($opt['count'] ?? 0);
    }
}
?>
<div class="pm-job-picker" data-multi="1">
    <input type="hidden" class="js-pm-job-filter" value="">
    <button type="button" class="pm-job-picker-btn js-pm-job-picker-toggle" aria-expanded="false">
        <span class="pm-job-picker-btn-text js-pm-job-picker-label">Все должности</span>
        <svg class="pm-job-picker-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </button>
    <div class="pm-job-picker-menu hide" role="listbox" aria-multiselectable="true">
        <div class="pm-job-picker-menu-head">
            <span>Должности</span>
            <span class="pm-job-picker-menu-hint">можно несколько</span>
        </div>
        <div class="pm-job-picker-menu-list">
            <label class="pm-job-picker-option js-pm-job-option is-active" data-job="" data-label="Все должности">
                <input type="checkbox" class="js-pm-job-check" checked>
                <span class="pm-job-picker-option-text">Все должности</span>
                <em><?php echo (int) $total; ?></em>
            </label>
            <?php foreach ($job_options as $opt) {
                $key = (string) ($opt['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $label = (string) ($opt['label'] ?? $key);
                $count = (int) ($opt['count'] ?? 0);
                ?>
                <label class="pm-job-picker-option js-pm-job-option"
                    data-job="<?php echo esc($key); ?>"
                    data-label="<?php echo esc($label); ?>"
                    title="<?php echo esc($label); ?>">
                    <input type="checkbox" class="js-pm-job-check">
                    <span class="pm-job-picker-option-text"><?php echo esc($label); ?></span>
                    <em><?php echo $count; ?></em>
                </label>
            <?php } ?>
        </div>
        <div class="pm-job-picker-menu-foot">
            <button type="button" class="btn btn-default btn-sm js-pm-job-clear">Сбросить</button>
            <button type="button" class="btn btn-primary btn-sm js-pm-job-apply">Готово</button>
        </div>
    </div>
</div>
