<?php
/** Listings backend: add or edit an entry. @var array<string, mixed> $v */
defined('WB_PATH') or die('This file cannot be accessed directly!');

use Listings\FieldTypes;
use Listings\Items;
use Listings\Module;

$lstForm = $v['form'];
$lstItem = $v['item'];
?>
<h3><?= h($lstItem === null ? L_('MOD_LISTINGS:NEW_ITEM') : L_('MOD_LISTINGS:EDIT_ITEM', $lstItem['title'])) ?></h3>
<form method="post" action="<?= h($v['action']) ?>" class="lst-form">
    <?= $v['hidden'] ?>
    <input type="hidden" name="lst_action" value="item_save">
    <?php if ($lstItem !== null): ?>
        <input type="hidden" name="lst_item" value="<?= h(Module::idKey($v['admin'], 'item', $lstItem['item_id'])) ?>">
    <?php endif; ?>

    <div class="lst-row">
        <label for="lst_title"><?= h(L_('MOD_LISTINGS:TITLE')) ?> *</label>
        <div><input type="text" id="lst_title" name="title" maxlength="255" required value="<?= h($lstForm['title'] ?? '') ?>"></div>
    </div>

    <?php foreach ($v['fields'] as $lstField): ?>
        <?php if ($lstField['type'] === 'heading'): ?>
            <?= FieldTypes::input($lstField, null) ?>
        <?php else: ?>
        <div class="lst-row">
            <label for="lst_f_<?= h($lstField['name']) ?>">
                <?= h($lstField['label']) ?><?= $lstField['required'] ? ' *' : '' ?>
                <?php if ($lstField['visibility'] === 'admin'): ?><span class="lst-badge"><?= h(L_('MOD_LISTINGS:VISIBILITY_ADMIN')) ?></span><?php endif; ?>
            </label>
            <div>
                <?= FieldTypes::input($lstField, $lstForm['fields'][$lstField['name']] ?? null) ?>
                <?php if ($lstField['help'] !== ''): ?><p class="lst-help"><?= h($lstField['help']) ?></p><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <fieldset class="lst-meta">
        <legend><?= h(L_('MOD_LISTINGS:PUBLICATION')) ?></legend>
        <div class="lst-row">
            <label for="lst_status"><?= h(L_('MOD_LISTINGS:STATUS')) ?></label>
            <div>
                <select id="lst_status" name="status">
                    <?php foreach (Items::STATUSES as $lstStatus): ?>
                        <option value="<?= h($lstStatus) ?>"<?= ($lstForm['status'] ?? 'draft') === $lstStatus ? ' selected' : '' ?>><?= h(L_('MOD_LISTINGS:STATUS_' . strtoupper($lstStatus))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="lst-row">
            <label for="lst_owner_email"><?= h(L_('MOD_LISTINGS:OWNER_EMAIL')) ?></label>
            <div>
                <input type="email" id="lst_owner_email" name="owner_email" maxlength="255" value="<?= h($lstForm['owner_email'] ?? '') ?>">
                <p class="lst-help"><?= h(L_('MOD_LISTINGS:HELP_OWNER_EMAIL_ADMIN')) ?></p>
            </div>
        </div>
        <div class="lst-row">
            <label for="lst_expires"><?= h(L_('MOD_LISTINGS:EXPIRES')) ?></label>
            <div>
                <input type="date" id="lst_expires" name="expires" value="<?= h($lstForm['expires'] ?? '') ?>">
                <p class="lst-help"><?= h(L_('MOD_LISTINGS:HELP_EXPIRES')) ?></p>
            </div>
        </div>
        <div class="lst-row">
            <label for="lst_slug"><?= h(L_('MOD_LISTINGS:SLUG')) ?></label>
            <div>
                <input type="text" id="lst_slug" name="slug" maxlength="180" value="<?= h($lstForm['slug'] ?? '') ?>">
                <p class="lst-help"><?= h(L_('MOD_LISTINGS:HELP_SLUG')) ?></p>
            </div>
        </div>
        <?php if ($lstItem !== null): ?>
        <p class="lst-help">
            <?= h(L_('MOD_LISTINGS:CREATED')) ?>: <?= h(Module::formatTime($lstItem['created_at'])) ?> ·
            <?= h(L_('MOD_LISTINGS:UPDATED')) ?>: <?= h(Module::formatTime($lstItem['updated_at'])) ?>
        </p>
        <?php endif; ?>
    </fieldset>

    <div class="lst-buttons">
        <button type="submit" name="lst_then" value="list"><?= h(L_('TEXT:SAVE')) ?></button>
        <button type="submit" name="lst_then" value="edit"><?= h(L_('MOD_LISTINGS:SAVE_CONTINUE')) ?></button>
        <a href="<?= h(($v['url'])(['lst_view' => 'items'])) ?>"><?= h(L_('TEXT:CANCEL')) ?></a>
    </div>
</form>
</div>
