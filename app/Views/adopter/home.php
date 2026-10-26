<?= $this->extend('layouts/adopter') ?>
<?= $this->section('adopter_content') ?>

<section class="relative bg-rescue-600 text-white overflow-hidden">
    <img class="absolute inset-0 opacity-20 object-cover w-full h-full" src="https://images.unsplash.com/photo-1570824104453-508955ab713e?auto=format&fit=crop&w=1800&q=80" alt="Cat shelter">
    <div class="absolute inset-0 bg-gradient-to-r from-rescue-900/80 to-rescue-600/40"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-24 md:py-32">
        <div class="max-w-2xl">
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-semibold tracking-tight mb-6 leading-tight">Find your new best friend today.</h1>
            <p class="text-lg md:text-xl text-rescue-50 mb-10 font-medium">We have dozens of loving cats waiting for a forever home. Start your adoption journey with CatCare.</p>
            <div class="flex gap-4 flex-wrap">
                <a href="#adoptable" class="bg-white text-rescue-600 px-8 py-3.5 rounded-lg font-semibold shadow-lg hover:bg-slate-50 transition-colors">View Cats</a>
                <a href="<?= site_url('adopter/application') ?>" class="bg-rescue-700/50 backdrop-blur-sm border border-rescue-400 text-white px-8 py-3.5 rounded-lg font-semibold hover:bg-rescue-700/70 transition-colors">Check Status</a>
            </div>
        </div>
    </div>
</section>

<section id="adoptable" class="py-14 sm:py-20 max-w-7xl mx-auto px-4 sm:px-6">
    <div class="mb-10 flex items-end justify-between">
        <div>
            <h2 class="text-3xl font-semibold text-slate-900 mb-2">Available for Adoption</h2>
            <p class="text-slate-500 font-medium">Meet the wonderful cats currently in our care.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (!empty($cats)): foreach ($cats as $cat): ?>
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow group flex flex-col">
            <a href="<?= site_url('adopter/cats/' . $cat['id']) ?>" class="aspect-[4/3] w-full relative overflow-hidden bg-slate-100 cursor-pointer group block">
                <img src="<?= esc($cat['image_url'] ? base_url($cat['image_url']) : 'https://images.unsplash.com/photo-1574158622682-e40ad296ce3f?auto=format&fit=crop&w=600&q=80') ?>" alt="<?= esc($cat['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-full text-xs font-semibold text-slate-900 flex items-center gap-1.5 shadow-sm"><i data-lucide="heart" class="w-4 h-4 text-red-600"></i>Available</div>
                <div class="absolute inset-0 bg-rescue-600/0 group-hover:bg-rescue-600/10 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100"><div class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-semibold text-slate-900">Click to view full profile</div></div>
            </a>
            <div class="p-6 flex-1 flex flex-col">
                <div class="flex justify-between items-start mb-1"><h3 class="text-2xl font-semibold text-slate-900"><?= esc($cat['name']) ?></h3><span class="text-rescue-600 font-medium bg-rescue-50 px-2.5 py-1 rounded-md text-sm"><?= esc($cat['age'] ?? 'Unknown') ?></span></div>
                <p class="text-sm font-medium text-slate-500 mb-3"><?= esc($cat['breed'] ?? 'Mixed Breed') ?></p>
                <div class="flex items-center gap-2 mb-4 flex-wrap">
                    <?php $gender = $cat['gender'] ?? 'Unknown'; $genderIcon = $gender==='Male' ? 'mars' : ($gender==='Female' ? 'venus' : 'help-circle'); ?>
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-rescue-700 bg-rescue-50 border border-rescue-100 px-2 py-0.5 rounded-full"><i data-lucide="<?= $genderIcon ?>" class="w-3 h-3"></i><?= esc($gender) ?></span>
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full"><i data-lucide="heart-pulse" class="w-3 h-3"></i><?= esc($cat['health'] ?? 'Unknown') ?></span>
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full"><i data-lucide="weight" class="w-3 h-3"></i><?= esc($cat['weight'] ?? 'N/A') ?></span>
                </div>
                <p class="text-slate-600 text-sm leading-relaxed mb-6 flex-1"><?= esc($cat['behavior_notes'] ?: 'A wonderful cat waiting for adoption.') ?></p>
                <div class="grid grid-cols-2 gap-3"><a href="<?= site_url('adopter/cats/' . $cat['id']) ?>" class="bg-slate-100 text-slate-700 py-3 rounded-xl font-medium text-sm hover:bg-slate-200 transition-colors flex items-center justify-center gap-2"><i data-lucide="eye" class="w-4 h-4"></i>View Profile</a><a href="<?= site_url('adopter/apply/' . $cat['id']) ?>" class="bg-rescue-50 text-rescue-700 py-3 rounded-xl font-medium text-sm hover:bg-rescue-100 transition-colors flex items-center justify-center gap-2"><i data-lucide="arrow-right" class="w-4 h-4"></i>Apply</a></div>
            </div>
        </div>
        <?php endforeach; else: ?>
        <div class="col-span-full text-center py-12"><i data-lucide="inbox" class="w-16 h-16 text-slate-300 mx-auto mb-4"></i><p class="text-slate-500 font-medium">No cats available for adoption at the moment.</p><p class="text-slate-400 text-sm mt-1">Check back soon!</p></div>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
