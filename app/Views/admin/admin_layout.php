<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'CatCare 360') ?></title>
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
        tailwind.config = {
            theme: { extend: { colors: { rescue: { 50: '#EFF8FC', 100: '#D4EDF7', 200: '#A9DBEF', 300: '#6DC3E3', 400: '#30A8D2', 500: '#0D7EA3', 600: '#0A6A8A', 700: '#085670', 800: '#064257', 900: '#03293A' }, catgold: { 50: '#FFFBEB', 100: '#FEF3C7', 200: '#FDE68A', 300: '#FCD34D', 400: '#FBBF24', 500: '#F59E0B', 600: '#D97706', 700: '#B45309', 800: '#92400E', 900: '#78350F' }, adopted: { 50: '#F0FDF4', 100: '#DCFCE7', 200: '#BBF7D0', 300: '#86EFAC', 400: '#4ADE80', 500: '#22C55E', 600: '#16A34A', 700: '#15803D', 800: '#166534', 900: '#14532D' }, wanted: { 50: '#FEF2F2', 100: '#FEE2E2', 200: '#FECACA', 300: '#FCA5A5', 400: '#F87171', 500: '#EF4444', 600: '#DC2626', 700: '#B91C1C', 800: '#991B1B', 900: '#7F1D1D' }, foster: { 50: '#FAF5FF', 100: '#F3E8FF', 200: '#E9D5FF', 300: '#D8B4FE', 400: '#C084FC', 500: '#A855F7', 600: '#9333EA', 700: '#7E22CE', 800: '#6B21A8', 900: '#581C87' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>[x-cloak]{display:none!important}body{font-family:Inter,sans-serif}h1,h2,h3,.brand,.metric-value{font-family:'Plus Jakarta Sans',Inter,sans-serif;letter-spacing:-.02em}button,input,select,textarea{font-family:inherit}.card{background:#fff;border:1px solid #E2E8F0;border-radius:1rem;padding:1.5rem;box-shadow:0 1px 3px rgba(13,126,163,.08),0 1px 2px rgba(13,126,163,.06)}.btn{display:inline-flex;align-items:center;gap:.5rem;border-radius:.5rem;padding:.625rem 1rem;font-size:.875rem;font-weight:600}.btn-primary{background:#0D7EA3;color:white}.btn-primary:hover{background:#0A6A8A}.btn-ghost{background:white;border:1px solid #E2E8F0;color:#334155}.field{width:100%;border-radius:.75rem;border:1px solid #E2E8F0;background:#F8FAFC;padding:.625rem .875rem;font-size:.875rem}.label{display:block;margin-bottom:.375rem;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748B}</style>
</head>
<body class="font-sans">
<div x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-50 text-slate-900 lg:flex lg:gap-6 lg:p-4">
    <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false"></div>
    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col overflow-y-auto border-r border-slate-200 bg-white shadow-xl transition-transform duration-200 lg:static lg:w-64 lg:max-w-none lg:translate-x-0 lg:overflow-hidden lg:rounded-2xl lg:border lg:shadow-sm" :class="sidebarOpen && 'translate-x-0'">
        <div class="flex items-center gap-3 border-b border-slate-100 p-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rescue-600 text-white shadow-sm">
                <i data-lucide="paw-print" class="h-5 w-5"></i>
            </div>
            <div class="brand text-xl font-semibold tracking-tight text-slate-900">CatCare</div>
        </div>

        <?php
            $path = trim(service('uri')->getPath(), '/');
            $nav = [
                ['Dashboard', 'layout-dashboard', 'admin/dashboard'],
                ['Cat Management', 'paw-print', 'admin/animals'],
                ['Applications', 'file-text', 'admin/applications'],
                ['Adopters', 'heart', 'admin/adopters'],
                ['Monitoring', 'activity', 'admin/monitoring'],
                ['Home Visits', 'house-heart', 'admin/home-visits'],
                ['Volunteers', 'users', 'admin/volunteers'],
                ['Tasks & Schedule', 'clipboard-list', 'admin/tasks'],
            ];
            $tools = [
                ['Analytics', 'bar-chart-3', 'admin/analytics'],
                ['Reports', 'book-open', 'admin/reports'],
            ];
        ?>

        <nav class="flex flex-1 flex-col gap-1.5 p-4">
            <?php foreach ($nav as $item): ?>
                <?php $active = $path === $item[2] || ($item[2] !== 'admin/dashboard' && str_starts_with($path, $item[2] . '/')); ?>
                <a href="<?= site_url($item[2]) ?>" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors <?= $active ? 'bg-rescue-50 text-rescue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                    <i data-lucide="<?= esc($item[1]) ?>" class="h-5 w-5"></i>
                    <?= esc($item[0]) ?>
                </a>
            <?php endforeach; ?>

            <div class="mt-2 flex flex-col gap-1.5 border-t border-slate-100 pt-2">
                <div class="mb-1 px-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Analytics & Tools</div>
                <?php foreach ($tools as $item): ?>
                    <?php $active = $path === $item[2] || str_starts_with($path, $item[2] . '/'); ?>
                    <a href="<?= site_url($item[2]) ?>" class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors <?= $active ? 'bg-rescue-50 text-rescue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <i data-lucide="<?= esc($item[1]) ?>" class="h-5 w-5"></i>
                        <?= esc($item[0]) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>

        <div class="flex flex-col gap-3 border-t border-slate-100 p-4">
            <a href="<?= site_url('admin/logout') ?>" class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-100 px-4 py-2.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                <i data-lucide="log-out" class="h-4 w-4"></i>
                Log Out
            </a>
            <div class="flex items-center justify-center gap-2 py-2 text-sm font-medium text-slate-500">
                <span class="h-2 w-2 rounded-full bg-adopted-500 shadow-[0_0_0_2px_rgba(16,185,129,0.2)]"></span>
                System Online
            </div>
        </div>
    </aside>

    <main class="relative min-w-0 flex-1 lg:overflow-y-auto">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur lg:hidden">
            <button type="button" @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Open navigation"><i data-lucide="menu" class="h-6 w-6"></i></button>
            <a href="<?= site_url('admin/dashboard') ?>" class="flex items-center gap-2 font-semibold"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rescue-600 text-white"><i data-lucide="paw-print" class="h-4 w-4"></i></span>CatCare Admin</a>
        </header>
        <?php if (session()->getFlashdata('success')): ?>
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition class="fixed right-4 top-4 z-[200] flex items-center gap-3 rounded-xl border border-adopted-200 bg-white px-5 py-3.5 text-sm font-medium text-adopted-700 shadow-lg">
                <i data-lucide="check-circle-2" class="h-5 w-5 text-adopted-600"></i>
                <?= esc(session()->getFlashdata('success')) ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition class="fixed right-4 top-4 z-[200] flex items-center gap-3 rounded-xl border border-red-200 bg-white px-5 py-3.5 text-sm font-medium text-red-600 shadow-lg">
                <i data-lucide="alert-circle" class="h-5 w-5 text-red-500"></i>
                <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>

        <div class="mx-auto max-w-[1600px] px-4 py-4 lg:pr-4">
            <?= $this->renderSection('content') ?>
        </div>
    </main>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons();const g={'Long-Term Commitment and Care Readiness':['1. Long-Term Commitment, Daily Care, and Household Readiness',23],'Love, Attention, and Daily Care':['1. Long-Term Commitment, Daily Care, and Household Readiness',23],'Family Readiness and Support':['1. Long-Term Commitment, Daily Care, and Household Readiness',23],'Housing Status and Permission':['2. Housing, Indoor Living, Escape Prevention, and Humane Space',30],'Indoor Living and Escape Prevention':['2. Housing, Indoor Living, Escape Prevention, and Humane Space',30],'Humane Housing and No Permanent Cage Confinement':['2. Housing, Indoor Living, Escape Prevention, and Humane Space',30],'Healthy Food and Clean Water':['3. Food, Water, Veterinary Care, and Parasite Prevention',28],'Vaccinations and Veterinary Expenses':['3. Food, Water, Veterinary Care, and Parasite Prevention',28],'Deworming Schedule':['3. Food, Water, Veterinary Care, and Parasite Prevention',28],'Tick and Flea Prevention':['3. Food, Water, Veterinary Care, and Parasite Prevention',28],'Spaying/Neutering Plan':['4. Spaying/Neutering and Existing Pets',14],'Existing Pets and Sterilization Status':['4. Spaying/Neutering and Existing Pets',14],'Backup Caregiver and Contingency Plan':['5. Backup Caregiver and Emergency Plan',5]};const buckets={};document.querySelectorAll('article').forEach(c=>{const h=c.querySelector('h3');if(h&&g[h.textContent.trim()])(buckets[g[h.textContent.trim()][0]]??=[]).push(c)});Object.entries(buckets).forEach(([title,cards])=>{const first=cards[0],badge=first.querySelector('span.rounded-full'),weight=g[first.querySelector('h3').textContent.trim()][1],points=cards.reduce((n,c)=>n+(parseFloat((c.querySelector('span.rounded-full')?.textContent.match(/·\s*([\d.]+)\s*pts/)||[])[1])||0),0);first.querySelector('h3').textContent=title;if(badge)badge.textContent=`${points.toFixed(2)} / ${weight.toFixed(2)} pts`;const answer=first.querySelector('p.whitespace-pre-line');if(answer){const parts=answer.textContent.split(/\n\s*\n/);answer.replaceChildren();parts.forEach((part,i)=>{const [label,...body]=part.split(': '),box=document.createElement('div'),strong=document.createElement('b'),p=document.createElement('p');box.className=i===0?'mb-4 rounded-lg bg-rescue-50 p-3':'mt-3 border-l-2 border-slate-200 pl-3';strong.className='text-rescue-800';strong.textContent=label;p.className='mt-1 whitespace-pre-line';p.textContent=body.join(': ');box.append(strong,p);answer.append(box)})}cards.slice(1).forEach(c=>c.remove())})})</script>
<?= $this->renderSection('scripts') ?>
<style>select[name="category"] option[value="Adoption"]{display:none!important}</style>
<script>(function(){function removeLegacyAdoptionCategory(){document.querySelectorAll('select[name="category"] option[value="Adoption"]').forEach(function(option){option.remove();});}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',removeLegacyAdoptionCategory);else removeLegacyAdoptionCategory();})();</script>
<script>document.querySelectorAll('article').forEach(c=>{const h=c.querySelector('h3');if(h&&h.textContent.includes('6. Overall Readiness')){const b=c.querySelector('span.rounded-full');if(b)b.textContent='Unweighted reflection';}})</script>
<?= view('components/session_timeout', [
    'idleSeconds' => config('AuthSession')->staffIdleSeconds,
    'warningSeconds' => config('AuthSession')->warningSeconds,
    'absoluteSeconds' => config('AuthSession')->absoluteSeconds,
    'keepAliveUrl' => site_url('admin/session/keep-alive'),
    'logoutUrl' => site_url('admin/logout'),
]) ?>
</body>
</html>
