<?php
/**
 * Listings frontend: confirm a new entry. A button, not the link itself:
 * mail scanners open links in advance and would confirm for the visitor.
 * @var array<string, mixed> $v
 */
defined('WB_PATH') or die('This file cannot be accessed directly!');
?>
<div class="mod_listings lst-owner" id="lst-<?= (int) $v['section_id'] ?>">
    <h2><?= h(L_('MOD_LISTINGS:CONFIRM_ENTRY')) ?></h2>
    <p class="lst-intro"><?= h(L_('MOD_LISTINGS:INTRO_CONFIRM')) ?></p>
    <form method="post" action="<?= h($v['action']) ?>">
        <?= $v['hidden'] ?>
        <input type="hidden" name="lst_action" value="confirm">
        <input type="hidden" name="token" value="<?= h($v['token']) ?>">
        <button type="submit"><?= h(L_('MOD_LISTINGS:CONFIRM_BUTTON')) ?></button>
    </form>
</div>
