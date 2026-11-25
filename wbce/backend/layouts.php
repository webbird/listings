<?php
/** Listings backend: list and detail layouts. @var array<string, mixed> $v */
defined('WB_PATH') or die('This file cannot be accessed directly!');

$lstNames = array_map(static fn ($f) => strtoupper($f['name']), array_filter($v['fields'], static fn ($f) => $f['type'] !== 'heading'));
?>
<p class="lst-hint"><?= h(L_('MOD_LISTINGS:HINT_LAYOUTS')) ?></p>
<?php if ($lstNames !== []): ?>
    <p class="lst-help"><?= h(L_('MOD_LISTINGS:LAYOUT_FIELD_KEYS')) ?>
        <?php foreach ($lstNames as $lstName): ?><code>[ITEM.<?= h($lstName) ?>]</code> <?php endforeach; ?>
    </p>
<?php endif; ?>
<form method="post" action="<?= h($v['action']) ?>" class="lst-form">
    <?= $v['hidden'] ?>
    <input type="hidden" name="lst_action" value="layouts_save">
    <?php foreach ($v['layouts'] as $lstName => $lstLayout): ?>
    <fieldset>
        <legend>
            <?= h(L_('MOD_LISTINGS:LAYOUT_' . strtoupper($lstName))) ?>
            <span class="lst-badge"><?= h(L_($v['custom'][$lstName] ? 'MOD_LISTINGS:LAYOUT_CUSTOM' : 'MOD_LISTINGS:LAYOUT_DEFAULT')) ?></span>
        </legend>
        <textarea name="layout_<?= h($lstName) ?>" rows="18" class="lst-code" spellcheck="false"><?= h($lstLayout) ?></textarea>
        <?php if ($v['custom'][$lstName]): ?>
            <label><input type="checkbox" name="reset_<?= h($lstName) ?>" value="1"> <?= h(L_('MOD_LISTINGS:LAYOUT_RESET')) ?></label>
        <?php endif; ?>
    </fieldset>
    <?php endforeach; ?>
    <div class="lst-buttons">
        <button type="submit"><?= h(L_('TEXT:SAVE')) ?></button>
    </div>
</form>
</div>
