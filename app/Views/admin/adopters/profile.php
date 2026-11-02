<?= $this->extend('admin/admin_layout') ?>
<?= $this->section('content') ?>
<div class="grid gap-6 xl:grid-cols-[.8fr_1.2fr]">
    <aside class="card">
        <a href="<?= site_url('admin/adopters') ?>" class="text-sm font-semibold text-rescue-600">← Back to Adopters</a>
        <div class="mt-6 flex h-24 w-24 items-center justify-center rounded-full bg-rescue-50 text-3xl font-bold text-rescue-700"><?= esc(substr($adopter['name'], 0, 1)) ?></div>
        <h1 class="mt-4 text-3xl font-bold"><?= esc($adopter['name']) ?></h1>
        <p class="text-slate-500"><?= esc($adopter['email']) ?><br><?= esc($adopter['phone']) ?></p>
        <div class="mt-5 space-y-2 text-sm"><div><b>GPS Verified for selected cat:</b> <?= esc($selectedSnapshot['gps_verified_count']) ?></div></div>
        <form method="post" class="mt-6 border-t border-slate-100 pt-5">
            <?= csrf_field() ?>
            <h2 class="mb-4 text-lg font-bold">Edit Adopter</h2>
            <div class="space-y-3">
                <div><label class="label">Full Name</label><input class="field" required name="name" value="<?= esc(old('name', $adopter['name'])) ?>"></div>
                <div><label class="label">Email</label><input class="field" required type="email" name="email" value="<?= esc(old('email', $adopter['email'])) ?>"></div>
                <div><label class="label">Phone</label><input class="field" name="phone" value="<?= esc(old('phone', $adopter['phone'])) ?>"></div>
                <div><label class="label">Address</label><textarea class="field min-h-20" name="address"><?= esc(old('address', $adopter['address'])) ?></textarea></div>
                <div><label class="label">Occupation</label><input class="field" name="occupation" value="<?= esc(old('occupation', $adopter['occupation'])) ?>"></div>
                <div><label class="label">Housing Type</label><input class="field" name="housing_type" value="<?= esc(old('housing_type', $adopter['housing_type'])) ?>"></div>
                <div><label class="label">Account Status Flag</label><select name="flag" class="field"><?php foreach (['None', 'WANTED', 'MISSING'] as $f): ?><option <?= $adopter['flag'] === $f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?></select></div>
            </div>
            <button class="btn btn-primary mt-5 w-full justify-center">Save Changes</button>
        </form>
    </aside>

    <section class="space-y-6">
        <?php if (count($adoptions) > 1): ?>
            <form method="get" class="card flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1"><label class="label" for="selected-adoption">Active Adoption</label><select id="selected-adoption" name="adoption_id" class="field" onchange="this.form.submit()"><?php foreach ($adoptions as $adoption): ?><option value="<?= (int) $adoption['id'] ?>" <?= (int) ($selectedAdoption['id'] ?? 0) === (int) $adoption['id'] ? 'selected' : '' ?>><?= esc($adoption['name']) ?> · adopted <?= esc(date('M d, Y', strtotime($adoption['adoption_date']))) ?></option><?php endforeach; ?></select></div>
                <noscript><button class="btn btn-primary">View adoption</button></noscript>
                <p class="text-xs text-slate-500 sm:max-w-52">Change the selected cat to view only that adoption's compliance and submissions.</p>
            </form>
        <?php endif; ?>

        <div class="card">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-bold">Monitoring Overview</h2><?php if ($selectedAdoption): ?><p class="mt-1 text-sm text-slate-500"><?= esc($selectedAdoption['name']) ?> only</p><?php endif; ?></div><?php if ($selectedAdoption): ?><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold <?= $selectedSnapshot['flag'] === 'None' ? 'bg-slate-100 text-slate-600' : ($selectedSnapshot['flag'] === 'WANTED' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700') ?>"><?= esc($selectedSnapshot['flag']) ?></span><?php endif; ?></div>
            <?php if (! $selectedAdoption): ?>
                <p class="mt-4 text-lg font-semibold text-slate-700">Not started</p><p class="mt-1 text-sm text-slate-500">No active adoption is under monitoring yet.</p>
            <?php elseif (! $selectedSnapshot['has_started']): ?>
                <p class="mt-4 text-lg font-semibold text-adopted-700">On track</p><p class="mt-1 text-sm text-slate-500">No required check-in is due yet. The compliance score will start on the first due date.</p>
            <?php else: ?>
                <div class="mt-4 text-5xl font-bold text-rescue-600"><?= esc($selectedSnapshot['score']) ?>%</div>
                <p class="mt-2 text-sm text-slate-500"><?= esc($selectedSnapshot['verified_expected_count']) ?> of <?= esc($selectedSnapshot['expected_count']) ?> due check-ins verified</p>
                <div class="mt-4 h-3 rounded-full bg-slate-100"><div class="h-3 rounded-full bg-rescue-600" style="width:<?= esc($selectedSnapshot['score']) ?>%"></div></div>
            <?php endif; ?>
            <?php if ($selectedAdoption): ?><div class="mt-5 border-t border-slate-100 pt-4 text-sm"><span class="text-slate-500">Next check-in:</span> <strong><?= $selectedSnapshot['next_check_in'] ? esc(date('M d, Y', strtotime($selectedSnapshot['next_check_in']))) : 'All checkpoints completed' ?></strong></div><?php endif; ?>
        </div>

        <div class="card">
            <h2 class="mb-4 text-lg font-bold">Selected Adoption</h2>
            <?php if (! $selectedAdoption): ?><p class="text-sm text-slate-500">No active adoption.</p><?php else: ?><div class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"><img class="h-16 w-16 rounded-full object-cover" src="<?= esc(base_url($selectedAdoption['image_url'])) ?>" alt="<?= esc($selectedAdoption['name']) ?>"><div><div class="text-lg font-semibold"><?= esc($selectedAdoption['name']) ?></div><div class="text-sm text-slate-500"><?= esc($selectedAdoption['breed']) ?> · <?= esc($selectedAdoption['adoption_date']) ?></div></div></div><?php endif; ?>
        </div>

        <div class="card">
            <h2 class="mb-4 text-lg font-bold">Monitoring Submissions</h2>
            <?php if (empty($submissions)): ?><p class="text-sm text-slate-500">No monitoring submission for this cat yet.</p><?php endif; ?>
            <div class="space-y-4">
                <?php foreach ($submissions as $s): ?>
                    <article class="rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3"><div><b><?= esc($s['checkpoint']) ?></b><p class="mt-1 text-xs text-slate-400">Submitted <?= esc(date('M d, Y g:i A', strtotime($s['submission_date']))) ?></p></div><?= statusBadge($s['status']) ?></div>
                        <div class="mt-4 flex flex-col gap-4 sm:flex-row">
                            <?php if (! empty($s['photo_url'])): ?><a href="<?= esc(base_url($s['photo_url'])) ?>" target="_blank" rel="noopener" class="block shrink-0"><img src="<?= esc(base_url($s['photo_url'])) ?>" class="h-40 w-full rounded-xl border border-slate-200 object-cover sm:w-48" alt="<?= esc($s['checkpoint']) ?> monitoring update photo"><span class="mt-1 block text-center text-xs font-semibold text-rescue-600">Open full photo</span></a><?php else: ?><div class="flex h-40 w-full shrink-0 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 text-xs text-slate-400 sm:w-48">No photo submitted</div><?php endif; ?>
                            <div class="min-w-0"><p class="text-sm font-semibold text-slate-700"><?= esc(ucfirst((string) ($s['health_status'] ?: 'General'))) ?> update</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600"><?= esc($s['behavior_notes'] ?: 'No behavior notes provided.') ?></p><?php if (! empty($s['captured_at'])): ?><p class="mt-3 text-xs text-slate-400">Captured <?= esc(date('M d, Y g:i A', strtotime($s['captured_at']))) ?></p><?php endif; ?></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
