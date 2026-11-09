<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<main class="public-info-page min-h-screen bg-slate-50 pt-16">
    <section class="relative overflow-hidden bg-slate-900 py-20 text-white sm:py-28">
        <img class="absolute inset-0 h-full w-full object-cover opacity-25" src="https://images.unsplash.com/photo-1495360010541-f48722b34f7d?auto=format&fit=crop&w=1800&q=80" alt="A community cat resting outdoors">
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/90 to-rescue-900/55"></div>
        <div class="relative mx-auto max-w-7xl px-4">
            <a href="<?= site_url('/') ?>" class="public-back-link"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to Home</a>
            <p class="mt-10 text-xs font-bold uppercase tracking-[.18em] text-rescue-300">About McKinley West Cats</p>
            <h1 class="mt-4 max-w-3xl text-4xl font-bold leading-tight text-white sm:text-6xl">Care that begins in the community.</h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">McKinley West Cats is a volunteer-led community caring for cats around McKinley West through consistent care, responsible adoption, and continued welfare support.</p>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-12 px-4 py-16 lg:grid-cols-[1.05fr_.95fr] lg:py-24">
        <div>
            <p class="public-eyebrow">Who we are</p>
            <h2 class="mt-3 text-3xl font-bold text-slate-900 sm:text-4xl">People looking out for community cats—together.</h2>
            <div class="mt-6 space-y-5 text-base leading-8 text-slate-600">
                <p>The group’s publicly shared work reflects a simple idea: regular feeding, attentive care, and responsible ownership can change a community cat’s life. Volunteers help cats receive day-to-day support while connecting suitable adopters with cats ready for a permanent home.</p>
                <p>CatCare 360 supports that work by keeping adoption applications, staff reviews, volunteer tasks, and post-adoption updates organized in one secure system. It assists human decision-making; it does not replace interviews, document review, or the judgment of the community team.</p>
                <p>McKinley West Cats is a community initiative, not a physical animal shelter. Care happens through volunteers, adopters, and people who choose to act responsibly for the cats around them.</p>
            </div>
        </div>
        <aside class="public-story-card">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rescue-50 text-rescue-700"><i data-lucide="heart-handshake"></i></span>
            <p class="mt-8 text-2xl font-bold leading-9 text-slate-900">“Every safe meal, careful decision, and responsible home adds up to a better life.”</p>
            <div class="mt-8 border-t border-slate-200 pt-6">
                <p class="font-bold text-slate-900">McKinley West Cats</p>
                <p class="mt-1 text-sm text-slate-500">Community care · Responsible adoption · Continued support</p>
            </div>
        </aside>
    </section>

    <section class="border-y border-slate-200 bg-white py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4">
            <div class="max-w-2xl"><p class="public-eyebrow">How care continues</p><h2 class="mt-3 text-3xl font-bold text-slate-900">A practical circle of care</h2></div>
            <div class="mt-10 grid gap-px overflow-hidden rounded-3xl border border-slate-200 bg-slate-200 md:grid-cols-2 lg:grid-cols-4">
                <?php foreach ([
                    ['utensils', 'Daily care', 'Volunteers help feed, observe, and support cats in the community.'],
                    ['stethoscope', 'Welfare support', 'Health concerns are documented and elevated for appropriate attention.'],
                    ['home', 'Responsible adoption', 'Applicants complete requirements and receive a human staff review.'],
                    ['clipboard-check', 'Post-adoption care', 'Scheduled updates help the team follow each adopted cat’s adjustment.'],
                ] as [$icon, $heading, $copy]): ?>
                    <article class="bg-white p-7 sm:p-8"><i data-lucide="<?= esc($icon) ?>" class="h-6 w-6 text-rescue-600"></i><h3 class="mt-6 text-lg font-bold text-slate-900"><?= esc($heading) ?></h3><p class="mt-3 text-sm leading-6 text-slate-600"><?= esc($copy) ?></p></article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="bg-rescue-900 py-16 text-white">
        <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-8 px-4 md:flex-row md:items-center">
            <div><p class="text-sm font-bold uppercase tracking-[.16em] text-rescue-300">Be part of the work</p><h2 class="mt-3 text-3xl font-bold text-white">A safer future starts with one responsible action.</h2></div>
            <div class="flex flex-wrap gap-3"><a href="<?= site_url('/') ?>#home" class="btn bg-white text-rescue-800 hover:bg-rescue-50">Explore Adoption</a><a href="<?= site_url('contact') ?>" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">Contact Us</a></div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
