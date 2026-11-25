<?php
/** Listings backend: the field set. @var array<string, mixed> $v */
defined('WB_PATH') or die('This file cannot be accessed directly!');

use Listings\Module;

$lstLast = count($v['fields']) - 1;
?>
<div class="lst-toolbar">
    <a class="lst-button" href="<?= h(($v['url'])(['lst_view' => 'field'])) ?>"><?= h(L_('MOD_LISTINGS:NEW_FIELD')) ?></a>
</div>
<p class="lst-hint"><?= h(L_('MOD_LISTINGS:HINT_FIELDS')) ?></p>

<?php if ($v['fields'] === []): ?>
    <p><?= h(L_('MOD_LISTINGS:NO_FIELDS')) ?></p>
<?php else: ?>
<table class="lst-table">
    <thead>
        <tr>
            <th></th>
            <th><?= h(L_('MOD_LISTINGS:FIELD_LABEL')) ?></th>
            <th><?= h(L_('MOD_LISTINGS:FIELD_NAME')) ?></th>
            <th><?= h(L_('MOD_LISTINGS:FIELD_TYPE')) ?></th>
            <th><?= h(L_('MOD_LISTINGS:FIELD_REQUIRED')) ?></th>
            <th><?= h(L_('MOD_LISTINGS:FIELD_VISIBILITY')) ?></th>
            <th><?= h(L_('MOD_LISTINGS:FIELD_IN_LIST')) ?></th>
            <th><?= h(L_('MOD_LISTINGS:FIELD_EDITABLE_SHORT')) ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($v['fields'] as $lstI => $lstField):
        $lstKey = Module::idKey($v['admin'], 'field', $lstField['field_id']); ?>
        <tr class="<?= $lstField['type'] === 'heading' ? 'lst-heading-row' : '' ?>">
            <td class="lst-move">
                <?php foreach ([-1 => ['up', '▲', $lstI > 0], 1 => ['down', '▼', $lstI < $lstLast]] as $lstDir => [$lstName, $lstArrow, $lstPossible]): ?>
                    <?php if ($lstPossible): ?>
                    <form method="post" action="<?= h($v['action']) ?>">
                        <?= $v['hidden'] ?>
                        <input type="hidden" name="lst_action" value="field_move">
                        <input type="hidden" name="lst_dir" value="<?= $lstDir ?>">
                        <input type="hidden" name="lst_field" value="<?= h($lstKey) ?>">
                        <button type="submit" class="lst-link-button" title="<?= h(L_('MOD_LISTINGS:MOVE_' . strtoupper($lstName))) ?>"><?= $lstArrow ?></button>
                    </form>
                    <?php endif; ?>
                <?php endforeach; ?>
            </td>
            <td><a href="<?= h(($v['url'])(['lst_view' => 'field', 'lst_field' => $lstField['field_id']])) ?>"><?= h($lstField['label']) ?></a></td>
            <td><code><?= h($lstField['name']) ?></code></td>
            <td><?= h(L_('MOD_LISTINGS:TYPE_' . strtoupper($lstField['type']))) ?></td>
            <td><?= $lstField['required'] ? '✓' : '' ?></td>
            <td><?= h(L_('MOD_LISTINGS:VISIBILITY_' . strtoupper($lstField['visibility']))) ?></td>
            <td><?= $lstField['in_list'] ? '✓' : '' ?></td>
            <td><?= $lstField['editable'] ? '✓' : '' ?></td>
            <td class="lst-actions">
                <a href="<?= h(($v['url'])(['lst_view' => 'field', 'lst_field' => $lstField['field_id']])) ?>"><?= h(L_('TEXT:MODIFY')) ?></a>
                <form method="post" action="<?= h($v['action']) ?>" data-lst-confirm="<?= h(L_('MOD_LISTINGS:CONFIRM_DELETE_FIELD', $lstField['label'])) ?>">
                    <?= $v['hidden'] ?>
                    <input type="hidden" name="lst_action" value="field_delete">
                    <input type="hidden" name="lst_field" value="<?= h($lstKey) ?>">
                    <button type="submit" class="lst-link-button"><?= h(L_('TEXT:DELETE')) ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>
