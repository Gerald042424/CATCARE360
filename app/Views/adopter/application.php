<?= $this->extend('layouts/adopter') ?>
<?= $this->section('adopter_content') ?>

<?php
$statusStyle = static function (string $status): array {
    return match ($status) {
        'Approved' => ['bg-adopted-50 text-adopted-700 ring-adopted-100', 'check-circle-2', 'Approved'],
        'Rejected' => ['bg-red-50 text-red-700 ring-red-100', 'x-circle', 'Not approved'],
        'Unavailable' => ['bg-amber-50 text-amber-800 ring-amber-100', 'circle-alert', 'Already adopted'],
        default => ['bg-violet-50 text-violet-700 ring-violet-100', 'search-check', 'Under review'],
    };
};
?>

<div class="mx-auto max-w-6xl px-6 py-10 sm:py-12">
    <a href="<?= site_url('adopter/home') ?>" class="mb-8 inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to Meet the Cats</a>
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="mb-2 text-xs font-bold uppercase tracking-[.16em] text-rescue-600">Adoption journey</p><h1 class="text-3xl font-semibold text-slate-900">My Applications</h1><p class="mt-2 text-sm font-medium text-slate-500">Select a cat to see the status and details of its adoption request.</p></div>
        <a href="<?= site_url('adopter/home') ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-rescue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-rescue-700"><i data-lucide="heart" class="h-4 w-4"></i>Browse Cats</a>
    </div>

    <?php if (! empty($appliedCats)): ?>
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-rescue-100 bg-rescue-50 px-4 py-3 text-sm text-rescue-800"><i data-lucide="file-heart" class="h-5 w-5 shrink-0 text-rescue-600"></i><span>You applied for <strong><?= count($appliedCats) ?></strong> <?= count($appliedCats) === 1 ? 'cat' : 'cats' ?>. Select a cat to track its individual status.</span></div>
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($appliedCats as $item): ?>
                <?php $application = $item['application']; $cat = $item['cat']; [$badge, $icon, $statusLabel] = $statusStyle($cat['status'] ?? 'Under Review'); $progressUrl = 'adopter/application/' . $application['id'] . (! empty($cat['id']) ? '/cat/' . $cat['id'] : ''); ?>
                <a href="<?= site_url($progressUrl) ?>" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-rescue-200 hover:shadow-lg">
                    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                        <img src="<?= esc($cat['image_url'] ? base_url($cat['image_url']) : 'https://images.unsplash.com/photo-1574158622682-e40ad296ce3f?auto=format&fit=crop&w=800&q=80') ?>" alt="<?= esc($cat['name'] ?: 'Cat') ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        <span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 <?= $badge ?>"><i data-lucide="<?= $icon ?>" class="h-3.5 w-3.5"></i><?= esc($statusLabel) ?></span>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-[.12em] text-slate-400"><?= esc($application['application_id'] ?: 'Application') ?></p><h2 class="mt-1 text-xl font-semibold text-slate-900"><?= esc($cat['name'] ?: 'Selected cat') ?></h2><p class="mt-1 text-sm font-medium text-rescue-600">Applied cat</p></div><i data-lucide="arrow-up-right" class="h-5 w-5 text-slate-300 transition group-hover:text-rescue-600"></i></div>
                        <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium text-slate-600"><?php if (! empty($cat['breed'])): ?><span class="rounded-full bg-slate-100 px-2.5 py-1"><?= esc($cat['breed']) ?></span><?php endif; ?><?php if (! empty($cat['age'])): ?><span class="rounded-full bg-slate-100 px-2.5 py-1"><?= esc($cat['age']) ?> old</span><?php endif; ?></div>
                        <div class="mt-5 border-t border-slate-100 pt-4 text-xs font-medium text-slate-500"><?php if (($cat['status'] ?? '') === 'Unavailable'): ?><div class="flex items-start gap-2 rounded-lg bg-amber-50 p-2.5 text-amber-800"><i data-lucide="info" class="h-4 w-4 shrink-0"></i><span>This cat was already adopted by another applicant.</span></div><?php else: ?><div class="flex items-center justify-between"><span class="flex items-center gap-2"><i data-lucide="calendar-days" class="h-4 w-4 text-rescue-600"></i>Applied <?= ! empty($application['submitted_at']) ? date('M d, Y', strtotime($application['submitted_at'])) : 'recently' ?></span><span class="font-semibold text-rescue-600">View progress</span></div><?php endif; ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm"><div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-rescue-50 text-rescue-600"><i data-lucide="heart" class="h-8 w-8"></i></div><h2 class="text-xl font-semibold text-slate-900">No applications yet</h2><p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">The cats you apply for adoption will appear here with their application status.</p><a href="<?= site_url('adopter/home') ?>" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-rescue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-rescue-700"><i data-lucide="search" class="h-4 w-4"></i>Meet the Cats</a></div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
