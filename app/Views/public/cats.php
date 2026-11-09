<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<main class="public-cats-page min-h-screen bg-slate-50 pt-16">
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-14 lg:grid-cols-[1fr_auto] lg:items-end lg:py-20">
            <div class="max-w-3xl">
                <a href="<?= site_url('/') ?>" class="public-back-link public-back-link-dark"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to Home</a>
                <p class="mt-10 text-sm font-bold text-rescue-700">McKinley West Cats</p>
                <h1 class="mt-3 text-4xl font-bold leading-tight text-slate-900 sm:text-6xl">Meet the cats waiting for a home.</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">Explore their profiles without creating an account. When one feels like the right match, sign in or create an adopter account to apply.</p>
            </div>
            <div class="public-cat-count"><strong><?= count($cats) ?></strong><span><?= count($cats) === 1 ? 'cat is' : 'cats are' ?><br>currently available</span></div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 sm:py-16">
        <?php if (! empty($cats)): ?>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($cats as $cat): ?>
                    <article class="public-cat-card group flex flex-col">
                        <a href="<?= site_url('cats/' . $cat['id']) ?>" class="relative block aspect-[4/3] overflow-hidden bg-slate-100" aria-label="View <?= esc($cat['name']) ?>'s profile">
                            <img src="<?= esc($cat['image_url'] ? base_url($cat['image_url']) : 'https://images.unsplash.com/photo-1574158622682-e40ad296ce3f?auto=format&fit=crop&w=900&q=80') ?>" alt="<?= esc($cat['name']) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.035]">
                            <span class="absolute left-4 top-4 rounded-full bg-white/95 px-3 py-1.5 text-xs font-bold text-rescue-800 shadow-sm backdrop-blur">Available</span>
                        </a>
                        <div class="flex flex-1 flex-col p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-4"><div><h2 class="text-2xl font-bold text-slate-900"><?= esc($cat['name']) ?></h2><p class="mt-1 text-sm text-slate-500"><?= esc($cat['breed'] ?: 'Mixed breed') ?></p></div><span class="rounded-lg bg-rescue-50 px-2.5 py-1 text-xs font-bold text-rescue-700"><?= esc($cat['age'] ?: 'Age not listed') ?></span></div>
                            <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold text-slate-600"><span class="rounded-full bg-slate-100 px-3 py-1.5"><?= esc($cat['gender'] ?: 'Gender not listed') ?></span><span class="rounded-full bg-slate-100 px-3 py-1.5"><?= esc($cat['color'] ?: 'Color not listed') ?></span><?php if (! empty($cat['health'])): ?><span class="rounded-full bg-adopted-50 px-3 py-1.5 text-adopted-700"><?= esc($cat['health']) ?></span><?php endif; ?></div>
                            <p class="mt-5 line-clamp-3 flex-1 text-sm leading-6 text-slate-600"><?= esc($cat['behavior_notes'] ?: 'A community cat ready to meet a responsible adopter.') ?></p>
                            <a href="<?= site_url('cats/' . $cat['id']) ?>" class="mt-6 inline-flex items-center justify-between border-t border-slate-100 pt-4 text-sm font-bold text-rescue-700"><span>View <?= esc($cat['name']) ?>'s profile</span><i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="mx-auto max-w-xl rounded-3xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rescue-50 text-rescue-700"><i data-lucide="cat" class="h-7 w-7"></i></span><h2 class="mt-6 text-2xl font-bold text-slate-900">No cats are listed right now.</h2><p class="mt-3 leading-7 text-slate-600">Availability changes as cats receive care and find homes. Please check again soon or follow the community on Instagram for updates.</p><a href="https://www.instagram.com/mckwestpusa/" target="_blank" rel="noopener noreferrer" class="btn btn-ghost mt-6">Open Instagram<i data-lucide="external-link" class="h-4 w-4"></i></a></div>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>
