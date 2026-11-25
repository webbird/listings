<?php
/** Listings backend: add or edit a field. @var array<string, mixed> $v */
defined('WB_PATH') or die('This file cannot be accessed directly!');

use Listings\Fields;
use Listings\FieldTypes;
use Listings\Module;

$lstForm = $v['form'];
$lstField = $v['field'];
$lstCheck = static fn (string $key): string => ($lstForm[$key] ?? '0') === '1' ? ' checked' : '';
?>
<h3><?= h($lstField === null ? L_('MOD_LISTINGS:NEW_FIELD') : L_('MOD_LISTINGS:EDIT_FIELD', $lstField['label'])) ?></h3>
<form method="post" action="<?= h($v['action']) ?>" class="lst-form lst-field-form">
    <?= $v['hidden'] ?>
    <input type="hidden" name="lst_action" value="field_save">
    <?php if ($lstField !== null): ?>
        <input type="hidden" name="lst_field" value="<?= h(Module::idKey($v['admin'], 'field', $lstField['field_id'])) ?>">
    <?php endif; ?>

    <div class="lst-row">
        <label for="lst_label"><?= h(L_('MOD_LISTINGS:FIELD_LABEL')) ?> *</label>
        <div><input type="text" id="lst_label" name="label" maxlength="255" required value="<?= h($lstForm['label']) ?>"></div>
    </div>
    <div class="lst-row">
        <label for="lst_name"><?= h(L_('MOD_LISTINGS:FIELD_NAME')) ?></label>
        <div>
            <?php if ($v['name_locked']): ?>
                <code><?= h($lstField['name']) ?></code>
                <p class="lst-help"><?= h(L_('MOD_LISTINGS:HELP_NAME_LOCKED')) ?></p>
            <?php else: ?>
                <input type="text" id="lst_name" name="name" maxlength="40" pattern="[a-z][a-z0-9_]*" value="<?= h($lstForm['name']) ?>">
                <p class="lst-help"><?= h(L_('MOD_LISTINGS:HELP_NAME')) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="lst-row">
        <label for="lst_type"><?= h(L_('MOD_LISTINGS:FIELD_TYPE')) ?></label>
        <div>
            <select id="lst_type" name="type" data-lst-type>
                <?php foreach (FieldTypes::TYPES as $lstType): ?>
                    <option value="<?= h($lstType) ?>"<?= $lstForm['type'] === $lstType ? ' selected' : '' ?>><?= h(L_('MOD_LISTINGS:TYPE_' . strtoupper($lstType))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="lst-row" data-lst-for="select radio checkboxes">
        <label for="lst_choices"><?= h(L_('MOD_LISTINGS:FIELD_CHOICES')) ?></label>
        <div>
            <textarea id="lst_choices" name="choices" rows="6"><?= h($lstForm['choices']) ?></textarea>
            <p class="lst-help"><?= h(L_('MOD_LISTINGS:HELP_CHOICES')) ?></p>
        </div>
    </div>
    <div class="lst-row" data-lst-for="text textarea">
        <label for="lst_maxlength"><?= h(L_('MOD_LISTINGS:FIELD_MAXLENGTH')) ?></label>
        <div><input type="number" id="lst_maxlength" name="maxlength" min="1" max="65535" value="<?= h($lstForm['maxlength']) ?>"></div>
    </div>
    <div class="lst-row" data-lst-for="number">
        <label for="lst_min"><?= h(L_('MOD_LISTINGS:FIELD_RANGE')) ?></label>
        <div>
            <input type="text" id="lst_min" name="min" size="8" value="<?= h($lstForm['min']) ?>" placeholder="min"> –
            <input type="text" name="max" size="8" value="<?= h($lstForm['max']) ?>" placeholder="max" aria-label="max">
        </div>
    </div>

    <div class="lst-row">
        <label for="lst_help"><?= h(L_('MOD_LISTINGS:FIELD_HELP')) ?></label>
        <div><input type="text" id="lst_help" name="help" maxlength="255" value="<?= h($lstForm['help']) ?>"></div>
    </div>
    <div class="lst-row" data-lst-for="text textarea number email url phone date datetime select radio checkboxes boolean">
        <span class="lst-label"><?= h(L_('MOD_LISTINGS:FIELD_OPTIONS')) ?></span>
        <div>
            <label><input type="checkbox" name="required" value="1"<?= $lstCheck('required') ?>> <?= h(L_('MOD_LISTINGS:FIELD_REQUIRED')) ?></label><br>
            <label><input type="checkbox" name="in_list" value="1"<?= $lstCheck('in_list') ?>> <?= h(L_('MOD_LISTINGS:FIELD_IN_LIST')) ?></label><br>
            <label><input type="checkbox" name="editable" value="1"<?= $lstCheck('editable') ?>> <?= h(L_('MOD_LISTINGS:FIELD_EDITABLE')) ?></label>
            <p class="lst-help"><?= h(L_('MOD_LISTINGS:HELP_EDITABLE')) ?></p>
        </div>
    </div>
    <div class="lst-row" data-lst-for="text textarea number email url phone date datetime select radio checkboxes boolean">
        <label for="lst_visibility"><?= h(L_('MOD_LISTINGS:FIELD_VISIBILITY')) ?></label>
        <div>
            <select id="lst_visibility" name="visibility">
                <?php foreach (Fields::VISIBILITY as $lstVis): ?>
                    <option value="<?= h($lstVis) ?>"<?= $lstForm['visibility'] === $lstVis ? ' selected' : '' ?>><?= h(L_('MOD_LISTINGS:VISIBILITY_' . strtoupper($lstVis))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="lst-buttons">
        <button type="submit"><?= h(L_('TEXT:SAVE')) ?></button>
        <a href="<?= h(($v['url'])(['lst_view' => 'fields'])) ?>"><?= h(L_('TEXT:CANCEL')) ?></a>
    </div>
</form>
</div>
