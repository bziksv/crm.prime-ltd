<div class="modal-body clearfix">
    <div class="container-fluid">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>От кого</th>
                <th>Кому</th>
                <th>Отправлено</th>
                <th>Прочитано</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($mails as $mail): ?>
                <tr>
                    <td><?php echo $mail["user_from_link"]; ?> [<?php echo $mail["user_from_email"]; ?>]</td>
                    <td><?php echo $mail["user_to_link"]; ?> [<?php echo $mail["user_to_email"]; ?>]
                        <?php if ($mail["is_primary_contact"]): ?> <i data-feather="star" class="icon-16"></i> <?php endif; ?>
                    </td>
                    <td><?php echo $mail["sent_at"]; ?></td>
                    <td><?php echo $mail["read_at"]; ?></td>
                </tr>
            <? endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">
        <span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?>
    </button>
</div>

<script type="text/javascript">
    $(document).ready(function () {

    });
</script>
