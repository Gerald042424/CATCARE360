<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<main class="grid min-h-screen place-items-center px-4 pt-16">
    <div class="card max-w-lg text-center"><div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-adopted-50 text-adopted-600"><i data-lucide="check-circle-2"></i></div><h1 class="text-3xl font-bold tracking-[-.025em]">Application Received</h1><p class="mt-3 text-slate-600">Your application is queued for staff review. Prepare your government ID, proof of address, home photos, and selfie with ID.</p><a class="btn btn-primary mt-6" href="<?= site_url('adopter/home') ?>">Back to Cats</a></div>
</main>
<?= $this->endSection() ?>
