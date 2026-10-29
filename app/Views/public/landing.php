<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>
<section id="home" class="relative min-h-screen bg-slate-900 pt-16">
    <img class="absolute inset-0 h-full w-full object-cover opacity-45" src="https://images.unsplash.com/photo-1570824104453-508955ab713e?auto=format&fit=crop&w=1800&q=80" alt="Cat shelter">
    <div class="absolute inset-0 bg-gradient-to-r from-slate-900/90 via-slate-900/65 to-slate-900/20"></div>
    <div class="relative mx-auto flex min-h-[calc(100vh-4rem)] max-w-7xl flex-col justify-center px-4 pb-16">
        <div class="mb-6 inline-flex w-fit items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur"><span class="h-2 w-2 animate-pulse rounded-full bg-adopted-400"></span>McKinley West Cats - Open for Adoption</span></div>
        <h1 class="max-w-4xl text-5xl font-bold tracking-[-.025em] text-white md:text-7xl"><span class="block">Give a Cat a</span><span class="mt-1 block text-rescue-400 md:mt-2">Forever Home.</span></h1>
        <p class="mt-6 max-w-xl text-xl leading-8 text-slate-200">A secure adoption and volunteer management system for tracking rescue cats, rule-based applications, 3-3-3 compliance, and community care.</p>
        <div class="mt-8 flex flex-wrap gap-3"><a href="#" @click.prevent="authOpen=true; authTab='signup'; signType='adopter'" class="btn btn-primary"><i data-lucide="heart" class="h-4 w-4"></i>Adopt a Cat</a><a href="#volunteer" class="btn border border-white/20 bg-white/15 text-white hover:bg-white/20">Become a Volunteer</a></div>
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 animate-bounce text-center text-xs font-bold tracking-wider text-white/70"><i data-lucide="chevron-down" class="mx-auto h-5 w-5"></i>SCROLL</div>
    </div>
</section>
<section class="bg-rescue-600 py-10" style="background-color:#0A6A8A">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 text-center md:grid-cols-4">
        <div><div class="text-4xl font-bold text-white"><?= esc($stats['cats'] ?: 312) ?>+</div><div class="text-sm font-medium text-rescue-100" style="color:#D4EDF7">Cats Intake</div></div>
        <div><div class="text-4xl font-bold text-white"><?= esc($stats['adoptions'] ?: 247) ?></div><div class="text-sm font-medium text-rescue-100" style="color:#D4EDF7">Successful Adoptions</div></div>
        <div><div class="text-4xl font-bold text-white"><?= esc($stats['volunteers'] ?: 42) ?>+</div><div class="text-sm font-medium text-rescue-100" style="color:#D4EDF7">Active Volunteers</div></div>
        <div><div class="text-4xl font-bold text-white"><?= esc($stats['compliance'] ?: 91) ?>%</div><div class="text-sm font-medium text-rescue-100" style="color:#D4EDF7">Compliance Rate</div></div>
    </div>
</section>
<section id="rules" class="bg-slate-50 py-24">
    <div class="mx-auto max-w-7xl px-4">
        <div class="mx-auto max-w-3xl text-center"><span class="rounded-full bg-rescue-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-rescue-700">Rules & Regulations</span><h2 class="mt-4 text-4xl font-bold tracking-[-.025em] text-slate-900">Adoption is loving, but it must be responsible.</h2><p class="mt-4 text-slate-600">Every approval is rule-based, document-backed, and reviewed by staff.</p></div>
        <div class="mt-12 grid gap-6 md:grid-cols-2">
            <div class="card">
                <div class="mb-6 flex items-center gap-3"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-rescue-50 text-rescue-600"><i data-lucide="heart"></i></span><h3 class="text-xl font-bold">Adoption Requirements</h3></div>
                <?php foreach (['Valid government ID', 'Proof of address', 'Home photos', 'Selfie with ID', 'Commitment to 3-3-3 monitoring', 'No re-homing without shelter approval'] as $row): ?>
                    <div class="flex gap-3 rounded-xl p-3 hover:bg-slate-50"><i data-lucide="check-circle-2" class="h-5 w-5 text-adopted-500"></i><div><div class="font-semibold text-slate-900"><?= esc($row) ?></div><p class="text-sm text-slate-500">Required before staff can finalize an adoption approval.</p></div></div>
                <?php endforeach; ?>
            </div>
            <div class="space-y-6">
                <div class="card">
                    <div class="mb-6 flex items-center gap-3"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-adopted-50 text-adopted-600"><i data-lucide="shield"></i></span><h3 class="text-xl font-bold">Shelter Policies</h3></div>
                    <?php foreach (['Respect cats and volunteers', 'Follow scheduled handoffs', 'Submit truthful documents', 'Allow check-ins when requested', 'Report health concerns quickly', 'Protect adopter and shelter privacy'] as $row): ?>
                        <div class="mb-3 flex items-center gap-3 text-sm font-medium text-slate-700"><i data-lucide="check-circle-2" class="h-4 w-4 text-adopted-500"></i><?= esc($row) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800"><div class="mb-2 flex items-center gap-2 font-bold"><i data-lucide="alert-circle" class="h-5 w-5"></i>Important Notice</div>Providing false information may result in rejection, WANTED/MISSING tagging, and BAI reporting.</div>
            </div>
        </div>
    </div>
</section>
<section id="volunteer" class="bg-white py-24">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 md:grid-cols-2">
        <div class="relative"><img class="aspect-[4/5] w-full rounded-3xl object-cover shadow-xl" src="https://images.unsplash.com/photo-1596854407944-bf87f6fdd49e?auto=format&fit=crop&w=1200&q=80" alt="Volunteer with cat"><div class="absolute bottom-6 left-6 rounded-2xl bg-white p-4 shadow-xl"><div class="flex items-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rescue-600 text-white"><i data-lucide="users"></i></span><div><div class="text-xl font-bold">42+ Active</div><div class="text-sm text-slate-500">Volunteers</div></div></div></div></div>
        <div class="flex flex-col justify-center"><span class="w-fit rounded-full bg-rescue-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-rescue-600">Volunteer Section</span><h2 class="mt-4 text-4xl font-bold tracking-[-.025em]">Help feed, transport, clean, and socialize cats.</h2><p class="mt-5 text-lg leading-8 text-slate-600">Volunteer onboarding happens externally through McKinley West Cats' Instagram. Once approved, staff can add your volunteer profile and assign tasks here.</p>
            <div class="mt-6 space-y-4"><?php foreach (['Task scheduling and reminders', 'Cat-linked medical notes for task context', 'Before/after reports for staff review'] as $row): ?><div class="flex items-center gap-3 font-semibold text-slate-700"><i data-lucide="check-circle-2" class="h-5 w-5 text-rescue-600"></i><?= esc($row) ?></div><?php endforeach; ?></div>
            <div class="mt-8 rounded-2xl border border-rescue-100 bg-rescue-50 p-5"><div class="font-bold text-rescue-900">DM us on Instagram</div><p class="mt-1 text-sm text-rescue-700">New volunteers must message @mckwestpusa. There is no in-system volunteer application form.</p><a class="btn mt-4 bg-rescue-600 text-white" href="https://www.instagram.com/mckwestpusa/">Open Instagram</a></div>
        </div>
    </div>
</section>
<section class="relative bg-slate-900 py-20 text-center text-white"><img class="absolute inset-0 h-full w-full object-cover opacity-10" src="https://images.unsplash.com/photo-1494256997604-768d1f608cac?auto=format&fit=crop&w=1600&q=80" alt=""><div class="relative mx-auto max-w-3xl px-4"><h2 class="text-4xl font-bold tracking-[-.025em]">Ready to make room for one more life?</h2><p class="mt-4 text-slate-400">Browse available cats or help the shelter through volunteer work.</p><div class="mt-8 flex justify-center gap-3"><a href="#" @click.prevent="authOpen=true; authTab='signup'; signType='adopter'" class="btn btn-primary">Adopt a Cat</a><a href="#volunteer" class="btn border border-white/20 bg-white/10 text-white">Volunteer With Us</a></div></div></section>
<?= $this->endSection() ?>
