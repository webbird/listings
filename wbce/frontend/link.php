<?php
/** Listings frontend: an owner asks for edit links. @var array<string, mixed> $v */
defined('WB_PATH') or die('This file cannot be accessed directly!');

$lstSid = (int) $v['section_id'];
?>
<div class="mod_listings lst-owner" id="lst-<?= $lstSid ?>">
    <h2><?= h(L_('MOD_LISTINGS:REQUEST_LINK')) ?></h2>
    <?php if ($v['errors'] !== []): ?>
        <div class="lst-message lst-error" role="alert">
            <ul><?php foreach ($v['errors'] as $lstError): ?><li><?= h($lstError) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <p class="lst-intro"><?= h(L_('MOD_LISTINGS:INTRO_REQUEST_LINK')) ?></p>
    <form method="post" action="<?= h($v['action']) ?>" class="lst-owner-form">
        <?= $v['hidden'] ?>
        <input type="hidden" name="lst_action" value="request">
        <div class="lst-row">
            <label for="lst_email_<?= $lstSid ?>"><?= h(L_('MOD_LISTINGS:OWNER_EMAIL')) ?> *</label>
            <input type="email" id="lst_email_<?= $lstSid ?>" name="owner_email" maxlength="255" required autocomplete="email" value="<?= h($v['values']['owner_email'] ?? '') ?>">
        </div>
        <div class="lst-hp" aria-hidden="true">
            <label for="lst_hp_<?= $lstSid ?>">Website</label>
            <input type="text" id="lst_hp_<?= $lstSid ?>" name="lst_hp" value="" tabindex="-1" autocomplete="off">
        </div>
        <?php if ($v['captcha'] !== ''): ?><div class="lst-captcha"><?= $v['captcha'] ?></div><?php endif; ?>
        <div class="lst-buttons">
            <button type="submit"><?= h(L_('MOD_LISTINGS:SEND_LINK')) ?></button>
            <a href="<?= h($v['list_url']) ?>"><?= h(L_('MOD_LISTINGS:BACK_TO_LIST')) ?></a>
        </div>
    </form>
</div>
