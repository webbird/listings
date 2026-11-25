<?php
/** Listings backend: settings of the directory. @var array<string, mixed> $v */
defined('WB_PATH') or die('This file cannot be accessed directly!');

use Listings\Settings;

$lstForm = $v['form'];
$lstCheck = static fn (string $key): string => ($lstForm[$key] ?? '0') === '1' ? ' checked' : '';
?>
<form method="post" action="<?= h($v['action']) ?>" class="lst-form">
    <?= $v['hidden'] ?>
    <input type="hidden" name="lst_action" value="settings_save">

    <fieldset>
        <legend><?= h(L_('MOD_LISTINGS:SETTINGS_LIST')) ?></legend>
        <div class="lst-row">
            <label for="lst_per_page"><?= h(L_('MOD_LISTINGS:PER_PAGE')) ?></label>
            <div><input type="number" id="lst_per_page" name="per_page" min="1" max="<?= Settings::PER_PAGE_MAX ?>" required value="<?= h($lstForm['per_page'] ?? '20') ?>"></div>
        </div>
        <div class="lst-row">
            <label for="lst_sort"><?= h(L_('MOD_LISTINGS:SORT')) ?></label>
            <div>
                <select id="lst_sort" name="sort">
                    <?php foreach (array_keys(Settings::SORTS) as $lstSort): ?>
                        <option value="<?= h($lstSort) ?>"<?= ($lstForm['sort'] ?? '') === $lstSort ? ' selected' : '' ?>><?= h(L_('MOD_LISTINGS:SORT_' . strtoupper($lstSort))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= h(L_('MOD_LISTINGS:SETTINGS_FRONTEND')) ?></legend>
        <div class="lst-row">
            <span class="lst-label"><?= h(L_('MOD_LISTINGS:FRONTEND')) ?></span>
            <div>
                <label><input type="checkbox" name="frontend" value="1"<?= $lstCheck('frontend') ?>> <?= h(L_('MOD_LISTINGS:FRONTEND_ON')) ?></label>
                <p class="lst-help"><?= h(L_('MOD_LISTINGS:HELP_FRONTEND')) ?></p>
            </div>
        </div>
        <div class="lst-row">
            <span class="lst-label"><?= h(L_('MOD_LISTINGS:APPROVAL')) ?></span>
            <div>
                <label><input type="checkbox" name="approval" value="1"<?= $lstCheck('approval') ?>> <?= h(L_('MOD_LISTINGS:APPROVAL_ON')) ?></label><br>
                <label><input type="checkbox" name="recheck" value="1"<?= $lstCheck('recheck') ?>> <?= h(L_('MOD_LISTINGS:RECHECK_ON')) ?></label>
            </div>
        </div>
        <div class="lst-row">
            <label for="lst_notify"><?= h(L_('MOD_LISTINGS:NOTIFY_EMAIL')) ?></label>
            <div>
                <input type="email" id="lst_notify" name="notify_email" maxlength="255" value="<?= h($lstForm['notify_email'] ?? '') ?>">
                <p class="lst-help"><?= h(L_('MOD_LISTINGS:HELP_NOTIFY_EMAIL')) ?></p>
            </div>
        </div>
        <div class="lst-row">
            <span class="lst-label"><?= h(L_('MOD_LISTINGS:SPAM')) ?></span>
            <div>
                <label><input type="checkbox" name="captcha" value="1"<?= $lstCheck('captcha') ?><?= class_exists('Captcha') ? '' : ' disabled' ?>> <?= h(L_('MOD_LISTINGS:CAPTCHA_ON')) ?></label>
                <p class="lst-help"><?= h(L_(class_exists('Captcha') ? 'MOD_LISTINGS:HELP_CAPTCHA' : 'MOD_LISTINGS:HELP_NO_CAPTCHA')) ?></p>
            </div>
        </div>
    </fieldset>

    <div class="lst-buttons">
        <button type="submit"><?= h(L_('TEXT:SAVE')) ?></button>
    </div>
</form>
</div>
