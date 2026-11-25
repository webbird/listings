<?php
/** Listings backend: tabs and messages. @var array<string, mixed> $v */
defined('WB_PATH') or die('This file cannot be accessed directly!');

$lstTabs = [
    'items'    => ['MOD_LISTINGS:TAB_ITEMS', ['item']],
    'fields'   => ['MOD_LISTINGS:TAB_FIELDS', ['field']],
    'layouts'  => ['MOD_LISTINGS:TAB_LAYOUTS', []],
    'settings' => ['MOD_LISTINGS:TAB_SETTINGS', []],
];
?>
<div class="mod_listings lst-backend">
<nav class="lst-tabs">
<?php foreach ($lstTabs as $lstTab => [$lstLabel, $lstChildren]):
    $lstActive = $v['view'] === $lstTab || in_array($v['view'], $lstChildren, true); ?>
    <a href="<?= h(($v['url'])(['lst_view' => $lstTab])) ?>" class="<?= $lstActive ? 'active' : '' ?>"><?= h(L_($lstLabel)) ?></a>
<?php endforeach; ?>
</nav>
<?php if ($v['errors'] !== []):
    $lstAlerts = new Alerts();
    foreach ($v['errors'] as $lstError) {
        $lstAlerts->error(h($lstError));
    }
    echo $lstAlerts->renderFragment();
endif; ?>
