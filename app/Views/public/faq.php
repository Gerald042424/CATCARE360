<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<?php
$faqs = [
    ['What is CatCare 360?', 'CatCare 360 is the digital adoption and volunteer management system used for McKinley West Cats. It organizes applications, document review, volunteer tasks, adoption decisions, and post-adoption monitoring.'],
    ['Is McKinley West Cats a physical animal shelter?', 'No. McKinley West Cats is a volunteer-led community initiative. The system supports cats under the community’s care, but it does not represent a physical shelter or walk-in facility.'],
    ['How do I apply to adopt a cat?', 'Create an adopter account, sign in, choose an available cat, and complete the application. You will provide the required answers and live-captured documents before staff review and a possible interview.'],
    ['Does the readiness score automatically approve or reject an applicant?', 'No. The score is a decision-support guide based on structured answers. Staff still review the full application, supporting documents, interview details, and the needs of the selected cat before making a final decision.'],
    ['What documents are required for an adoption application?', 'The application may require a supported government-issued ID, proof of address, home photos, and a live selfie. Automated checks are preliminary only; staff manually review submitted documents and identity details.'],
    ['Why does the system capture time and location with photos?', 'Time and GPS details help establish when and where a live photo was taken. Location readings depend on the device, browser permission, signal, and surrounding conditions, so staff use them as supporting information rather than absolute proof.'],
    ['What is 3-3-3 post-adoption monitoring?', 'It is a scheduled follow-up pattern around the first three days, three weeks, and three months after adoption. Adopters submit updates so the community team can follow the cat’s adjustment and identify concerns that may need attention.'],
    ['How can I become a volunteer?', 'Volunteer onboarding begins through the official McKinley West Cats Instagram page. Once accepted, staff create the volunteer profile used to receive tasks and submit work updates in CatCare 360.'],
    ['What should I do if a cat has an urgent welfare or medical concern?', 'Contact the McKinley West Cats community through its official social channel and seek appropriate veterinary or local animal-welfare assistance when urgent. CatCare 360 is a management tool and does not replace emergency or veterinary services.'],
    ['How is my information protected?', 'Access is role-based, private documents are not shown as public files, and account sessions are automatically ended after inactivity. Only submit accurate information that is necessary for your application or assigned task.'],
];
?>
<main class="public-info-page min-h-screen bg-slate-50 pt-16">
    <section class="border-b border-slate-200 bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-4xl px-4">
            <a href="<?= site_url('/') ?>" class="public-back-link public-back-link-dark"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to Home</a>
            <p class="public-eyebrow mt-10">Frequently asked questions</p>
            <h1 class="mt-3 text-4xl font-bold text-slate-900 sm:text-5xl">Clear answers before you begin.</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">Learn how adoption, volunteer work, document checks, and post-adoption monitoring work in CatCare 360.</p>
        </div>
    </section>
    <section class="mx-auto max-w-4xl px-4 py-12 sm:py-16">
        <div class="space-y-3">
            <?php foreach ($faqs as $index => [$question, $answer]): ?>
                <details class="public-faq group"<?= $index === 0 ? ' open' : '' ?>>
                    <summary><span><?= esc($question) ?></span><span class="public-faq-icon"><i data-lucide="plus" class="h-5 w-5"></i></span></summary>
                    <div class="public-faq-answer"><p><?= esc($answer) ?></p></div>
                </details>
            <?php endforeach; ?>
        </div>
        <div class="mt-10 flex flex-col items-start justify-between gap-5 rounded-2xl bg-rescue-900 p-7 text-white sm:flex-row sm:items-center sm:p-8">
            <div><h2 class="text-xl font-bold text-white">Still need help?</h2><p class="mt-2 text-sm leading-6 text-rescue-100">Use our verified contact channel for community inquiries.</p></div>
            <a href="<?= site_url('contact') ?>" class="btn shrink-0 bg-white text-rescue-800 hover:bg-rescue-50">Go to Contact Us</a>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
