<?php
/** Listings frontend: a message above the list or instead of a form. @var array<string, mixed> $v */
defined('WB_PATH') or die('This file cannot be accessed directly!');

$lstError = ($v['type'] ?? '') === 'error';
?>
<div class="mod_listings"><div class="lst-message <?= $lstError ? 'lst-error' : 'lst-success' ?>" role="<?= $lstError ? 'alert' : 'status' ?>">
    <p><?= h($v['text']) ?></p>
    <?php if (!empty($v['link_url'])): ?>
        <p><a href="<?= h($v['link_url']) ?>"><?= h(L_('MOD_LISTINGS:REQUEST_LINK')) ?></a>
        <?php if (!empty($v['list_url'])): ?> · <a href="<?= h($v['list_url']) ?>"><?= h(L_('MOD_LISTINGS:BACK_TO_LIST')) ?></a><?php endif; ?></p>
    <?php endif; ?>
</div></div>
