<?= $this->extend('layouts/adopter') ?>
<?= $this->section('adopter_content') ?>

<div class="min-h-screen grid place-items-center px-6 py-16">
    <div class="max-w-lg w-full">
        <h1 class="text-3xl font-semibold text-slate-900 mb-2">Create an adopter account</h1>
        <p class="text-slate-500 mb-6">Sign up to apply for adoption and manage your adopted pet.</p>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-100 rounded-lg text-red-700"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('adopter/register') ?>" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="text-sm font-medium text-slate-700 mb-2 block">First name</label>
                    <input name="first_name" value="<?= old('first_name') ?>" required class="w-full bg-white border border-slate-200 rounded-lg py-2.5 px-4 text-sm text-slate-900">
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700 mb-2 block">Last name</label>
                    <input name="last_name" value="<?= old('last_name') ?>" required class="w-full bg-white border border-slate-200 rounded-lg py-2.5 px-4 text-sm text-slate-900">
                </div>
            </div>

            <div class="mb-4">
                <label class="text-sm font-medium text-slate-700 mb-2 block">Email</label>
                <input type="email" name="email" value="<?= old('email') ?>" required class="w-full bg-white border border-slate-200 rounded-lg py-2.5 px-4 text-sm text-slate-900">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="text-sm font-medium text-slate-700 mb-2 block">Password</label>
                    <input type="password" name="password" required class="w-full bg-white border border-slate-200 rounded-lg py-2.5 px-4 text-sm text-slate-900">
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700 mb-2 block">Confirm Password</label>
                    <input type="password" name="confirm_password" required class="w-full bg-white border border-slate-200 rounded-lg py-2.5 px-4 text-sm text-slate-900">
                </div>
            </div>

            <div class="flex items-center gap-3 mb-4">
                <input type="checkbox" name="terms" id="terms" class="h-4 w-4">
                <label for="terms" class="text-sm text-slate-600">I agree to the terms and conditions</label>
            </div>

            <div class="flex items-center justify-between">
                <a href="<?= site_url('/') ?>" class="text-sm text-slate-500">Already have an account?</a>
                <button type="submit" class="bg-rescue-600 hover:bg-rescue-700 text-white px-5 py-2.5 rounded-lg">Create account</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
