
<script>
    // Always use the server default (timesheets when available).
    // The selected_report_* cookie used to reopen the last finance tab and sent some users to Expenses.
    window.location.href = "<?php echo_uri($redirect_to); ?>";
</script>
