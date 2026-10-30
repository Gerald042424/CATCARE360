<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'CatCare') ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('catcare-favicon-v3-32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('catcare-favicon-v3-16.png') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= base_url('catcare-favicon-v3-32.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('catcare-apple-touch-v3.png') ?>">
    <link rel="apple-touch-icon-precomposed" sizes="180x180" href="<?= base_url('catcare-apple-touch-v3.png') ?>">
    <link rel="manifest" href="<?= base_url('site.webmanifest') ?>?v=3">
    <meta name="apple-mobile-web-app-title" content="CatCare 360">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { rescue: { 50: '#EFF8FC', 100: '#D4EDF7', 200: '#A9DBEF', 300: '#6DC3E3', 400: '#30A8D2', 500: '#0D7EA3', 600: '#0A6A8A', 700: '#085670', 800: '#064257', 900: '#03293A' }, catgold: { 50: '#FFFBEB', 100: '#FEF3C7', 200: '#FDE68A', 300: '#FCD34D', 400: '#FBBF24', 500: '#F59E0B', 600: '#D97706', 700: '#B45309', 800: '#92400E', 900: '#78350F' }, adopted: { 50: '#F0FDF4', 100: '#DCFCE7', 200: '#BBF7D0', 300: '#86EFAC', 400: '#4ADE80', 500: '#22C55E', 600: '#16A34A', 700: '#15803D', 800: '#166534', 900: '#14532D' }, wanted: { 50: '#FEF2F2', 100: '#FEE2E2', 200: '#FECACA', 300: '#FCA5A5', 400: '#F87171', 500: '#EF4444', 600: '#DC2626', 700: '#B91C1C', 800: '#991B1B', 900: '#7F1D1D' }, foster: { 50: '#FAF5FF', 100: '#F3E8FF', 200: '#E9D5FF', 300: '#D8B4FE', 400: '#C084FC', 500: '#A855F7', 600: '#9333EA', 700: '#7E22CE', 800: '#6B21A8', 900: '#581C87' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons())</script>
    <style>[x-cloak]{display:none!important}body{font-family:Inter,sans-serif;background:#F5F7FA}h1,h2,h3,.brand,.metric-value{font-family:'Plus Jakarta Sans',Inter,sans-serif;letter-spacing:-.02em}button,input,select,textarea{font-family:inherit}</style>
</head>
<body class="min-h-screen">

<?php $profileSetupRequired = (bool) session('adopter_profile_setup_required'); ?>
<div x-data="{ showProfile: <?= $profileSetupRequired ? 'true' : 'false' ?>, mobileMenu: false, profileRequired: <?= $profileSetupRequired ? 'true' : 'false' ?> }" @keydown.escape.window="if (!profileRequired) showProfile = false; mobileMenu = false">
    <header class="sticky top-0 z-50 bg-white border-b border-slate-200 h-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between h-full">
            <a href="<?= site_url('adopter/home') ?>" class="flex items-center gap-3">
                <div class="w-8 h-8 bg-rescue-600 rounded-lg flex items-center justify-center text-white shadow-sm"><i data-lucide="paw-print" class="w-4 h-4"></i></div>
                <span class="brand font-semibold text-lg tracking-tight text-slate-900">CatCare</span>
            </a>

            <nav class="hidden md:flex items-center gap-6">
                <a href="<?= site_url('adopter/home') ?>" class="font-medium text-sm <?= uri_string()==='adopter/home' ? 'text-rescue-600' : 'text-slate-600 hover:text-slate-900' ?>">Meet the Cats</a>
                <?php if (session('adopter_user')): ?>
                <a href="<?= site_url('adopter/application') ?>" class="font-medium text-sm <?= strpos(uri_string(),'adopter/application')!==false ? 'text-rescue-600' : 'text-slate-600 hover:text-slate-900' ?>">My Application</a>
                <a href="<?= site_url('adopter/portal') ?>" class="font-medium text-sm <?= strpos(uri_string(),'adopter/portal')!==false ? 'text-rescue-600' : 'text-slate-600 hover:text-slate-900' ?>">My Adoption</a>
                <?php endif; ?>
                <div class="h-4 w-px bg-slate-300 mx-2"></div>
            </nav>

            <div class="flex items-center gap-2 sm:gap-4">
                <?php if (session('adopter_user')): ?>
                <?php $adopterUser = session('adopter_user') ?? []; $displayName = isset($adopterUser['name']) ? $adopterUser['name'] : trim((isset($adopterUser['first_name']) ? $adopterUser['first_name'] : '') . ' ' . (isset($adopterUser['last_name']) ? $adopterUser['last_name'] : '')); $displayName = $displayName ?: 'Adopter'; $initial = strtoupper(substr($displayName,0,1)); ?>
                <button @click="showProfile = true" class="flex items-center gap-2 hover:bg-slate-50 px-2 py-1 rounded-full transition-colors">
                    <div class="w-8 h-8 rounded-full overflow-hidden border border-slate-200 shrink-0 bg-rescue-600 text-white flex items-center justify-center text-xs font-bold">
                        <?= esc($initial) ?>
                    </div>
                    <span class="hidden text-sm font-medium text-slate-700 sm:inline"><?= esc($displayName) ?></span>
                </button>
                <a href="<?= site_url('adopter/logout') ?>" class="flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-red-600 transition-colors ml-2"><i data-lucide="log-out" class="w-4 h-4"></i><span class="hidden sm:inline">Logout</span></a>
                <?php else: ?>
                <?php $displayName = 'Guest'; $initial = 'G'; ?>
                <a href="<?= site_url('/') ?>" class="text-sm font-medium text-slate-500 hover:text-slate-900">Home</a>
                <a href="<?= site_url('adopter/register') ?>" class="rounded-lg bg-rescue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rescue-700 sm:px-4"><span class="hidden sm:inline">Create Account</span><i data-lucide="user-plus" class="h-4 w-4 sm:hidden"></i></a>
                <?php endif; ?>
                <button type="button" @click="mobileMenu = !mobileMenu" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Toggle navigation"><i data-lucide="menu" class="h-5 w-5"></i></button>
            </div>
        </div>
        <nav x-cloak x-show="mobileMenu" x-transition class="absolute inset-x-0 top-16 border-b border-slate-200 bg-white p-3 shadow-lg md:hidden">
            <a @click="mobileMenu=false" href="<?= site_url('adopter/home') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-rescue-50">Meet the Cats</a>
            <?php if (session('adopter_user')): ?><a @click="mobileMenu=false" href="<?= site_url('adopter/application') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-rescue-50">My Application</a><a @click="mobileMenu=false" href="<?= site_url('adopter/portal') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-rescue-50">My Adoption</a><?php endif; ?>
        </nav>
    </header>

    <?= view('components/flash_message', ['top' => '5rem']) ?>

    <main class="min-h-[calc(100vh-4rem)]">
        <?= $this->renderSection('adopter_content') ?>
    </main>

    <footer class="bg-white border-t border-slate-200 py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col md:flex-row justify-between items-center gap-4 text-center md:text-left">
            <div class="flex items-center gap-2 text-slate-500"><i data-lucide="paw-print" class="w-5 h-5"></i><span class="font-semibold text-sm">CatCare Adoption Center</span></div>
            <div class="text-sm text-slate-400">© 2026 CatCare 360. All rights reserved.</div>
        </div>
    </footer>

    <?php if (session('adopter_user')): ?>
    <?php $profileAdopter = (new \App\Models\AdopterModel())->find((int) (session('adopter_user.adopter_id') ?? 0)) ?? []; $adoptedCatsCount = (new \App\Models\AdoptionModel())->where('adopter_id', (int) (session('adopter_user.adopter_id') ?? 0))->where('status', 'Active')->countAllResults(); ?>
    <!-- Profile Modal -->
    <div x-cloak x-show="showProfile" @click.self="if (!profileRequired) showProfile = false" x-transition class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div><h2 class="text-xl font-semibold text-slate-900">My Adopter Profile</h2><?php if ($profileSetupRequired): ?><p class="mt-1 text-sm text-rescue-700">Complete your phone number and address to continue.</p><?php endif; ?></div>
                <button x-show="!profileRequired" @click="showProfile = false" class="p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 rounded-full"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6 overflow-y-auto">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="md:w-1/3 flex flex-col items-center text-center">
                            <div class="w-32 h-32 rounded-full overflow-hidden bg-slate-100 border-4 border-white shadow-sm mb-4 flex items-center justify-center bg-rescue-600 text-white text-5xl font-bold"><?= esc($initial) ?></div>
                            <h3 class="font-bold text-slate-900 text-2xl"><?= esc($displayName) ?></h3>
                            <p class="text-sm font-medium text-rescue-600 mb-1">Approved Adopter</p>
                            <p class="text-xs text-slate-500 mb-6">ID: ADO-<?= str_pad(isset($adopterUser['id']) ? $adopterUser['id'] : 0,5,'0',STR_PAD_LEFT) ?></p>

                            <div class="w-full bg-rescue-50/50 rounded-xl p-4 text-left border border-rescue-100/50 space-y-3">
                                <div class="flex items-center gap-2 text-sm"><i data-lucide="calendar" class="w-4 h-4 text-rescue-600"></i><div><p class="text-xs text-slate-500">Joined</p><p class="font-medium text-slate-900"><?= date('F Y', strtotime(isset($adopterUser['created_at']) ? $adopterUser['created_at'] : date('Y-m-d'))) ?></p></div></div>
                                <div class="flex items-center gap-2 text-sm"><i data-lucide="shield-check" class="w-4 h-4 text-adopted-600"></i><div><p class="text-xs text-slate-500">Profile Status</p><p class="font-medium text-adopted-600">Verified</p></div></div>
                                <div class="flex items-center gap-2 text-sm"><i data-lucide="heart" class="w-4 h-4 text-red-600"></i><div><p class="text-xs text-slate-500">Cats Adopted</p><p class="font-medium text-slate-900"><?= esc($adoptedCatsCount) ?></p></div></div>
                            </div>
                        </div>

                    <div class="md:w-2/3 space-y-6">
                        <form method="post" action="<?= site_url('adopter/profile') ?>">
                            <?= csrf_field() ?><h4 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2 mb-3">Contact Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2"><label class="text-xs text-slate-500 mb-1 flex items-center gap-1.5 font-medium"><i data-lucide="mail" class="w-4 h-4"></i>Email</label><p class="text-sm font-medium text-slate-900"><?= esc($profileAdopter['email'] ?? $adopterUser['email'] ?? '') ?></p></div>
                                <div><label class="text-xs text-slate-500 mb-1 flex items-center gap-1.5 font-medium"><i data-lucide="phone" class="w-4 h-4"></i>Phone</label><input required name="phone" value="<?= esc($profileAdopter['phone'] ?? '') ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-rescue-500 focus:outline-none"></div>
                                <div class="md:col-span-2"><label class="text-xs text-slate-500 mb-1 flex items-center gap-1.5 font-medium"><i data-lucide="map-pin" class="w-4 h-4"></i>Complete Address</label><textarea required name="address" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-rescue-500 focus:outline-none" placeholder="House/unit, street, barangay, city, province"><?= esc($profileAdopter['address'] ?? '') ?></textarea><p class="mt-1 text-xs text-slate-500">This address is printed on new live monitoring captures.</p></div>
                            </div><div class="mt-4 flex justify-end"><button class="rounded-lg bg-rescue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rescue-700">Save contact details</button></div>
                        </form>

                        <div class="p-4 bg-adopted-50 border border-adopted-100 rounded-lg">
                            <div class="flex items-start gap-3"><i data-lucide="check-circle-2" class="w-5 h-5 text-adopted-600"></i><div><p class="text-sm font-semibold text-adopted-900">Account Verified</p><p class="text-xs text-adopted-700 mt-0.5">Your profile is active and you can start browsing and applying for cats.</p></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div x-show="!profileRequired" class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end"><button @click="showProfile = false" class="px-5 py-2.5 bg-rescue-600 text-white rounded-lg text-sm font-medium hover:bg-rescue-700 transition-colors shadow-sm">Done</button></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>lucide.createIcons();</script>
<?php if (session('adopter_user')): ?>
<?= view('components/session_timeout', [
    'idleSeconds' => config('AuthSession')->adopterIdleSeconds,
    'warningSeconds' => config('AuthSession')->warningSeconds,
    'absoluteSeconds' => config('AuthSession')->absoluteSeconds,
    'keepAliveUrl' => site_url('adopter/session/keep-alive'),
    'logoutUrl' => site_url('adopter/logout'),
]) ?>
<?php endif; ?>
</body>
</html>
