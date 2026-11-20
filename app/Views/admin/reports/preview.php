<?= $this->extend('admin/admin_layout') ?>
<?= $this->section('content') ?>
<?php $previewUrl = site_url('admin/reports/preview/' . $type); ?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="<?= site_url('admin/reports') ?>" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-rescue-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to Reports</a>
        <h1 class="text-3xl font-bold tracking-[-.025em]"><?= esc($title) ?></h1>
        <p class="mt-1 text-sm text-slate-500"><?= number_format($count) ?> <?= $count === 1 ? 'record' : 'records' ?> · CSV export includes all pages.</p>
    </div>
    <a href="<?= site_url('admin/reports/export/' . $type) ?>" class="btn btn-primary"><i data-lucide="download" class="h-4 w-4"></i>Export CSV</a>
</div>
<div class="card">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm" aria-label="<?= esc($title, 'attr') ?> preview">
            <thead class="bg-slate-50 text-slate-700">
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold"><?= esc($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td class="px-4 py-3"><?= esc((string) $value) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= count($headers) ?>" class="px-4 py-6 text-center text-slate-500">No records to preview.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($lastPage > 1): ?>
        <nav aria-label="Report pages" class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4 text-sm">
            <?php if ($page > 1): ?><a class="btn" href="<?= esc($previewUrl . '?page=' . ($page - 1), 'attr') ?>" rel="prev">Previous</a><?php endif; ?>
            <span class="text-slate-600">Page <?= $page ?> of <?= $lastPage ?></span>
            <?php if ($page < $lastPage): ?><a class="btn" href="<?= esc($previewUrl . '?page=' . ($page + 1), 'attr') ?>" rel="next">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
