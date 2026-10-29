<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'CatCare 360') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { rescue: { 50: '#EFF8FC', 100: '#D4EDF7', 200: '#A9DBEF', 300: '#6DC3E3', 400: '#30A8D2', 500: '#0D7EA3', 600: '#0A6A8A', 700: '#085670', 800: '#064257', 900: '#03293A' }, catgold: { 50: '#FFFBEB', 100: '#FEF3C7', 200: '#FDE68A', 300: '#FCD34D', 400: '#FBBF24', 500: '#F59E0B', 600: '#D97706', 700: '#B45309', 800: '#92400E', 900: '#78350F' }, adopted: { 50: '#F0FDF4', 100: '#DCFCE7', 200: '#BBF7D0', 300: '#86EFAC', 400: '#4ADE80', 500: '#22C55E', 600: '#16A34A', 700: '#15803D', 800: '#166534', 900: '#14532D' }, wanted: { 50: '#FEF2F2', 100: '#FEE2E2', 200: '#FECACA', 300: '#FCA5A5', 400: '#F87171', 500: '#EF4444', 600: '#DC2626', 700: '#B91C1C', 800: '#991B1B', 900: '#7F1D1D' }, foster: { 50: '#FAF5FF', 100: '#F3E8FF', 200: '#E9D5FF', 300: '#D8B4FE', 400: '#C084FC', 500: '#A855F7', 600: '#9333EA', 700: '#7E22CE', 800: '#6B21A8', 900: '#581C87' } } } } };
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>[x-cloak]{display:none!important}body{font-family:Inter,sans-serif;background:#F5F7FA;color:#0F172A}.btn{display:inline-flex;align-items:center;gap:.5rem;border-radius:.5rem;padding:.625rem 1rem;font-size:.875rem;font-weight:600}.btn-primary{background:#0D7EA3;color:white}.btn-primary:hover{background:#0A6A8A}.btn-ghost{background:white;border:1px solid #E2E8F0;color:#334155}.card{background:white;border:1px solid #E2E8F0;border-radius:1rem;padding:1.5rem;box-shadow:0 1px 3px rgba(13,126,163,.08),0 1px 2px rgba(13,126,163,.06)}.field{width:100%;border-radius:.75rem;border:1px solid #E2E8F0;background:#F8FAFC;padding:.625rem .875rem;font-size:.875rem}.label{display:block;margin-bottom:.375rem;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748B}.terms-page-lock{overflow:hidden}</style>
</head>
<body x-data="<?= esc('Object.assign(authState(' . (session('auth_modal') ? 'true' : 'false') . ', ' . json_encode(session('auth_modal') ?: 'signin') . ', ' . json_encode(session('signin_type') ?: 'adopter') . ', ' . json_encode(old('email')) . ', ' . json_encode(session('login_error') ?: '') . '), { mobileMenu: false })', 'attr') ?>">
<nav class="fixed inset-x-0 top-0 z-40 h-16 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-full max-w-7xl items-center justify-between px-4">
        <a href="<?= site_url('/') ?>" class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rescue-600 text-white shadow-sm" style="background-color:#0A6A8A"><i data-lucide="paw-print" class="h-5 w-5"></i></span>
            <span><span class="block text-sm font-bold tracking-tight">CatCare 360</span><span class="block text-xs font-medium text-slate-500">McKinley West Cats</span></span>
        </a>
        <div class="hidden items-center gap-2 md:flex">
            <a href="<?= site_url('/') ?>#home" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-rescue-50 hover:text-rescue-600">Home</a>
            <a href="<?= site_url('/') ?>#rules" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-rescue-50 hover:text-rescue-600">Rules & Regulations</a>
            <a href="<?= site_url('/') ?>#volunteer" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-rescue-50 hover:text-rescue-600">Become a Volunteer</a>
        </div>
        <div class="flex items-center gap-2">
            <?php if (session('adopter_user')): ?>
                <?php $adopterUser = session('adopter_user'); ?>
                <a href="<?= site_url('adopter/home') ?>" class="hidden text-sm font-semibold text-slate-600 sm:inline"><?= esc($adopterUser['name'] ?? 'Adopter') ?></a>
                <a href="<?= site_url('auth/logout') ?>" class="btn btn-ghost">Log Out</a>
            <?php else: ?>
                <button type="button" class="btn btn-ghost px-3 sm:px-4" @click="authOpen=true; authTab='signin'"><span class="hidden sm:inline">Sign In</span><i data-lucide="log-in" class="h-4 w-4 sm:hidden"></i></button>
                <button type="button" class="btn btn-primary px-3 sm:px-4" @click="authOpen=true; authTab='signup'"><span class="hidden sm:inline">Create Account</span><i data-lucide="user-plus" class="h-4 w-4 sm:hidden"></i></button>
            <?php endif; ?>
            <button type="button" @click="mobileMenu=!mobileMenu" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Toggle navigation"><i data-lucide="menu" class="h-5 w-5"></i></button>
        </div>
    </div>
    <div x-cloak x-show="mobileMenu" x-transition class="border-t border-slate-100 bg-white px-4 py-3 shadow-lg md:hidden">
        <a @click="mobileMenu=false" href="<?= site_url('/') ?>#home" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-rescue-50 hover:text-rescue-600">Home</a>
        <a @click="mobileMenu=false" href="<?= site_url('/') ?>#rules" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-rescue-50 hover:text-rescue-600">Rules &amp; Regulations</a>
        <a @click="mobileMenu=false" href="<?= site_url('/') ?>#volunteer" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-rescue-50 hover:text-rescue-600">Become a Volunteer</a>
    </div>
</nav>
<?php if (session('success') || session('error')): ?>
    <div class="fixed left-1/2 top-20 z-50 -translate-x-1/2 rounded-xl border bg-white px-4 py-3 text-sm font-semibold shadow-lg"><?= esc(session('error') ?: session('success')) ?></div>
<?php endif; ?>
<?= $this->renderSection('content') ?>
<div x-cloak x-show="authOpen" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/50 p-4 backdrop-blur-sm" @keydown.escape.window="authOpen=false">
    <div class="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white shadow-2xl" @click.outside="authOpen=false">
        <div class="relative border-b border-slate-200 p-6 text-center">
            <button class="absolute right-4 top-4 rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="authOpen=false"><i data-lucide="x" class="h-5 w-5"></i></button>
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-rescue-600 text-white"><i data-lucide="paw-print"></i></div>
            <h2 class="text-xl font-bold tracking-[-.025em] text-slate-900">CatCare 360</h2>
            <p class="text-sm font-medium text-slate-500">Adopters and volunteers portal</p>
        </div>
        <div class="p-6">
            <div class="mb-5 grid grid-cols-2 rounded-xl bg-slate-100 p-1">
                <button type="button" class="rounded-lg px-4 py-2 text-sm font-semibold" :class="authTab==='signin' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400'" @click="authTab='signin'">Sign In</button>
                <button type="button" class="rounded-lg px-4 py-2 text-sm font-semibold" :class="authTab==='signup' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400'" @click="authTab='signup'">Create Account</button>
            </div>

            <div x-show="authTab==='signin' && !recoveryOpen">
                <form method="post" action="<?= site_url('auth/login') ?>" @submit="if (!validateLogin(true)) $event.preventDefault()" novalidate class="space-y-4">
                    <?= csrf_field() ?>
                    <div><label class="label">Email</label><input class="field" :class="loginErrors.email && 'border-red-400 focus:ring-red-100'" x-model="login.email" @input="validateLogin" type="email" name="email" autocomplete="email"><p x-show="loginErrors.email" x-text="loginErrors.email" class="mt-1 text-xs font-medium text-red-600"></p></div>
                    <div><div class="mb-1 flex items-center justify-between"><label class="label mb-0">Password</label><button type="button" @click="openRecovery" class="text-xs font-semibold text-rescue-600">Forgot password?</button></div><div class="relative"><input class="field pr-20" :class="loginErrors.password && 'border-red-400 focus:ring-red-100'" x-model="login.password" @input="validateLogin" :type="showLoginPassword ? 'text' : 'password'" name="password" autocomplete="current-password"><button type="button" @click="showLoginPassword=!showLoginPassword" class="absolute inset-y-0 right-2 px-2 text-xs font-semibold text-rescue-600" x-text="showLoginPassword ? 'Hide' : 'Show'"></button></div><p x-show="loginErrors.password" x-text="loginErrors.password" class="mt-1 text-xs font-medium text-red-600"></p></div>
                    <button class="btn btn-primary w-full justify-center">Sign In</button>
                </form>
            </div>

            <div x-show="authTab==='signin' && recoveryOpen" class="space-y-5">
                <div class="flex items-center gap-3" x-show="recoveryStep!=='success'"><button type="button" @click="recoveryOpen=false; recoveryStep='email'; recoveryErrors={}; recoveryError=''" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><i data-lucide="arrow-left" class="h-5 w-5"></i></button><div><h3 class="text-lg font-bold text-slate-900" x-text="recoveryStep==='email' ? 'Reset your password' : (recoveryStep==='code' ? 'Verify your code' : 'Create a new password')"></h3><p class="text-sm text-slate-500" x-text="recoveryStep==='email' ? 'We’ll email you a six-digit reset code.' : (recoveryStep==='code' ? 'Enter the code from your email to continue.' : 'Choose a strong new password.')"></p></div></div>
                <form x-show="recoveryStep==='email'" @submit.prevent="sendResetCode" novalidate class="space-y-4"><div x-show="recoveryError" x-text="recoveryError" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700"></div><div><label class="label">Email Address</label><input class="field" :class="recoveryErrors.email && 'border-red-400'" x-model="reset.email" type="email" autocomplete="email"><p x-show="recoveryErrors.email" x-text="recoveryErrors.email" class="mt-1 text-xs font-medium text-red-600"></p></div><button class="btn btn-primary w-full justify-center" :disabled="loading" x-text="loading ? 'Sending code…' : 'Send reset code'"></button></form>
                <form x-show="recoveryStep==='code'" @submit.prevent="verifyResetCode" novalidate class="space-y-4"><div class="rounded-xl border border-rescue-100 bg-rescue-50 p-3 text-center text-sm text-rescue-800">Code sent to <strong x-text="reset.email"></strong></div><div x-show="recoveryError" x-text="recoveryError" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700"></div><div><label class="label">Six-digit code</label><div class="flex justify-center gap-2" @paste.prevent="pasteResetCode($event)"><template x-for="(digit, index) in reset.code" :key="index"><input :id="'reset-code-'+index" class="h-12 w-10 rounded-xl border bg-white text-center text-lg font-bold outline-none focus:border-rescue-500 focus:ring-4 focus:ring-rescue-100" :class="recoveryErrors.code && 'border-red-400'" inputmode="numeric" maxlength="1" x-model="reset.code[index]" @input="moveResetFocus($event,index)" @keydown.backspace="backspaceReset($event,index)"></template></div><p x-show="recoveryErrors.code" x-text="recoveryErrors.code" class="mt-1 text-center text-xs font-medium text-red-600"></p></div><button class="btn btn-primary w-full justify-center" :disabled="loading" x-text="loading?'Verifying code…':'Continue'"></button><button type="button" @click="sendResetCode" class="block w-full text-center text-sm font-semibold text-rescue-600">Resend code</button></form>
                <form x-show="recoveryStep==='password'" @submit.prevent="completePasswordReset" novalidate class="space-y-4"><div class="rounded-xl border border-adopted-100 bg-adopted-50 p-3 text-center text-sm font-medium text-adopted-700"><i data-lucide="check-circle-2" class="mr-1 inline h-4 w-4"></i>Code verified. Create your new password.</div><div x-show="recoveryError" x-text="recoveryError" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700"></div><div><label class="label">New Password</label><div class="relative"><input class="field pr-20" :class="recoveryErrors.password && 'border-red-400'" x-model="reset.password" :type="showResetPassword?'text':'password'" autocomplete="new-password"><button type="button" @click="showResetPassword=!showResetPassword" class="absolute inset-y-0 right-2 px-2 text-xs font-semibold text-rescue-600" x-text="showResetPassword?'Hide':'Show'"></button></div><p x-show="recoveryErrors.password" x-text="recoveryErrors.password" class="mt-1 text-xs font-medium text-red-600"></p></div><div><label class="label">Confirm New Password</label><input class="field" :class="recoveryErrors.confirm_password && 'border-red-400'" x-model="reset.confirm_password" :type="showResetPassword?'text':'password'" autocomplete="new-password"><p x-show="recoveryErrors.confirm_password" x-text="recoveryErrors.confirm_password" class="mt-1 text-xs font-medium text-red-600"></p></div><button class="btn btn-primary w-full justify-center" :disabled="loading" x-text="loading?'Updating password…':'Update password'"></button></form>
                <div x-show="recoveryStep==='success'" class="py-6 text-center"><div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-adopted-50 text-adopted-600"><i data-lucide="check-circle-2" class="h-8 w-8"></i></div><p class="text-xs font-bold uppercase tracking-[.16em] text-adopted-600">Password updated</p><h3 class="mt-2 text-2xl font-bold text-slate-900">You’re all set!</h3><p class="mt-3 text-sm leading-6 text-slate-500">Your password was changed successfully. You can now sign in with your new password.</p><button type="button" @click="recoveryOpen=false; recoveryStep='email'; reset={email:'',code:['','','','','',''],password:'',confirm_password:''}" class="btn btn-primary mt-6 w-full justify-center">Back to Sign In</button></div>
            </div>

            <form x-show="authTab==='signup' && registrationStep==='details'" @submit.prevent="sendCode" novalidate class="space-y-4">
                <?= csrf_field() ?>
                <div x-show="formError" x-text="formError" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="label">First Name</label><input class="field" :class="errors.first_name && 'border-red-400 focus:ring-red-100'" x-model="form.first_name" name="first_name" autocomplete="given-name"><p x-show="errors.first_name" x-text="errors.first_name" class="mt-1 text-xs font-medium text-red-600"></p></div>
                    <div><label class="label">Last Name</label><input class="field" :class="errors.last_name && 'border-red-400 focus:ring-red-100'" x-model="form.last_name" name="last_name" autocomplete="family-name"><p x-show="errors.last_name" x-text="errors.last_name" class="mt-1 text-xs font-medium text-red-600"></p></div>
                </div>
                <div><label class="label">Email Address</label><input class="field" :class="errors.email && 'border-red-400 focus:ring-red-100'" x-model="form.email" @input="validateRegistrationField('email')" type="email" name="email" autocomplete="email" placeholder="yourname@example.com"><p class="mt-1 text-xs text-slate-500">Use an active email inbox. A verification code is required before an account is created.</p><p x-show="errors.email" x-text="errors.email" class="mt-1 text-xs font-medium text-red-600"></p></div>
                <div>
                    <label class="label">Password</label>
                    <div class="relative"><input class="field pr-20" :class="errors.password && 'border-red-400 focus:ring-red-100'" x-model="form.password" @input="validateRegistrationField('password')" :type="showPassword ? 'text' : 'password'" name="password" autocomplete="new-password"><button type="button" @click="showPassword=!showPassword" class="absolute inset-y-0 right-2 px-2 text-xs font-semibold text-rescue-600" x-text="showPassword ? 'Hide' : 'Show'"></button></div>
                    <p class="mt-1 text-xs text-slate-500">Use 8+ characters with an uppercase letter, lowercase letter, number, and special character.</p>
                    <p x-show="errors.password" x-text="errors.password" class="mt-1 text-xs font-medium text-red-600"></p>
                </div>
                <div>
                    <label class="label">Confirm Password</label>
                    <div class="relative"><input class="field pr-20" :class="errors.confirm_password && 'border-red-400 focus:ring-red-100'" x-model="form.confirm_password" @input="validateRegistrationField('confirm_password')" :type="showPassword ? 'text' : 'password'" name="confirm_password" autocomplete="new-password"><button type="button" @click="showPassword=!showPassword" class="absolute inset-y-0 right-2 px-2 text-xs font-semibold text-rescue-600" x-text="showPassword ? 'Hide' : 'Show'"></button></div>
                    <p x-show="errors.confirm_password" x-text="errors.confirm_password" class="mt-1 text-xs font-medium text-red-600"></p>
                </div>
                <div class="rounded-xl border p-3" :class="errors.terms ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-slate-50'">
                    <label class="flex items-start gap-3 text-sm text-slate-600"><input type="checkbox" :checked="termsAccepted" @click.prevent="termsAccepted ? revokeTerms() : openTerms()" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-rescue-600"><span><button type="button" class="font-semibold text-rescue-600 hover:text-rescue-700" @click.prevent="openTerms">Read and accept the Terms &amp; Conditions</button><span x-show="termsAccepted"> — accepted</span>.</span></label>
                    <p x-show="errors.terms" x-text="errors.terms" class="mt-2 text-xs font-medium text-red-600"></p>
                </div>
                <button class="btn btn-primary w-full justify-center" :disabled="loading || !termsAccepted" :class="(loading || !termsAccepted) && 'cursor-not-allowed opacity-60'"><span x-text="loading ? 'Creating account…' : 'Create account'"></span></button>
            </form>
            <form x-show="authTab==='signup' && registrationStep==='verify'" @submit.prevent="verifyCode" novalidate class="space-y-5">
                <div class="overflow-hidden rounded-2xl border border-rescue-100 bg-gradient-to-br from-rescue-50 via-white to-rescue-50 p-5 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-rescue-600 text-white shadow-lg shadow-rescue-200"><i data-lucide="mail-check" class="h-6 w-6"></i></div>
                    <div class="mb-3 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-[.16em] text-rescue-600"><span class="h-2 w-2 rounded-full bg-rescue-600"></span> Secure verification</div>
                    <h3 class="text-xl font-bold tracking-[-.025em] text-slate-900">Check your inbox</h3>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">We sent a one-time code to</p>
                    <p class="mx-auto mt-1 max-w-sm break-all rounded-lg bg-white/80 px-3 py-2 text-sm font-bold text-slate-700 shadow-sm" x-text="form.email"></p>
                </div>
                <div x-show="formError" x-text="formError" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700"></div>
                <div>
                    <div class="mb-3 flex items-center justify-between"><label class="label mb-0">Enter 6-digit code</label><span class="text-xs font-medium text-slate-400">Expires in 10 minutes</span></div>
                    <div class="flex justify-center gap-2" @paste.prevent="pasteCode($event)">
                        <template x-for="(digit, index) in code" :key="index"><input :id="'verification-code-' + index" class="h-12 w-10 rounded-xl border bg-white text-center text-lg font-bold text-slate-900 shadow-sm outline-none transition duration-150 placeholder:text-slate-300 hover:border-rescue-300 focus:border-rescue-500 focus:ring-4 focus:ring-rescue-100 sm:h-14 sm:w-11" :class="errors.code ? 'border-red-400 focus:ring-red-100' : 'border-slate-200'" inputmode="numeric" autocomplete="one-time-code" maxlength="1" x-model="code[index]" @input="moveCodeFocus($event, index)" @keydown.backspace="backspaceCode($event, index)"></template>
                    </div>
                </div>
                <p x-show="errors.code" x-text="errors.code" class="-mt-3 text-center text-xs font-medium text-red-600"></p>
                <button class="btn btn-primary w-full justify-center" :disabled="loading" :class="loading && 'cursor-wait opacity-70'" x-text="loading ? 'Verifying…' : 'Verify & create account'"></button>
                <div class="rounded-xl bg-slate-50 px-4 py-3 text-center text-xs leading-5 text-slate-500"><i data-lucide="shield-check" class="mr-1 inline h-4 w-4 text-adopted-600"></i>For your security, never share this code with anyone.</div>
                <div class="flex items-center justify-between text-sm"><button type="button" @click="registrationStep='details'; errors={}; formError=''" class="font-semibold text-slate-500 hover:text-slate-700">Edit details</button><button type="button" @click="sendCode" :disabled="loading" class="font-semibold text-rescue-600 hover:text-rescue-700">Resend code</button></div>
            </form>
        </div>
    </div>
</div>
<div x-cloak x-show="termsOpen" class="fixed inset-0 z-[60] grid place-items-center overflow-hidden bg-slate-900/60 p-4 backdrop-blur-sm" @wheel.prevent @touchmove.prevent>
    <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" @wheel.stop @touchmove.stop>
        <div class="flex items-center justify-between border-b p-5"><div class="flex items-center gap-3 font-bold"><i data-lucide="scroll-text" class="h-5 w-5 text-rescue-600"></i>Terms & Conditions</div><button type="button" @click="closeTerms"><i data-lucide="x" class="h-5 w-5"></i></button></div>
        <div x-ref="termsPanel" class="max-h-[46vh] flex-1 space-y-5 overflow-y-auto overscroll-contain p-6 text-sm leading-6 text-slate-600" @scroll="termsScrolled = $event.target.scrollTop + $event.target.clientHeight >= $event.target.scrollHeight - 8">
            <p><b>1. Acceptance and Scope.</b> These Terms &amp; Conditions govern your use of the CatCare 360 website and application. By creating an account, submitting a form, uploading a file, or using any feature, you agree to follow these terms. If you do not agree, please do not use the system.</p>
            <p><b>2. Purpose of CatCare 360.</b> CatCare 360 is used to manage cat profiles, adoption applications, document review, account communication, and post-adoption monitoring. Use of the system does not guarantee account approval, application approval, or the availability of a particular cat.</p>
            <p><b>3. Account Information and Security.</b> Provide complete, current, and truthful account information. Keep your password and verification code private, and notify the shelter immediately if you believe your account has been accessed without permission. You are responsible for activity completed through your account.</p>
            <p><b>4. Uploaded Documents.</b> The system may request a valid government ID, proof of address, home photos, a selfie with ID, and other documents needed to review an application or confirm identity. Upload only documents that belong to you or that you are authorized to submit. Documents must be clear, genuine, and current where applicable.</p>
            <p><b>5. Data Privacy and Use.</b> CatCare 360 collects only the personal information, documents, messages, photos, and monitoring records needed to operate the website and support the adoption process. Authorized shelter personnel may access this information for identity verification, application review, account support, cat welfare monitoring, recordkeeping, and compliance with applicable requirements. We do not sell personal data.</p>
            <p><b>6. Protection of Sensitive Records.</b> Government IDs, proof of address, contact details, and monitoring information are treated as confidential records and protected with reasonable administrative and technical safeguards. Do not upload unrelated sensitive information. While we take reasonable steps to protect records, no online system can guarantee absolute security.</p>
            <p><b>7. Data Retention and Requests.</b> Records are retained only for as long as reasonably needed for account management, application review, welfare follow-up, dispute handling, and legal or operational obligations. You may contact the official CatCare 360 channel to ask about your personal data, subject to identity verification and applicable recordkeeping requirements.</p>
            <p><b>8. Truthful Use of the System.</b> Do not submit false details, edited or borrowed documents, misleading photos, old monitoring images presented as new, or another person's information without permission. False or fraudulent submissions may result in application rejection, account restriction, removal from the system, and referral to the proper authority when warranted.</p>
            <p><b>9. Post-Adoption Monitoring.</b> Adopters must submit requested post-adoption updates through CatCare 360 at the scheduled checkpoints. Updates may include recent photos of the adopted cat, health or behavior notes, and other reasonable welfare information. The system may record the submission date, time, and verification details needed to review the update.</p>
            <p><b>10. Location and Monitoring Permission.</b> If the monitoring feature asks for location permission, location information is used only to verify the stated welfare update and protect the adopted cat. You may decline permission, but the shelter may request another reasonable form of verification. Location-related records are handled as confidential monitoring data.</p>
            <p><b>11. Missed Updates and Welfare Escalation.</b> If an adopter does not submit required monitoring updates, CatCare 360 and the shelter may send reminders and request follow-up information. Continued non-response, repeated missed updates, false monitoring records, or a credible welfare concern may lead to account restriction and an investigation into the cat's status. When the cat's whereabouts or welfare cannot be confirmed, the shelter may mark the case for missing/unverified-cat follow-up, seek information needed to locate the cat, and report the concern to the Bureau of Animal Industry (BAI) or another appropriate authority when warranted.</p>
            <p><b>12. Submitted Photos and Content.</b> You are responsible for the content you upload. Do not submit unlawful, harmful, copyrighted, or privacy-violating material. Photos and updates submitted for monitoring may be used by authorized shelter personnel for the cat's record, welfare review, and non-identifying system reporting.</p>
            <p><b>13. Responsible and Lawful Use.</b> Use CatCare 360 only for its intended adoption, monitoring, or approved volunteer purpose. Do not attempt to damage, interrupt, copy, misuse, impersonate, or gain unauthorized access to the website, application, accounts, or records. We may suspend or close accounts that violate these terms or place a cat's welfare at risk.</p>
            <p><b>14. Updates to These Terms and Contact.</b> We may update these Terms &amp; Conditions when website features, privacy practices, monitoring procedures, or applicable requirements change. Material updates may be shown in the system or sent to your registered contact details. For questions about account access, documents, privacy, or monitoring, contact the official CatCare 360 or McKinley West Cats communication channel.</p>
            <div class="rounded-xl border border-rescue-100 bg-rescue-50 p-4 text-rescue-800"><b>You reached the end.</b> Confirm below to record your acceptance before continuing.</div>
        </div>
        <div class="border-t bg-slate-50/50 p-5"><label class="flex items-start gap-3 text-sm font-medium text-slate-700"><input type="checkbox" x-model="termsChecked" :disabled="!termsScrolled" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-rescue-600 disabled:cursor-not-allowed disabled:opacity-40"><span :class="!termsScrolled && 'text-slate-400'">I have read and agree to the Terms &amp; Conditions.</span></label><p x-show="!termsScrolled" class="mt-2 text-xs text-slate-500">Scroll through the terms to enable this checkbox.</p><div class="mt-4 flex justify-end gap-3"><button type="button" class="btn btn-ghost" @click="closeTerms">Close</button><button type="button" class="btn btn-primary" :disabled="!termsChecked" :class="!termsChecked && 'cursor-not-allowed opacity-50'" @click="acceptTerms">Continue</button></div></div>
    </div>
</div>
<footer class="border-t border-slate-800 bg-slate-900 py-8 text-slate-400">
    <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 text-white"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rescue-600"><i data-lucide="paw-print" class="h-5 w-5"></i></span><span class="font-semibold">CatCare 360 <span class="text-slate-400">McKinley West Cats</span></span></div>
        <p class="text-sm">© 2026 CatCare 360.</p>
        <div class="flex gap-4 text-sm"><a href="#" @click.prevent="authOpen=true; authTab='signin'">Sign In</a><a href="#" @click.prevent="authOpen=true; authTab='signup'">Create Account</a></div>
    </div>
</footer>
<script>
function authState(initialOpen, initialTab, initialType, initialLoginEmail, initialLoginError) {
    return {
        authOpen: initialOpen, authTab: initialTab, signType: initialType, termsOpen: false, termsScrolled: false, termsChecked: false, termsAccepted: false,
        registrationStep: 'details', loading: false, showPassword: false, showLoginPassword: false, formError: '', errors: {}, code: ['', '', '', '', '', ''],
        login: {email: initialLoginEmail || '', password: ''}, loginErrors: initialLoginError ? {password: initialLoginError} : {},
        recoveryOpen: false, recoveryStep: 'email', recoveryError: '', recoveryErrors: {}, showResetPassword: false, reset: {email: '', code: ['', '', '', '', '', ''], password: '', confirm_password: ''},
        form: {first_name: '', last_name: '', email: '', password: '', confirm_password: ''},
        openTerms() { this.termsOpen = true; this.termsScrolled = this.termsAccepted; this.termsChecked = this.termsAccepted; document.body.classList.add('terms-page-lock'); this.$nextTick(() => { const panel = this.$refs.termsPanel; if (panel && !this.termsAccepted) panel.scrollTop = 0; }); },
        closeTerms() { this.termsOpen = false; document.body.classList.remove('terms-page-lock'); },
        acceptTerms() { if (!this.termsScrolled || !this.termsChecked) return; this.termsAccepted = true; this.errors.terms = ''; this.closeTerms(); this.authOpen = true; this.authTab = 'signup'; this.registrationStep = 'details'; },
        revokeTerms() { this.termsAccepted = false; this.termsChecked = false; this.errors.terms = ''; },
        emailError(value, required = false) {
            if (!value) return required ? 'Enter your email address.' : '';
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? '' : 'Enter a valid email address (include @).';
        },
        passwordError(value, required = false) {
            if (!value) return required ? 'Enter your password.' : '';
            if (value.length < 8) return 'Use at least 8 characters.';
            if (!/[A-Z]/.test(value)) return 'Include at least one uppercase letter.';
            if (!/[a-z]/.test(value)) return 'Include at least one lowercase letter.';
            if (!/\d/.test(value)) return 'Include at least one number.';
            if (!/[^A-Za-z0-9]/.test(value)) return 'Include at least one special character.';
            return '';
        },
        setError(group, field, message) { this[group] = {...this[group], [field]: message}; },
        validateLogin(showEmpty = false) {
            const emailError = this.emailError(this.login.email, showEmpty);
            const passwordError = this.login.password ? '' : (showEmpty ? 'Enter your password.' : '');
            this.loginErrors = {email: emailError, password: passwordError};
            return !emailError && !passwordError;
        },
        validateRegistrationField(field) {
            if (field === 'email') this.setError('errors', 'email', this.emailError(this.form.email));
            if (field === 'password') {
                this.setError('errors', 'password', this.passwordError(this.form.password));
                if (this.form.confirm_password) this.setError('errors', 'confirm_password', this.form.password === this.form.confirm_password ? '' : 'Passwords do not match.');
            }
            if (field === 'confirm_password') this.setError('errors', 'confirm_password', this.form.confirm_password && this.form.password === this.form.confirm_password ? '' : 'Passwords do not match.');
        },
        validateRegistration() {
            const nextErrors = {};
            if (!this.form.first_name.trim()) nextErrors.first_name = 'Enter your first name.';
            if (!this.form.last_name.trim()) nextErrors.last_name = 'Enter your last name.';
            const emailError = this.emailError(this.form.email, true); if (emailError) nextErrors.email = emailError;
            const passwordError = this.passwordError(this.form.password, true); if (passwordError) nextErrors.password = passwordError;
            if (!this.form.confirm_password) nextErrors.confirm_password = 'Confirm your password.';
            else if (this.form.password !== this.form.confirm_password) nextErrors.confirm_password = 'Passwords do not match.';
            if (!this.termsAccepted) nextErrors.terms = 'Read and accept the Terms & Conditions to continue.';
            this.errors = nextErrors;
            return Object.keys(nextErrors).length === 0;
        },
        openRecovery() { this.recoveryOpen = true; this.recoveryStep = 'email'; this.recoveryError = ''; this.recoveryErrors = {}; this.reset = {email: '', code: ['', '', '', '', '', ''], password: '', confirm_password: ''}; },
        async sendResetCode() {
            this.loading = true; this.recoveryError = ''; this.recoveryErrors = {}; const body = new FormData(); body.append('email', this.reset.email);
            try {
                const response = await fetch('<?= site_url('auth/forgot-password/send-code') ?>', {method: 'POST', body, headers: {'X-Requested-With': 'XMLHttpRequest'}});
                const responseText = await response.text();
                let data = {};
                try { data = responseText ? JSON.parse(responseText) : {}; } catch (parseError) { data = {}; }
                if (!response.ok) { this.recoveryErrors = data.errors || {}; this.recoveryError = this.recoveryErrors.form || 'We could not send the reset code. Please try again.'; return; }
                this.reset.code = ['', '', '', '', '', ''];
                this.recoveryStep = 'code';
                this.recoveryOpen = true;
                this.authOpen = true;
                this.authTab = 'signin';
                this.$nextTick(() => { const firstCodeBox = document.getElementById('reset-code-0'); if (firstCodeBox) firstCodeBox.focus(); });
            }
            catch (error) { this.recoveryError = 'Unable to contact the server. Please try again.'; } finally { this.loading = false; }
        },
        async verifyResetCode() {
            this.loading = true; this.recoveryError = ''; this.recoveryErrors = {}; const body = new FormData(); body.append('code', this.reset.code.join(''));
            try { const response = await fetch('<?= site_url('auth/forgot-password/verify-code') ?>', {method: 'POST', body, headers: {'X-Requested-With': 'XMLHttpRequest'}}); const data = await response.json(); if (!response.ok) { this.recoveryErrors = data.errors || {}; this.recoveryError = this.recoveryErrors.form || ''; return; } this.recoveryStep = 'password'; this.$nextTick(() => lucide.createIcons()); }
            catch (error) { this.recoveryError = 'Unable to contact the server. Please try again.'; } finally { this.loading = false; }
        },
        async completePasswordReset() {
            this.loading = true; this.recoveryError = ''; this.recoveryErrors = {}; const body = new FormData(); body.append('code', this.reset.code.join('')); body.append('password', this.reset.password); body.append('confirm_password', this.reset.confirm_password);
            try { const response = await fetch('<?= site_url('auth/forgot-password/reset-password') ?>', {method: 'POST', body, headers: {'X-Requested-With': 'XMLHttpRequest'}}); const data = await response.json(); if (!response.ok) { this.recoveryErrors = data.errors || {}; this.recoveryError = this.recoveryErrors.form || ''; return; } this.recoveryStep = 'success'; this.$nextTick(() => lucide.createIcons()); }
            catch (error) { this.recoveryError = 'Unable to contact the server. Please try again.'; } finally { this.loading = false; }
        },
        moveResetFocus(event,index) { const value = event.target.value.replace(/\D/g,'').slice(-1); this.reset.code[index] = value; if (value && index < 5) document.getElementById('reset-code-'+(index+1)).focus(); },
        backspaceReset(event,index) { if (!this.reset.code[index] && index > 0) document.getElementById('reset-code-'+(index-1)).focus(); },
        pasteResetCode(event) { const digits = event.clipboardData.getData('text').replace(/\D/g,'').slice(0,6).split(''); if (!digits.length) return; this.reset.code = Array.from({length:6},(_,i)=>digits[i]||''); this.$nextTick(()=>document.getElementById('reset-code-'+Math.min(digits.length,5)).focus()); },
        async sendCode() {
            this.formError = ''; if (!this.validateRegistration()) return; this.loading = true;
            const body = new FormData(); Object.entries(this.form).forEach(([key, value]) => body.append(key, value)); body.append('terms', this.termsAccepted ? '1' : '');
            try { const response = await fetch('<?= site_url('auth/adopter-register/send-code') ?>', {method: 'POST', body, headers: {'X-Requested-With': 'XMLHttpRequest'}}); const data = await response.json(); if (!response.ok) { this.errors = data.errors || {}; this.formError = this.errors.form || ''; return; } this.code = ['', '', '', '', '', '']; this.registrationStep = 'verify'; this.$nextTick(() => document.getElementById('verification-code-0').focus()); }
            catch (error) { this.formError = 'Unable to contact the server. Please try again.'; }
            finally { this.loading = false; }
        },
        async verifyCode() {
            this.loading = true; this.errors = {}; this.formError = '';
            const body = new FormData(); body.append('code', this.code.join(''));
            try { const response = await fetch('<?= site_url('auth/adopter-register/verify-code') ?>', {method: 'POST', body, headers: {'X-Requested-With': 'XMLHttpRequest'}}); const data = await response.json(); if (!response.ok) { this.errors = data.errors || {}; this.formError = this.errors.form || ''; return; } window.location.assign(data.redirect); }
            catch (error) { this.formError = 'Unable to contact the server. Please try again.'; }
            finally { this.loading = false; }
        },
        moveCodeFocus(event, index) { const value = event.target.value.replace(/\D/g, '').slice(-1); this.code[index] = value; if (value && index < 5) document.getElementById('verification-code-' + (index + 1)).focus(); },
        backspaceCode(event, index) { if (!this.code[index] && index > 0) { document.getElementById('verification-code-' + (index - 1)).focus(); } },
        pasteCode(event) { const digits = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6).split(''); if (!digits.length) return; this.code = Array.from({length: 6}, (_, index) => digits[index] || ''); this.$nextTick(() => document.getElementById('verification-code-' + Math.min(digits.length, 5)).focus()); }
    };
}
lucide.createIcons();
</script>
</body>
</html>
