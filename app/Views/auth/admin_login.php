<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Staff Login') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { rescue: { 50: '#EFF8FC', 100: '#D4EDF7', 200: '#A9DBEF', 300: '#6DC3E3', 400: '#30A8D2', 500: '#0D7EA3', 600: '#0A6A8A', 700: '#085670', 800: '#064257', 900: '#03293A' }, catgold: { 50: '#FFFBEB', 100: '#FEF3C7', 200: '#FDE68A', 300: '#FCD34D', 400: '#FBBF24', 500: '#F59E0B', 600: '#D97706', 700: '#B45309', 800: '#92400E', 900: '#78350F' }, adopted: { 50: '#F0FDF4', 100: '#DCFCE7', 200: '#BBF7D0', 300: '#86EFAC', 400: '#4ADE80', 500: '#22C55E', 600: '#16A34A', 700: '#15803D', 800: '#166534', 900: '#14532D' }, wanted: { 50: '#FEF2F2', 100: '#FEE2E2', 200: '#FECACA', 300: '#FCA5A5', 400: '#F87171', 500: '#EF4444', 600: '#DC2626', 700: '#B91C1C', 800: '#991B1B', 900: '#7F1D1D' }, foster: { 50: '#FAF5FF', 100: '#F3E8FF', 200: '#E9D5FF', 300: '#D8B4FE', 400: '#C084FC', 500: '#A855F7', 600: '#9333EA', 700: '#7E22CE', 800: '#6B21A8', 900: '#581C87' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>[x-cloak]{display:none!important}body{font-family:Inter,sans-serif}h1,h2,h3,.brand,.metric-value{font-family:'Plus Jakarta Sans',Inter,sans-serif;letter-spacing:-.02em}button,input,select,textarea{font-family:inherit}</style>
</head>
<body class="font-sans">
<main class="flex min-h-screen bg-white">
    <section class="relative hidden w-1/2 overflow-hidden bg-rescue-50 lg:flex">
        <img src="https://images.unsplash.com/photo-1589883661923-6476cb0ae9f2?auto=format&fit=crop&w=1500&q=80" alt="Cat in modern office" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-rescue-900/30 backdrop-blur-[2px]"></div>
        <div class="absolute bottom-12 left-12 right-12 text-white">
            <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 shadow-lg backdrop-blur-md">
                <i data-lucide="paw-print" class="h-6 w-6 text-white"></i>
            </div>
            <h2 class="mb-3 text-3xl font-semibold">CatCare 360 Staff Portal</h2>
            <p class="max-w-md text-lg font-medium leading-relaxed text-rescue-50">Manage animal intakes, adoption applications, and coordinate with our wonderful volunteers.</p>
        </div>
    </section>

    <section class="flex w-full flex-col items-center justify-center bg-slate-50 p-8 sm:p-12 lg:w-1/2 lg:p-24">
        <div class="w-full max-w-md">
            <a href="<?= site_url('/') ?>" class="mb-12 inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to Public Site
            </a>

            <div class="mb-10">
                <h1 class="mb-2 text-3xl font-semibold text-slate-900">Welcome Back</h1>
                <p class="font-medium text-slate-500">Please sign in to your staff account.</p>
            </div>

            <form method="post" action="<?= site_url('admin/login') ?>" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm" x-data="{ loading: false }" @submit.prevent="loading = true; $el.submit()">
                <?= csrf_field() ?>

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"></i>
                        <input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" placeholder="Enter your email" required class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 text-sm text-slate-900 transition-all placeholder:text-slate-400 focus:border-rescue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rescue-500/20">
                    </div>
                </div>

                <div x-data="{ show: false }">
                    <div class="mb-2 flex items-center justify-between">
                        <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                        <a href="#" class="text-sm font-medium text-rescue-600 hover:text-rescue-700">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"></i>
                        <input id="password" name="password" :type="show ? 'text' : 'password'" autocomplete="current-password" placeholder="Enter your password" required class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-11 text-sm text-slate-900 transition-all placeholder:text-slate-400 focus:border-rescue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rescue-500/20">
                        <button type="button" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" @click="show = !show">
                            <i data-lucide="eye" class="h-5 w-5" x-show="!show"></i>
                            <i data-lucide="eye-off" class="h-5 w-5" x-show="show" x-cloak></i>
                        </button>
                    </div>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                        <i data-lucide="alert-circle" class="h-5 w-5"></i>
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition class="flex items-center gap-2 rounded-lg border border-adopted-200 bg-adopted-50 px-4 py-3 text-sm text-adopted-700">
                        <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                        <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-rescue-600 py-3 font-medium text-white shadow-sm transition-colors hover:bg-rescue-700">
                    <span x-show="loading" x-cloak class="h-5 w-5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    <span>Sign In to Dashboard</span>
                </button>
            </form>

            <p class="mt-8 text-center text-sm font-medium text-slate-500">
                Don't have an account?
                <a href="mailto:support@catcare360.org" class="text-rescue-600 hover:underline">Contact IT Support</a>
            </p>
        </div>
    </section>
</main>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons())</script>
</body>
</html>
