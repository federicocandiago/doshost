<?php /** @var App\Models\Instance $instance @var array $config */ ?>
<div class="player-shell">
    <div class="player-bar" role="toolbar" aria-label="<?= e(__('Instance controls')) ?>">
        <span class="status" id="player-status" aria-live="polite"><?= e(__('Booting…')) ?></span>
        <button class="pbtn icon only-mobile" id="btn-keyboard" type="button" aria-pressed="false" aria-label="<?= e(__('Keyboard')) ?>">⌨</button>
        <div class="player-menu">
            <button class="pbtn icon" id="btn-menu" type="button" aria-expanded="false" aria-controls="player-menu" aria-label="<?= e(__('Menu')) ?>">☰</button>
            <div class="menu-panel" id="player-menu" hidden>
                <div class="menu-head">
                    <span class="mono name"><?= e($instance->name) ?></span>
                    <span class="mono usage <?= $instance->diskState() ? 'warn' : '' ?>" id="player-usage" title="<?= e(__('Disk usage')) ?>"><?= $instance->usagePercent() ?>%</span>
                </div>
                <button class="menu-item" id="btn-save" type="button"><?= e(__('Save')) ?></button>
                <button class="menu-item" id="btn-fullscreen" type="button" aria-pressed="false"><?= e(__('Full screen')) ?></button>
                <button class="menu-item" id="btn-cursor" type="button" aria-pressed="<?= $instance->show_cursor ? 'true' : 'false' ?>" title="<?= e(__('Show or hide the system mouse cursor over DOS')) ?>"><?= e(__('Mouse cursor')) ?></button>
                <button class="menu-item only-mobile" id="btn-touchpad" type="button" aria-pressed="false" title="<?= e(__('Drag your finger to move the pointer, tap to click')) ?>"><?= e(__('Touchpad mouse')) ?></button>
                <button class="menu-item" id="btn-tools" type="button" aria-pressed="false" title="<?= e(__('Show or hide the js-dos side bar')) ?>"><?= e(__('Tools')) ?></button>
                <button class="menu-item primary" id="btn-exit" type="button"><?= e(__('Save & exit')) ?></button>
            </div>
        </div>
    </div>

    <div id="dos" class="dos-root"></div>
    <button class="start-overlay" id="start-overlay" type="button" hidden><span><?= e(__('Tap to start')) ?></span></button>

    <div class="mobile-keys" id="mobile-keys" aria-label="<?= e(__('Extra keys')) ?>">
        <button type="button" data-key="esc">Esc</button>
        <button type="button" data-key="tab">Tab</button>
        <button type="button" data-mod="ctrl" aria-pressed="false">Ctrl</button>
        <button type="button" data-mod="alt" aria-pressed="false">Alt</button>
        <button type="button" data-key="left" aria-label="Left">←</button>
        <button type="button" data-key="up" aria-label="Up">↑</button>
        <button type="button" data-key="down" aria-label="Down">↓</button>
        <button type="button" data-key="right" aria-label="Right">→</button>
        <button type="button" data-key="enter" aria-label="Enter">↵</button>
        <button type="button" data-key="backspace" aria-label="Backspace">⌫</button>
        <?php for ($i = 1; $i <= 10; $i++): ?>
            <button type="button" data-key="f<?= $i ?>">F<?= $i ?></button>
        <?php endfor ?>
    </div>

    <input type="text" id="mobile-input" class="mobile-input" aria-hidden="true" tabindex="-1"
           autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" enterkeyhint="send">
</div>

<script type="application/json" id="player-config"><?= json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= asset('vendor/js-dos/js-dos.js') ?>"></script>
<script src="<?= asset('assets/js/keyboard.js') ?>"></script>
<script src="<?= asset('assets/js/player.js') ?>"></script>
