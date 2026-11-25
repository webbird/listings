<?php
/** Listings backend: list of entries. @var array<string, mixed> $v */
defined('WB_PATH') or die('This file cannot be accessed directly!');

use Listings\Module;
?>
<div class="lst-toolbar">
    <a class="lst-button" href="<?= h(($v['url'])(['lst_view' => 'item'])) ?>"><?= h(L_('MOD_LISTINGS:NEW_ITEM')) ?></a>
    <span class="lst-filter">
        <?php foreach ($v['counts'] as $lstKey => $lstCount):
            if ($lstKey !== '' && $lstCount === 0 && $v['status'] !== $lstKey) {
                continue;
            }
            $lstParams = ['lst_view' => 'items'] + ($lstKey === '' ? [] : ['lst_status' => $lstKey]); ?>
            <a href="<?= h(($v['url'])($lstParams)) ?>" class="<?= ($v['status'] ?? '') === $lstKey ? 'active' : '' ?><?= $lstKey === 'pending' ? ' lst-waiting' : '' ?>">
                <?= h(L_($lstKey === '' ? 'MOD_LISTINGS:ALL' : 'MOD_LISTINGS:STATUS_' . strtoupper($lstKey))) ?> (<?= (int) $lstCount ?>)
            </a>
        <?php endforeach; ?>
    </span>
</div>

<?php if ($v['fields'] === []): ?>
    <p class="lst-hint"><?= h(L_('MOD_LISTINGS:HINT_NO_FIELDS')) ?>
        <a href="<?= h(($v['url'])(['lst_view' => 'field'])) ?>"><?= h(L_('MOD_LISTINGS:NEW_FIELD')) ?></a></p>
<?php endif; ?>

<?php if ($v['items'] === []): ?>
    <p><?= h(L_('MOD_LISTINGS:NO_ENTRIES')) ?></p>
<?php else: ?>
<table class="lst-table">
    <thead>
        <tr>
            <th><?= h(L_('MOD_LISTINGS:TITLE')) ?></th>
            <th><?= h(L_('MOD_LISTINGS:STATUS')) ?></th>
            <th><?= h(L_('MOD_LISTINGS:UPDATED')) ?></th>
            <th><?= h(L_('MOD_LISTINGS:EXPIRES')) ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($v['items'] as $lstItem):
        $lstExpired = $lstItem['expires_at'] > 0 && $lstItem['expires_at'] <= time();
        // a draft with an owner address was added in the frontend and is not confirmed yet
        $lstStatusKey = $lstItem['status'] === 'draft' && $lstItem['owner_email'] !== '' ? 'UNCONFIRMED' : strtoupper($lstItem['status']);
        $lstKey = Module::idKey($v['admin'], 'item', $lstItem['item_id']); ?>
        <tr class="<?= $lstItem['status'] === 'pending' ? 'lst-row-waiting' : '' ?>">
            <td>
                <a href="<?= h(($v['url'])(['lst_view' => 'item', 'lst_item' => $lstItem['item_id']])) ?>"><?= h($lstItem['title']) ?></a>
                <?php if ($lstItem['owner_email'] !== ''): ?><br><span class="lst-help"><?= h($lstItem['owner_email']) ?></span><?php endif; ?>
            </td>
            <td><span class="lst-status lst-status-<?= h($lstItem['status']) ?>"><?= h(L_('MOD_LISTINGS:STATUS_' . $lstStatusKey)) ?></span></td>
            <td><?= h(Module::formatTime($lstItem['updated_at'])) ?></td>
            <td class="<?= $lstExpired ? 'lst-expired' : '' ?>"><?= $lstItem['expires_at'] > 0 ? h(date('Y-m-d', $lstItem['expires_at'])) : '' ?></td>
            <td class="lst-actions">
                <?php if ($lstItem['status'] === 'pending'): ?>
                    <?php foreach (['published' => 'APPROVE', 'rejected' => 'REJECT'] as $lstTo => $lstLabel): ?>
                    <form method="post" action="<?= h($v['action']) ?>">
                        <?= $v['hidden'] ?>
                        <input type="hidden" name="lst_action" value="item_status">
                        <input type="hidden" name="lst_status" value="<?= $lstTo ?>">
                        <input type="hidden" name="lst_item" value="<?= h($lstKey) ?>">
                        <button type="submit" class="lst-button lst-<?= strtolower($lstLabel) ?>"><?= h(L_('MOD_LISTINGS:' . $lstLabel)) ?></button>
                    </form>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="<?= h(($v['url'])(['lst_view' => 'item', 'lst_item' => $lstItem['item_id']])) ?>"><?= h(L_('TEXT:MODIFY')) ?></a>
                <form method="post" action="<?= h($v['action']) ?>" data-lst-confirm="<?= h(L_('MOD_LISTINGS:CONFIRM_DELETE_ITEM', $lstItem['title'])) ?>">
                    <?= $v['hidden'] ?>
                    <input type="hidden" name="lst_action" value="item_delete">
                    <input type="hidden" name="lst_item" value="<?= h($lstKey) ?>">
                    <button type="submit" class="lst-link-button"><?= h(L_('TEXT:DELETE')) ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if ($v['pages'] > 1): ?>
<nav class="lst-pager">
    <?php for ($lstP = 1; $lstP <= $v['pages']; $lstP++):
        $lstParams = ['lst_view' => 'items', 'lst_page' => $lstP] + ($v['status'] !== null ? ['lst_status' => $v['status']] : []); ?>
        <?php if ($lstP === $v['page']): ?><strong><?= $lstP ?></strong><?php else: ?><a href="<?= h(($v['url'])($lstParams)) ?>"><?= $lstP ?></a><?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php endif; ?>
</div>
