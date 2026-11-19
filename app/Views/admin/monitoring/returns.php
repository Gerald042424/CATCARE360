<?= $this->extend('admin/admin_layout') ?>
<?= $this->section('content') ?>
<?php use App\Services\AdoptionReturnService; ?>
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-3xl font-bold">Return Requests</h1>
        <p class="mt-1 text-slate-500">Coordinate handovers and record cats received by McKinley West Cats.</p>
    </div>
    <a class="btn btn-ghost" href="<?= site_url('admin/monitoring') ?>">Back to Monitoring</a>
</div>
<?php if (! $enabled): ?>
    <div class="card text-slate-600">Return requests will be available after the database update is applied.</div>
<?php elseif ($requests === []): ?>
    <div class="card text-slate-600">No return requests yet.</div>
<?php endif; ?>
<div class="space-y-4">
    <?php foreach ($requests as $request): ?>
        <?php
        $actions = 'admin/monitoring/returns/' . $request['adoption_id'];
        $open = in_array($request['status'], AdoptionReturnService::OPEN_STATUSES, true);
        ?>
        <article id="return-<?= (int) $request['adoption_id'] ?>" class="card space-y-3">
            <header class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-bold"><?= esc($request['cat_name'] ?? 'Cat record') ?> · <?= esc($request['adopter_name'] ?? 'Adopter record') ?></h2>
                <div class="flex items-center gap-2">
                    <?php if ($request['urgent'] && $open): ?><span class="rounded-full bg-red-50 px-3 py-1 text-sm font-bold text-red-700">Urgent</span><?php endif; ?>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold"><?= esc($request['status']) ?></span>
                </div>
            </header>
            <p class="text-sm"><span class="font-semibold">Reason:</span> <?= esc($request['reason']) ?></p>
            <?php if ($request['notes'] !== ''): ?><p class="whitespace-pre-wrap break-words text-sm text-slate-700"><?= esc($request['notes'] ?? '') ?></p><?php endif; ?>
            <?php if ($open): ?>
                <p class="text-sm text-slate-600"><?= esc(implode(' · ', array_filter([$request['email'], $request['phone']]))) ?></p>
                <form method="post" action="<?= site_url($actions . '/schedule') ?>" class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <?= csrf_field() ?>
                    <label><span class="label">Handover date and time (Philippine time)</span>
                        <input class="field" type="datetime-local" name="handover_at" required
                            value="<?= esc($request['handover_at'] ? str_replace(' ', 'T', substr($request['handover_at'], 0, 16)) : '') ?>">
                    </label>
                    <label><span class="label">Handover location</span>
                        <input class="field" name="handover_location" maxlength="255" required value="<?= esc($request['handover_location'] ?? '') ?>">
                    </label>
                    <div><button class="btn btn-primary" type="submit">Save &amp; Email Schedule</button></div>
                </form>
                <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-3">
                    <?php if ($request['status'] === 'Handover Scheduled'): ?>
                        <form method="post" action="<?= site_url($actions . '/confirm') ?>" onsubmit="return confirm('Confirm that McKinley West Cats has physically received this cat?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary">Confirm Return</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="<?= site_url($actions . '/cancel') ?>" onsubmit="return confirm('Cancel this return request? The adoption will stay active.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-ghost">Cancel Request</button>
                    </form>
                </div>
            <?php elseif ($request['status'] === 'Returned' && $request['animal_id']): ?>
                <a class="btn btn-ghost" href="<?= site_url('admin/animals/' . $request['animal_id']) ?>">Open Cat Record</a>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
