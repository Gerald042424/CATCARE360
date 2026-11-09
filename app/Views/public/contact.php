<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<main class="public-info-page min-h-screen bg-slate-50 pt-16">
    <section class="border-b border-slate-200 bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4">
            <a href="<?= site_url('/') ?>" class="public-back-link public-back-link-dark"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to Home</a>
            <div class="mt-10 grid items-end gap-6 lg:grid-cols-[1fr_.7fr]">
                <div><p class="public-eyebrow">Contact us</p><h1 class="mt-3 max-w-3xl text-4xl font-bold text-slate-900 sm:text-5xl">Start with the right channel.</h1></div>
                <p class="text-base leading-7 text-slate-600">For general inquiries, community updates, and volunteer onboarding, message the verified McKinley West Cats Instagram account.</p>
            </div>
        </div>
    </section>
    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-14 lg:grid-cols-[1.1fr_.9fr] lg:py-20">
        <article class="overflow-hidden rounded-3xl bg-gradient-to-br from-rescue-800 to-rescue-950 p-8 text-white shadow-xl sm:p-10">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10"><i data-lucide="instagram" class="h-7 w-7"></i></div>
            <p class="mt-10 text-xs font-bold uppercase tracking-[.16em] text-rescue-200">Verified social channel</p>
            <h2 class="mt-3 text-3xl font-bold text-white">@mckwestpusa</h2>
            <p class="mt-4 max-w-xl leading-7 text-rescue-100">Send a direct message for volunteer inquiries, community concerns, or questions that are not covered in the FAQ. Please avoid sending sensitive identification documents through social media.</p>
            <a href="https://www.instagram.com/mckwestpusa/" target="_blank" rel="noopener noreferrer" class="btn mt-8 bg-white text-rescue-800 hover:bg-rescue-50">Open Instagram<i data-lucide="external-link" class="h-4 w-4"></i></a>
        </article>
        <div class="space-y-4">
            <a href="<?= site_url('/') ?>#home" class="public-contact-row"><span class="public-contact-icon"><i data-lucide="heart" class="h-5 w-5"></i></span><span><strong>Adoption</strong><small>Create or sign in to your adopter account to view cats and submit an application.</small></span><i data-lucide="arrow-up-right" class="ml-auto h-5 w-5 text-slate-400"></i></a>
            <a href="<?= site_url('/') ?>#volunteer" class="public-contact-row"><span class="public-contact-icon"><i data-lucide="hand-heart" class="h-5 w-5"></i></span><span><strong>Volunteer work</strong><small>Read the volunteer process before messaging the community on Instagram.</small></span><i data-lucide="arrow-up-right" class="ml-auto h-5 w-5 text-slate-400"></i></a>
            <a href="<?= site_url('faq') ?>" class="public-contact-row"><span class="public-contact-icon"><i data-lucide="circle-help" class="h-5 w-5"></i></span><span><strong>Questions about the system</strong><small>Check common answers about applications, verification, and monitoring.</small></span><i data-lucide="arrow-up-right" class="ml-auto h-5 w-5 text-slate-400"></i></a>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900"><div class="mb-2 flex items-center gap-2 font-bold"><i data-lucide="triangle-alert" class="h-5 w-5"></i>Urgent animal concern</div>CatCare 360 is not an emergency service. For an urgent medical or welfare situation, seek appropriate veterinary or local animal-welfare assistance while informing the community team.</div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
