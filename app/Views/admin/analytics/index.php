<?= $this->extend('admin/admin_layout') ?>
<?= $this->section('content') ?>

<?php
$analyticsHelp = static function (string $label, string $description): void {
    ?>
    <span class="analytics-help contents" x-data="{ open: false }"
          @click.outside="open = false" @keydown.escape="open = false">
        <button type="button" class="rounded px-1 text-sm font-semibold text-rescue-700 focus-visible:outline focus-visible:outline-2"
                aria-label="<?= esc('About ' . $label, 'attr') ?>" :aria-expanded="open" @click="open = !open">ⓘ</button>
        <span class="analytics-help-text text-sm font-normal leading-6 text-slate-600"
              :class="{ 'analytics-help-open': open }"><span class="analytics-help-inner"><span class="block pt-2"><?= esc($description) ?></span></span></span>
    </span>
    <?php
};
?>
<style>
    .analytics-help-text {
        display: grid;
        grid-template-rows: 0fr;
        opacity: 0;
        transition: grid-template-rows 340ms ease-in-out, opacity 260ms ease-in-out;
    }

    .analytics-help-inner {
        min-height: 0;
        overflow: hidden;
    }
    .analytics-help-open { grid-template-rows: 1fr; opacity: 1; }

    @media (hover: hover) and (pointer: fine) {
        .analytics-card:hover > h2 .analytics-help-text,
        .analytics-card dt:hover .analytics-help-text { grid-template-rows: 1fr; opacity: 1; }
    }

    @media (prefers-reduced-motion: reduce) {
        .analytics-help-text { transition: none; }
    }
</style>
<div class="mb-6">
    <h1 class="text-3xl font-bold tracking-[-.025em]">Analytics Dashboard</h1>
    <p class="mt-1 text-slate-500">Track adoptions, see what needs staff review, and check completed volunteer work using CatCare 360 records.</p>
</div>

<div class="grid items-start gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($summary as $item): ?>
        <section class="card analytics-card">
            <h2 class="text-sm font-semibold text-slate-700"><?= esc($item['label']) ?> <?php $analyticsHelp($item['label'], $item['description']); ?></h2>
            <p class="metric-value mt-1 text-3xl font-bold <?= esc($item['tone'], 'attr') ?>"><?= number_format($item['value']) ?></p>
            <a href="<?= site_url($item['path']) ?>" class="mt-3 inline-block text-sm font-semibold text-rescue-700 underline">View <?= esc($item['source']) ?></a>
        </section>
    <?php endforeach; ?>
</div>

<div class="mt-6 grid items-start gap-6 xl:grid-cols-2">
    <?php foreach ([
        ['title' => 'Application Status', 'description' => 'Counts adoption applications by their current status. Use this breakdown to see how many are awaiting review and how many have received a decision.', 'source' => 'Adopters', 'path' => 'admin/applications', 'rows' => $applicationBreakdown, 'empty' => 'No application records yet.'],
        ['title' => 'Monitoring Review Status', 'description' => 'Counts submitted post-adoption check-ins by review status. Use this breakdown to find updates that still need staff attention.', 'source' => 'Monitoring', 'path' => 'admin/monitoring', 'rows' => $monitoringBreakdown, 'empty' => 'No monitoring records yet.'],
    ] as $section): ?>
        <section class="card analytics-card">
            <h2 class="text-lg font-bold"><?= esc($section['title']) ?> <?php $analyticsHelp($section['title'], $section['description']); ?></h2>
            <a href="<?= site_url($section['path']) ?>" class="mt-2 inline-block text-sm font-semibold text-rescue-700 underline">View <?= esc($section['source']) ?></a>
            <div class="mt-5 space-y-3">
                <?php if ($section['rows'] === []): ?>
                    <p class="text-sm text-slate-500"><?= esc($section['empty']) ?></p>
                <?php else: ?>
                    <?php foreach ($section['rows'] as $item): ?>
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-4 py-3">
                            <span class="font-medium text-slate-700"><?= esc($item['status']) ?></span>
                            <span class="font-bold text-rescue-600"><?= number_format((int) $item['total']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="card analytics-card">
        <h2 class="text-lg font-bold">Adoption Trend <?php $analyticsHelp('Adoption Trend', 'Shows up to six recent months with recorded adoptions, grouped by adoption date across all statuses. Compare monthly totals to see when more adoptions were recorded.'); ?></h2>
        <a href="<?= site_url('admin/reports/preview/adoptions') ?>" class="mt-2 inline-block text-sm font-semibold text-rescue-700 underline">View Adoption Register</a>
        <div class="mt-5 space-y-3">
            <?php if ($monthlyAdoptions === []): ?>
                <p class="text-sm text-slate-500">No dated adoption records yet.</p>
            <?php else: ?>
                <?php foreach ($monthlyAdoptions as $item): ?>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <span class="text-sm font-medium text-slate-700"><?= esc($item['month']) ?></span>
                        <span class="rounded-full bg-rescue-50 px-3 py-1 text-sm font-bold text-rescue-700"><?= number_format($item['total']) ?> <?= $item['total'] === 1 ? 'adoption' : 'adoptions' ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="card analytics-card">
        <h2 class="text-lg font-bold">Assessment Overview <?php $analyticsHelp('Assessment Overview', 'Groups adoption applications by their weighted readiness score from the application assessment. Use the score range and welfare indicators to guide staff review before making a decision.'); ?></h2>
        <a href="<?= site_url('admin/applications') ?>" class="mt-2 inline-block text-sm font-semibold text-rescue-700 underline">View Application Assessments</a>
        <dl class="mt-5 grid grid-cols-3 gap-2 text-center sm:gap-3">
            <div class="rounded-xl bg-adopted-50 p-3 text-adopted-700"><dt class="text-xs font-semibold">80–100%</dt><dd class="mt-1 text-2xl font-bold"><?= number_format($assessment['strong']) ?></dd></div>
            <div class="rounded-xl bg-amber-50 p-3 text-amber-700"><dt class="text-xs font-semibold">60% to under 80%</dt><dd class="mt-1 text-2xl font-bold"><?= number_format($assessment['watch']) ?></dd></div>
            <div class="rounded-xl bg-red-50 p-3 text-red-700"><dt class="text-xs font-semibold">Below 60%</dt><dd class="mt-1 text-2xl font-bold"><?= number_format($assessment['risk']) ?></dd></div>
        </dl>
        <dl class="mt-5 space-y-3 text-sm text-slate-600">
            <div>
                <dt class="font-semibold text-slate-900">Average Readiness · <?= $assessment['average'] === null ? 'No scores yet' : number_format($assessment['average'], 2) . '%' ?> <?php $analyticsHelp('Average Readiness', 'Average of applications with a recorded readiness score.'); ?></dt>
            </div>
            <div>
                <dt class="font-semibold text-slate-900">Provisional · <?= number_format($assessment['provisional']) ?> <?php $analyticsHelp('Provisional', 'Applications without a recorded readiness score.'); ?></dt>
            </div>
            <div>
                <dt class="font-semibold text-slate-900">Welfare Concerns · <?= number_format($assessment['welfareConcerns']) ?> <?php $analyticsHelp('Welfare Concerns', 'Applications where the indoor living or humane housing check has a recorded result other than “Meets Requirements.” These can also appear in the score ranges above.'); ?></dt>
            </div>
        </dl>
    </section>
</div>
<?= $this->endSection() ?>
