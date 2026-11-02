<?= $this->extend('admin/admin_layout') ?>
<?= $this->section('content') ?>
<div class="mb-6"><h1 class="text-3xl font-bold tracking-[-.025em]">Adopter Management</h1><p class="text-slate-500">One record per adopter. Select an active adoption to view that cat's monitoring data.</p></div>
<?php $monitoredAdopters = array_filter($adopters, static fn (array $adopter): bool => (int) ($adopter['monitoring_due_count'] ?? 0) > 0); ?>
<div class="mb-6 grid gap-6 md:grid-cols-4"><?php foreach ([['Total Adopters', count($adopters)], ['Fully Compliant', count(array_filter($monitoredAdopters, fn($a)=>(int) $a['compliance_score']>=85))], ['Needs Attention', count(array_filter($monitoredAdopters, fn($a)=>(int) $a['compliance_score']<85))], ['Overdue Visits', array_sum(array_column($adopters, 'overdue_visits'))]] as $s): ?><div class="card"><div class="text-2xl font-bold"><?= esc($s[1]) ?></div><div class="text-sm font-medium text-slate-500"><?= esc($s[0]) ?></div></div><?php endforeach; ?></div>
<div class="card overflow-x-auto p-0">
    <table class="min-w-[940px] w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3 text-left">Adopter</th><th class="px-4 py-3 text-left">Active Adoption</th><th class="px-4 py-3 text-left">Compliance Score</th><th class="px-4 py-3 text-left">Next Check-in</th><th class="px-4 py-3 text-left">Flag</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
        <tbody>
        <?php foreach ($adopters as $a): ?>
            <?php
            $rowAdoptions = array_map(static fn (array $cat): array => [
                'id' => (int) $cat['adoption_id'],
                'name' => $cat['animal_name'],
                'score' => (int) $cat['compliance_score'],
                'due_count' => (int) $cat['monitoring_due_count'],
                'verified_due_count' => (int) $cat['monitoring_verified_due_count'],
                'next_check_in' => $cat['next_check_in'],
                'next_check_in_label' => $cat['next_check_in'] ? date('M d, Y', strtotime($cat['next_check_in'])) : '—',
                'flag' => $cat['flag'],
                'manage_url' => site_url('admin/adopters/' . $a['id']) . '?adoption_id=' . (int) $cat['adoption_id'],
            ], $a['adopted_cats']);
            ?>
            <tr class="border-t hover:bg-slate-50" x-data='<?= esc(json_encode(['adoptions' => $rowAdoptions, 'selected' => 0], JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>'>
                <td class="px-4 py-3"><a class="font-semibold text-rescue-700 hover:underline" href="<?= site_url('admin/adopters/' . $a['id']) ?>"><?= esc($a['name']) ?></a><div class="text-xs text-slate-500"><?= esc($a['email']) ?></div></td>
                <td class="px-4 py-3">
                    <?php if ($rowAdoptions === []): ?><span class="text-slate-400">None</span>
                    <?php elseif (count($rowAdoptions) === 1): ?><span class="inline-flex rounded-lg border border-rescue-100 bg-rescue-50 px-3 py-2 text-xs font-bold text-rescue-700"><?= esc($rowAdoptions[0]['name']) ?></span>
                    <?php else: ?><label class="sr-only" for="adoption-<?= (int) $a['id'] ?>">Select active adoption</label><select id="adoption-<?= (int) $a['id'] ?>" x-model.number="selected" class="field min-w-36 py-2 text-sm font-semibold"><?php foreach ($rowAdoptions as $index => $cat): ?><option value="<?= $index ?>"><?= esc($cat['name']) ?></option><?php endforeach; ?></select><p class="mt-1 text-xs text-slate-400" x-text="`${adoptions.length} active adoptions`"></p><?php endif; ?>
                </td>
                <td class="px-4 py-3">
                    <?php if ($rowAdoptions === []): ?><span class="text-slate-400">Not started</span>
                    <?php else: ?><div x-show="adoptions[selected].due_count === 0"><span class="font-semibold text-adopted-700">On track</span><div class="mt-1 text-xs text-slate-400">Not due yet</div></div><div x-cloak x-show="adoptions[selected].due_count > 0"><div class="h-2 w-28 rounded bg-slate-100"><div class="h-2 rounded bg-rescue-600" :style="`width:${adoptions[selected].score}%`"></div></div><div class="mt-1 text-xs text-slate-600"><span x-text="`${adoptions[selected].score}% compliance`"></span></div><div class="mt-1 text-xs text-slate-400" x-text="`${adoptions[selected].verified_due_count} of ${adoptions[selected].due_count} due verified`"></div></div><?php endif; ?>
                </td>
                <td class="px-4 py-3"><?php if ($rowAdoptions === []): ?><span class="text-slate-400">—</span><?php else: ?><span x-text="adoptions[selected].next_check_in_label"></span><?php endif; ?></td>
                <td class="px-4 py-3"><?php if ($rowAdoptions === []): ?><?= statusBadge('None') ?><?php else: ?><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold" :class="adoptions[selected].flag === 'None' ? 'bg-slate-100 text-slate-600' : (adoptions[selected].flag === 'WANTED' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700')" x-text="adoptions[selected].flag"></span><?php endif; ?></td>
                <td class="px-4 py-3 text-right"><a href="<?= site_url('admin/adopters/' . $a['id']) ?>" :href="adoptions.length ? adoptions[selected].manage_url : '<?= site_url('admin/adopters/' . $a['id']) ?>'" class="inline-flex items-center gap-1.5 rounded-lg border border-rescue-200 bg-rescue-50 px-3 py-2 text-xs font-semibold text-rescue-700 hover:bg-rescue-100"><i data-lucide="pencil" class="h-4 w-4"></i>Manage</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($adopters)): ?><div class="p-10 text-center text-sm text-slate-500">No adopter records yet.</div><?php endif; ?>
</div>
<?= $this->endSection() ?>
