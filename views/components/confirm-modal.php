<?php /* One shared confirmation dialog, driven by [data-confirm] buttons in app.js */ ?>
<div class="modal fade" id="confirm-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5"><?= e(__('Are you sure?')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(__('Close')) ?>"></button>
            </div>
            <div class="modal-body" data-confirm-text></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal"><?= e(__('Cancel')) ?></button>
                <button type="button" class="btn btn-danger" data-confirm-ok><?= e(__('Delete')) ?></button>
            </div>
        </div>
    </div>
</div>
