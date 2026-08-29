<div class="modal-body clearfix">
    <div class="container-fluid">
        <?php if (empty($mails)) { ?>
            <div class="alert alert-info mb0"><?php echo app_lang("no_record_found"); ?></div>
        <?php } else { ?>
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
                <?php foreach ($mails as $mail) { ?>
                    <tr>
                        <td><?php echo $mail["user_from_link"]; ?> [<?php echo $mail["user_from_email"]; ?>]</td>
                        <td><?php echo $mail["user_to_link"]; ?> [<?php echo $mail["user_to_email"]; ?>]
                            <?php if (!empty($mail["is_primary_contact"])) { ?> <i data-feather="star" class="icon-16"></i> <?php } ?>
                        </td>
                        <td><?php echo $mail["sent_at"]; ?></td>
                        <td><?php echo $mail["read_at"]; ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">
        <span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?>
    </button>
</div>
