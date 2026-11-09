<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<main class="public-cat-profile min-h-screen bg-slate-50 pt-16">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:py-14">
        <a href="<?= site_url('cats') ?>" class="public-back-link public-back-link-dark"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to All Cats</a>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1.08fr_.92fr]">
            <section>
                <div class="relative overflow-hidden rounded-3xl bg-slate-200 shadow-xl"><div class="aspect-[4/3]"><img src="<?= esc($cat['image_url'] ? base_url($cat['image_url']) : 'https://images.unsplash.com/photo-1574158622682-e40ad296ce3f?auto=format&fit=crop&w=1200&q=80') ?>" alt="<?= esc($cat['name']) ?>" class="h-full w-full object-cover"></div><span class="absolute left-5 top-5 rounded-full bg-white/95 px-3.5 py-2 text-xs font-bold text-rescue-800 shadow-sm backdrop-blur"><span class="mr-2 inline-block h-2 w-2 rounded-full bg-adopted-500"></span>Available for adoption</span></div>
                <?php if (! empty($galleryPhotos)): ?>
                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <?php foreach (array_slice($galleryPhotos, 0, 3) as $photo): ?><a href="<?= esc(base_url($photo['photo_url'])) ?>" target="_blank" rel="noopener noreferrer" class="group aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-100"><img src="<?= esc(base_url($photo['photo_url'])) ?>" alt="<?= esc($photo['caption'] ?: $cat['name'] . ' gallery photo') ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105"></a><?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="flex flex-col">
                <div><p class="text-sm font-bold text-rescue-700"><?= esc($cat['breed'] ?: 'Mixed breed') ?></p><h1 class="mt-2 text-4xl font-bold text-slate-900 sm:text-5xl"><?= esc($cat['name']) ?></h1><p class="mt-5 text-base leading-7 text-slate-600"><?= esc($cat['behavior_notes'] ?: $cat['name'] . ' is a community cat ready to meet a patient, responsible adopter.') ?></p></div>

                <dl class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">
                    <?php foreach ([['Age', $cat['age'] ?: '—'], ['Gender', $cat['gender'] ?: '—'], ['Color', $cat['color'] ?: '—'], ['Health', $cat['health'] ?: '—']] as [$label, $value]): ?><div class="bg-white p-4"><dt class="text-xs font-semibold text-slate-500"><?= esc($label) ?></dt><dd class="mt-1 text-sm font-bold text-slate-900"><?= esc($value) ?></dd></div><?php endforeach; ?>
                </dl>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex items-center justify-between gap-4"><div><h2 class="text-base font-bold text-slate-900">CNVR care record</h2><p class="mt-1 text-xs leading-5 text-slate-500">Community care steps recorded for <?= esc($cat['name']) ?>.</p></div><i data-lucide="shield-check" class="h-6 w-6 text-rescue-600"></i></div>
                    <div class="mt-4 grid grid-cols-4 gap-2"><?php foreach (['catch' => 'C', 'neuter' => 'N', 'vaccinate' => 'V', 'return' => 'R'] as $step => $label): ?><?php $complete = ! empty($cat['cnvr_' . $step]); ?><div class="rounded-xl px-2 py-3 text-center <?= $complete ? 'bg-adopted-50 text-adopted-700' : 'bg-slate-100 text-slate-400' ?>"><div class="text-lg font-bold"><?= esc($label) ?></div><div class="mt-1 text-[10px] font-semibold"><?= $complete ? 'Recorded' : 'Pending' ?></div></div><?php endforeach; ?></div>
                </div>

                <div class="mt-6 rounded-3xl bg-rescue-900 p-6 text-white sm:p-7">
                    <h2 class="text-xl font-bold text-white">Could <?= esc($cat['name']) ?> be your match?</h2>
                    <p class="mt-2 text-sm leading-6 text-rescue-100">Browsing is open to everyone. An adopter account is required only when you are ready to complete the application.</p>
                    <?php if (session('adopter_user')): ?>
                        <a href="<?= site_url('adopter/apply/' . $cat['id']) ?>" class="btn mt-5 w-full bg-white text-rescue-900 hover:bg-rescue-50"><i data-lucide="heart" class="h-4 w-4"></i>Apply to Adopt <?= esc($cat['name']) ?></a>
                    <?php else: ?>
                        <button type="button" @click="authOpen=true; authTab='signup'; signType='adopter'" class="btn mt-5 w-full bg-white text-rescue-900 hover:bg-rescue-50"><i data-lucide="user-plus" class="h-4 w-4"></i>Create Account to Apply</button>
                        <button type="button" @click="authOpen=true; authTab='signin'; signType='adopter'" class="mt-3 w-full text-center text-sm font-semibold text-rescue-100 hover:text-white">Already have an account? Sign in</button>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</main>
<?= $this->endSection() ?>
