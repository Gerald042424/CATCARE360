<?php

use App\Services\AdoptionReturnService;

$requestUrl = 'adopter/portal/' . $cat['adoption_id'] . '/return-request';
$open = in_array($returnRequest['status'] ?? '', AdoptionReturnService::OPEN_STATUSES, true);
$selectedReason = (string) old('reason', '');
?>
<section id="return-request" class="mb-8 rounded-2xl border border-slate-200 bg-white p-5">
    <h2 class="text-lg font-semibold text-slate-900">Return to McKinley West Cats</h2>
    <?php if ($open): ?>
        <p class="mt-2 font-semibold text-slate-900"><?= esc($returnRequest['status']) ?></p>
        <?php if ($returnRequest['status'] === 'Handover Scheduled'): ?>
            <p class="mt-2 text-sm text-slate-600">
                <?= esc(date('M d, Y, g:i A', strtotime($returnRequest['handover_at']))) ?> (Philippine time)
                · <?= esc($returnRequest['handover_location']) ?>
            </p>
        <?php else: ?>
            <p class="mt-2 text-sm text-slate-600">Staff will contact you to arrange the handover.</p>
        <?php endif; ?>
        <form method="post" action="<?= site_url($requestUrl . '/cancel') ?>" class="mt-3">
            <?= csrf_field() ?>
            <button class="btn btn-ghost" type="submit">Cancel Request</button>
        </form>
    <?php else: ?>
        <?php if (($returnRequest['status'] ?? '') === 'Cancelled'): ?>
            <p class="mt-2 text-sm text-slate-600">Your previous request was cancelled.</p>
        <?php endif; ?>
        <details class="mt-3" <?= old('reason') ? 'open' : '' ?>>
            <summary class="cursor-pointer font-semibold text-rescue-700">Request to Return Cat</summary>
            <p class="mt-3 text-sm text-slate-600">If you can no longer care for <?= esc($cat['name']) ?>, request a handover with McKinley West Cats. Please continue care and monitoring until staff receives your cat.</p>
            <form method="post" action="<?= site_url($requestUrl) ?>" class="mt-4 max-w-xl space-y-3"
                x-data="{ reason: <?= esc(json_encode($selectedReason, JSON_INVALID_UTF8_SUBSTITUTE), 'attr') ?> }">
                <?= csrf_field() ?>
                <p class="text-sm text-slate-700"><?= esc($cat['name']) ?> · <?= esc($adopter['name'] ?? '') ?></p>
                <label class="block"><span class="label">Reason</span>
                    <select class="field" name="reason" x-model="reason" required>
                        <option value="">Choose a reason</option>
                        <?php foreach (AdoptionReturnService::REASONS as $reason): ?>
                            <option <?= $selectedReason === $reason ? 'selected' : '' ?>><?= esc($reason) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block" x-show="reason === 'Other'" x-cloak>
                    <span class="label">Please specify your reason</span>
                    <input class="field" type="text" name="other_reason" maxlength="<?= AdoptionReturnService::REASON_LENGTH ?>"
                        value="<?= esc(old('other_reason') ?? '', 'attr') ?>" :required="reason === 'Other'" :disabled="reason !== 'Other'">
                </label>
                <label class="block"><span class="label">Notes (optional)</span>
                    <textarea class="field" name="notes" maxlength="1000" rows="3"><?= esc(old('notes') ?? '') ?></textarea>
                </label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="urgent" value="1" <?= old('urgent') === '1' ? 'checked' : '' ?>> This is urgent</label>
                <button type="submit" class="btn btn-primary">Submit Return Request</button>
            </form>
        </details>
    <?php endif; ?>
</section>
