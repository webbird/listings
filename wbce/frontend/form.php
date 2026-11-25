<?php
/** Listings frontend: an owner adds (mode new) or edits (mode edit) an entry. @var array<string, mixed> $v */
defined('WB_PATH') or die('This file cannot be accessed directly!');

use Listings\FieldTypes;

$lstNew = $v['mode'] === 'new';
$lstValues = $v['values'];
$lstItem = $v['item'];
$lstSid = (int) $v['section_id'];
?>
<div class="mod_listings lst-owner" id="lst-<?= $lstSid ?>">
    <h2><?= h($lstNew ? L_('MOD_LISTINGS:NEW_ENTRY') : L_('MOD_LISTINGS:EDIT_ITEM', $lstItem['title'])) ?></h2>

    <?php if ($v['success'] !== ''): ?>
        <p class="lst-message lst-success" role="status"><?= h($v['success']) ?></p>
    <?php endif; ?>
    <?php if ($v['errors'] !== []): ?>
        <div class="lst-message lst-error" role="alert">
            <ul><?php foreach ($v['errors'] as $lstError): ?><li><?= h($lstError) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($lstNew): ?>
        <p class="lst-intro"><?= h(L_($v['approval'] ? 'MOD_LISTINGS:INTRO_NEW_APPROVAL' : 'MOD_LISTINGS:INTRO_NEW')) ?></p>
    <?php else: ?>
        <p class="lst-intro"><?= h(L_('MOD_LISTINGS:INTRO_STATUS_' . strtoupper($lstItem['status']))) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= h($v['action']) ?>" class="lst-owner-form">
        <?= $v['hidden'] ?>
        <input type="hidden" name="lst_action" value="<?= $lstNew ? 'create' : 'update' ?>">
        <?php if (!$lstNew): ?><input type="hidden" name="token" value="<?= h($v['token']) ?>"><?php endif; ?>

        <div class="lst-row">
            <label for="lst_title_<?= $lstSid ?>"><?= h(L_('MOD_LISTINGS:TITLE')) ?> *</label>
            <input type="text" id="lst_title_<?= $lstSid ?>" name="title" maxlength="255" required value="<?= h($lstValues['title'] ?? '') ?>">
        </div>

        <?php if ($lstNew): ?>
        <div class="lst-row">
            <label for="lst_email_<?= $lstSid ?>"><?= h(L_('MOD_LISTINGS:OWNER_EMAIL')) ?> *</label>
            <input type="email" id="lst_email_<?= $lstSid ?>" name="owner_email" maxlength="255" required autocomplete="email" value="<?= h($lstValues['owner_email'] ?? '') ?>">
            <p class="lst-help"><?= h(L_('MOD_LISTINGS:HELP_OWNER_EMAIL')) ?></p>
        </div>
        <?php endif; ?>

        <?php foreach ($v['fields'] as $lstField): ?>
            <?php if ($lstField['type'] === 'heading'): ?>
                <?= FieldTypes::input($lstField, null) ?>
            <?php else: ?>
            <div class="lst-row">
                <label for="lst_f_<?= h($lstField['name']) ?>">
                    <?= h($lstField['label']) ?><?= $lstField['required'] ? ' *' : '' ?>
                    <?php if ($lstField['visibility'] === 'owner'): ?><span class="lst-badge"><?= h(L_('MOD_LISTINGS:NOT_PUBLIC')) ?></span><?php endif; ?>
                </label>
                <?= FieldTypes::input($lstField, $lstValues['fields'][$lstField['name']] ?? null) ?>
                <?php if ($lstField['help'] !== ''): ?><p class="lst-help"><?= h($lstField['help']) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($lstNew): ?>
            <div class="lst-hp" aria-hidden="true">
                <label for="lst_hp_<?= $lstSid ?>">Website</label>
                <input type="text" id="lst_hp_<?= $lstSid ?>" name="lst_hp" value="" tabindex="-1" autocomplete="off">
            </div>
            <div class="lst-row lst-privacy">
                <label><input type="checkbox" name="privacy" value="1" required<?= ($lstValues['privacy'] ?? '') === '1' ? ' checked' : '' ?>> <?= h(L_('MOD_LISTINGS:PRIVACY_CONSENT')) ?></label>
            </div>
            <?php if ($v['captcha'] !== ''): ?><div class="lst-captcha"><?= $v['captcha'] ?></div><?php endif; ?>
        <?php endif; ?>

        <div class="lst-buttons">
            <button type="submit"><?= h(L_($lstNew ? 'MOD_LISTINGS:SUBMIT_NEW' : 'TEXT:SAVE')) ?></button>
            <a href="<?= h($v['list_url']) ?>"><?= h(L_('MOD_LISTINGS:BACK_TO_LIST')) ?></a>
        </div>
    </form>

    <?php if (!$lstNew): ?>
    <form method="post" action="<?= h($v['action']) ?>" class="lst-delete" data-lst-confirm="<?= h(L_('MOD_LISTINGS:CONFIRM_DELETE_ITEM', $lstItem['title'])) ?>">
        <?= $v['hidden'] ?>
        <input type="hidden" name="lst_action" value="delete">
        <input type="hidden" name="token" value="<?= h($v['token']) ?>">
        <button type="submit"><?= h(L_('MOD_LISTINGS:DELETE_ENTRY')) ?></button>
    </form>
    <?php endif; ?>
</div>
