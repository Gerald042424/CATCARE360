<?= $this->extend('layouts/adopter') ?>
<?= $this->section('adopter_content') ?>
<?php
$input = 'w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-rescue-500 focus:outline-none focus:ring-2 focus:ring-rescue-500/20';
$docs = [
    'selfie' => ['Live Selfie Verification', 'Use the front camera and complete the blink or head-turn challenge before capturing.', 'scan-face'],
    'gov_id' => ['Valid Government-Issued ID', 'Scan an original ID that belongs to the applicant and is unexpired when an expiry date applies.', 'credit-card'],
    'address_proof' => ['Proof of Address', 'Capture a current utility bill, lease, or bank statement with readable text.', 'file-text'],
    'home_photos' => ['Indoor Living Area', 'Capture a wide, well-lit view of the actual indoor area where the cat will live.', 'house'],
    'home_access' => ['Windows / Outdoor Access Area', 'Capture windows, doors, balcony, or outdoor access so staff can review escape-prevention measures.', 'door-open'],
];
$stepLabels = ['Personal Information'];
foreach (array_values($assessmentGroups) as $index => $_group) $stepLabels[] = 'Question ' . ($index + 1);
foreach ($docs as $key => $_document) {
    $stepLabels[] = match ($key) {
        'selfie' => 'Selfie',
        'gov_id' => 'Government ID',
        'address_proof' => 'Proof of Address',
        'home_photos' => 'Indoor Area',
        default => 'Access Area',
    };
}
?>

<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-12">
    <a href="<?= site_url('adopter/home') ?>" class="mb-6 inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-900"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to Cats</a>
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-slate-900">Adoption Application</h1>
        <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-500">Complete one short section at a time. Your answers are reviewed for the care plan they describe—not their length or writing style.</p>
    </div>

    <?php if (! empty($errors)): ?><div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4"><p class="mb-2 text-sm font-semibold text-red-700">Please review the following:</p><ul class="list-inside list-disc space-y-1 text-sm text-red-600"><?php foreach ($errors as $validationError): ?><li><?= esc($validationError) ?></li><?php endforeach ?></ul></div><?php endif ?>
    <?php if (session('error')): ?><div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700"><?= esc(session('error')) ?></div><?php endif ?>

    <form id="adoptionApplication" method="post" action="<?= site_url('adopter/apply') ?>" data-reverse-geocode-url="<?= esc(site_url('adopter/location/reverse')) ?>" enctype="multipart/form-data" class="overflow-visible rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="applicationWizard(<?= esc(json_encode($stepLabels), 'attr') ?>)">
        <?= csrf_field() ?>
        <input type="hidden" name="cat_id" value="<?= (int) $catId ?>">
        <input id="verificationMetadata" type="hidden" name="verification_metadata">

        <div class="sticky top-16 z-30 rounded-t-2xl border-b border-slate-200 bg-white/95 px-4 py-4 shadow-sm backdrop-blur sm:px-6">
            <div class="flex items-end justify-between gap-4">
                <div><p class="text-sm font-semibold text-slate-900" x-text="'Step ' + (step + 1) + ' of ' + total"></p><p class="mt-0.5 text-xs text-slate-500" x-text="progressText()"></p></div>
                <p class="max-w-[50%] text-right text-sm font-semibold text-rescue-700" x-text="labels[step]"></p>
            </div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100" aria-hidden="true"><div class="h-full rounded-full bg-rescue-600 transition-[width] duration-300 motion-reduce:transition-none" :style="'width:' + progressPercent() + '%'"></div></div>
            <div class="wizard-step-scroll mt-4 overflow-x-auto pb-1">
                <ol class="flex min-w-max items-start gap-2" aria-label="Application progress">
                    <template x-for="(label, index) in labels" :key="index">
                        <li class="flex items-center" :data-step-index="index">
                            <div class="flex w-12 flex-col items-center gap-1.5 text-center sm:w-14">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full border text-xs font-bold transition-colors" :class="completed[index] ? 'border-adopted-600 bg-adopted-600 text-white' : (step === index ? 'border-rescue-600 bg-rescue-600 text-white ring-4 ring-rescue-100' : 'border-slate-200 bg-white text-slate-400')"><i x-show="completed[index]" data-lucide="check" class="h-4 w-4"></i><span x-show="!completed[index]" x-text="index + 1"></span></span>
                                <span class="hidden max-w-14 text-[10px] font-medium leading-3 sm:block" :class="step === index ? 'text-rescue-700' : 'text-slate-400'" x-text="label"></span>
                            </div>
                            <span x-show="index < total - 1" class="mb-5 h-px w-3 bg-slate-200 sm:w-5"></span>
                        </li>
                    </template>
                </ol>
            </div>
        </div>

        <div class="p-5 sm:p-8">
            <section data-wizard-panel="0" x-show="step === 0" x-transition.opacity.duration.150ms>
                <div class="mb-6 flex items-start gap-3 border-b border-slate-100 pb-4"><span class="rounded-lg bg-rescue-50 p-2 text-rescue-700"><i data-lucide="user-circle" class="h-5 w-5"></i></span><div><h2 class="text-xl font-semibold text-slate-900">Personal Information</h2><p class="mt-1 text-sm text-slate-500">Confirm your contact information and the cat you want to adopt.</p></div></div>
                <div class="space-y-5">
                    <div><label class="mb-2 block text-sm font-medium">Full Name *</label><input required name="full_name" value="<?= esc(old('full_name', $adopter['name'] ?? '')) ?>" class="<?= $input ?>"></div>
                    <div><label class="mb-2 block text-sm font-medium">Address *</label><input required name="address" value="<?= esc(old('address', $adopter['address'] ?? '')) ?>" class="<?= $input ?>"></div>
                    <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-2 block text-sm font-medium">Phone Number *</label><input required type="tel" inputmode="tel" autocomplete="tel" maxlength="13" pattern="(?:09\d{9}|\+639\d{9})" title="Use 09XXXXXXXXX or +639XXXXXXXXX" name="phone" value="<?= esc(old('phone', $adopter['phone'] ?? '')) ?>" @input="$el.value = $el.value.replace(/[^\d+]/g, '').replace(/(?!^)\+/g, '')" placeholder="09XXXXXXXXX" class="<?= $input ?>"><p class="mt-1 text-xs text-slate-500">Numbers only. Use 09XXXXXXXXX or +639XXXXXXXXX.</p></div><div><label class="mb-2 block text-sm font-medium">Occupation *</label><input required name="occupation" value="<?= esc(old('occupation', $adopter['occupation'] ?? '')) ?>" class="<?= $input ?>"></div></div>
                    <div><label class="mb-2 block text-sm font-medium">Specific Cat *</label><?php if ($cat): ?><div class="rounded-xl border border-rescue-100 bg-rescue-50 p-3 font-semibold text-rescue-900"><?= esc($cat['name']) ?> <a class="ml-3 text-sm text-rescue-600" href="<?= site_url('adopter/apply') ?>">Change</a></div><?php else: ?><input required name="cat_description" value="<?= esc(old('cat_description')) ?>" placeholder="Cat name, color, or location" class="<?= $input ?>"><?php endif ?></div>
                </div>
            </section>

            <?php $assessmentKeys = array_keys($assessmentGroups); ?>
            <?php foreach ($assessmentGroups as $groupKey => $group): ?>
                <?php $assessmentStep = 1 + array_search($groupKey, $assessmentKeys, true); ?>
                <section data-wizard-panel="<?= $assessmentStep ?>" x-cloak x-show="step === <?= $assessmentStep ?>" x-transition.opacity.duration.150ms>
                    <div class="mb-6 flex items-start gap-3 border-b border-slate-100 pb-4"><span class="rounded-lg bg-rescue-50 p-2 text-rescue-700"><i data-lucide="heart-handshake" class="h-5 w-5"></i></span><div><p class="text-xs font-semibold text-rescue-700">Assessment question <?= $assessmentStep ?> of <?= count($assessmentGroups) ?></p><h2 class="mt-1 text-xl font-semibold text-slate-900"><?= esc($group['title']) ?></h2><p class="mt-1 text-sm text-slate-500">Answer the main question and its four related questions before continuing.</p></div></div>
                    <fieldset>
                        <legend class="sr-only"><?= esc($group['title']) ?></legend>
                        <div class="rounded-xl border border-rescue-100 bg-rescue-50/60 p-4"><label for="assessment_<?= esc($groupKey) ?>_main" class="block text-sm font-semibold text-rescue-900">Main question</label><p class="mt-2 text-sm leading-6 text-slate-700"><?= esc($group['question']) ?></p><textarea id="assessment_<?= esc($groupKey) ?>_main" required maxlength="3000" name="assessment_<?= esc($groupKey) ?>_main" rows="4" class="<?= $input ?> mt-3 resize-y" placeholder="Your main answer…"><?= esc(old('assessment_' . $groupKey . '_main')) ?></textarea></div>
                        <div class="mt-5 space-y-5"><?php foreach (($group['subquestions'] ?? []) as $index => $subquestion): ?><div><label for="assessment_<?= esc($groupKey) ?>_sub_<?= $index + 1 ?>" class="block text-sm font-medium text-slate-800"><span class="mr-1 text-rescue-700"><?= $index + 1 ?>.</span><?= esc($subquestion) ?></label><textarea id="assessment_<?= esc($groupKey) ?>_sub_<?= $index + 1 ?>" required maxlength="2000" name="assessment_<?= esc($groupKey) ?>_sub_<?= $index + 1 ?>" rows="3" class="<?= $input ?> mt-2 resize-y" placeholder="Your answer…"><?= esc(old('assessment_' . $groupKey . '_sub_' . ($index + 1))) ?></textarea></div><?php endforeach ?></div>
                    </fieldset>
                </section>
            <?php endforeach ?>

            <div id="liveVerification">
                <div class="sr-only"><h2>Live Verification Photos</h2><p>Camera only. Each photo is stamped with date, time, address, and GPS location.</p></div>
                <div id="captureError" class="mb-4 hidden rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700"></div>
                <?php $documentStart = 1 + count($assessmentGroups); $documentKeys = array_keys($docs); ?>
                <?php foreach ($docs as $documentKey => $document): ?>
                    <?php $documentNumber = array_search($documentKey, $documentKeys, true); $documentStep = $documentStart + $documentNumber; [$label, $help, $icon] = $document; ?>
                    <article data-wizard-panel="<?= $documentStep ?>" x-cloak x-show="step === <?= $documentStep ?>" x-transition.opacity.duration.150ms class="verification-capture" data-key="<?= esc($documentKey) ?>">
                        <div class="mb-6 flex items-start justify-between gap-3 border-b border-slate-100 pb-4"><div class="flex items-start gap-3"><span class="rounded-lg bg-rescue-50 p-2 text-rescue-700"><i data-lucide="<?= esc($icon) ?>" class="h-5 w-5"></i></span><div><p class="text-xs font-semibold text-rescue-700">Live verification <?= $documentNumber + 1 ?> of <?= count($docs) ?></p><h2 class="mt-1 text-xl font-semibold text-slate-900"><?= esc($label) ?></h2><p class="mt-1 text-sm leading-6 text-slate-500"><?= esc($help) ?></p></div></div><span class="capture-state shrink-0 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Not captured</span></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><input class="capture-file" type="file" name="verification_documents[<?= esc($documentKey) ?>]" accept="image/jpeg" hidden><video class="camera-stream hidden max-h-[62vh] w-full rounded-lg bg-black object-contain" autoplay playsinline muted></video><canvas class="capture-canvas hidden"></canvas><button type="button" class="capture-preview-wrap hidden w-full overflow-hidden rounded-lg border border-adopted-200 bg-white text-left"><img class="capture-preview block max-h-[62vh] w-full object-contain" alt="Captured verification photo"><span class="block bg-adopted-50 px-3 py-2 text-xs font-medium text-adopted-800">Photo captured. Click to zoom and check it before continuing.</span></button><div class="mt-4 flex flex-wrap gap-2"><button type="button" class="start-camera rounded-lg bg-rescue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rescue-700">Open camera</button><button type="button" class="switch-camera hidden rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Switch front/back</button><button type="button" class="capture-photo rounded-lg bg-adopted-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" disabled>Capture photo</button><button type="button" class="retake-photo hidden rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800">Retake</button></div><p class="location-status mt-3 text-xs font-medium text-slate-500">Open the camera, then capture and allow location access.</p></div>
                    </article>
                <?php endforeach ?>
            </div>

            <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <button x-show="step > 0" type="button" @click="previous()" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="arrow-left" class="h-4 w-4"></i>Previous</button>
                <span x-show="step === 0" class="hidden sm:block"></span>
                <button x-show="step < total - 1" type="button" @click="next()" class="inline-flex items-center justify-center gap-2 rounded-lg bg-rescue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rescue-700">Continue<i data-lucide="arrow-right" class="h-4 w-4"></i></button>
                <button x-cloak x-show="step === total - 1" type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-rescue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rescue-700 disabled:cursor-wait disabled:opacity-75"><i data-lucide="send" class="h-4 w-4"></i><span>Submit Application</span></button>
            </div>
        </div>
    </form>
</div>

<style>.wizard-step-scroll{scrollbar-width:thin;scrollbar-color:#B8DED2 transparent}.wizard-step-scroll::-webkit-scrollbar{height:4px}.wizard-step-scroll::-webkit-scrollbar-thumb{background:#B8DED2;border-radius:999px}</style>

<script>
function applicationWizard(labels) {
    return {
        step: 0, labels: labels, total: labels.length, completed: labels.map(function () { return false; }),
        progressPercent: function () { return Math.round((this.completed.filter(Boolean).length / this.total) * 100); },
        progressText: function () { var percent = this.progressPercent(); return percent === 0 ? 'Start with the details below.' : (percent === 100 ? 'Application sections complete.' : "You're " + percent + '% complete.'); },
        currentPanel: function () { return this.$root.querySelector('[data-wizard-panel="' + this.step + '"]'); },
        validateCurrent: function () {
            var panel = this.currentPanel(); if (!panel) return false;
            var fields = Array.prototype.slice.call(panel.querySelectorAll('input:not([type="hidden"]), textarea, select'));
            for (var index = 0; index < fields.length; index += 1) { if (!fields[index].checkValidity()) { fields[index].reportValidity(); fields[index].scrollIntoView({ behavior: 'smooth', block: 'center' }); return false; } }
            var captureCard = panel.matches('.verification-capture') ? panel : panel.querySelector('.verification-capture');
            if (captureCard && captureCard.querySelector('.capture-file').files.length !== 1) { var captureError = document.getElementById('captureError'); captureError.textContent = 'Capture and review this required photo before continuing.'; captureError.classList.remove('hidden'); captureCard.scrollIntoView({ behavior: 'smooth', block: 'center' }); return false; }
            var errorBox = document.getElementById('captureError'); if (errorBox) errorBox.classList.add('hidden'); return true;
        },
        next: function () { if (!this.validateCurrent()) return; this.completed[this.step] = true; this.completed = this.completed.slice(); if (this.step < this.total - 1) this.step += 1; this.focusStep(); },
        previous: function () { if (this.step > 0) this.step -= 1; this.focusStep(); },
        focusStep: function () { var form = this.$root, current = this.step; this.$nextTick(function () { form.scrollIntoView({ behavior: 'smooth', block: 'start' }); var marker = form.querySelector('[data-step-index="' + current + '"]'); if (marker) marker.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' }); if (window.lucide) window.lucide.createIcons(); }); }
    };
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var cats = <?= json_encode(array_map(static fn (array $item): array => ['id' => (int) $item['id'], 'name' => $item['name'], 'breed' => $item['breed'], 'age' => $item['age'], 'image' => $item['image_url'] ? base_url($item['image_url']) : 'https://images.unsplash.com/photo-1574158622682-e40ad296ce3f?auto=format&fit=crop&w=160&q=80'], $availableCats), JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var initial = <?= (int) $catId ?>;
    var label = Array.from(document.querySelectorAll('label')).find(function (item) { return item.textContent.trim().indexOf('Specific Cat') === 0; });
    if (!label) return;
    var holder = label.parentElement;
    var selected = initial ? [initial] : [];
    function esc(value) { var node = document.createElement('span'); node.textContent = value || ''; return node.innerHTML; }
    function render() {
        var chosen = cats.filter(function (cat) { return selected.indexOf(cat.id) !== -1; });
        holder.innerHTML = '<div class="mb-2 flex items-center justify-between"><label class="block text-sm font-medium">Specific Cat <span class="text-red-500">*</span></label><button type="button" id="addAnotherCat" class="text-sm font-semibold text-rescue-600">Add another cat</button></div><p class="mb-3 text-xs text-slate-500">Choose one or more available cats for this application.</p><div id="selectedCats" class="space-y-2"></div><div id="catChooser" class="mt-3 hidden rounded-xl border border-slate-200 bg-slate-50 p-3"><div class="grid gap-2 sm:grid-cols-2"></div></div>';
        var selectedBox = document.getElementById('selectedCats');
        chosen.forEach(function (cat) { selectedBox.insertAdjacentHTML('beforeend', '<div class="flex items-center gap-3 rounded-xl border border-rescue-100 bg-rescue-50 p-3"><input type="hidden" name="cat_ids[]" value="' + cat.id + '"><img src="' + esc(cat.image) + '" class="h-12 w-12 rounded-lg border border-rescue-200 object-cover" alt="' + esc(cat.name) + '"><div class="min-w-0"><p class="font-semibold text-rescue-950">' + esc(cat.name) + '</p><p class="text-xs text-rescue-700">' + esc(cat.breed) + ' · ' + esc(cat.age) + '</p></div><button type="button" data-remove="' + cat.id + '" class="ml-auto rounded-lg p-2 text-red-600 hover:bg-red-100">Remove</button></div>'); });
        if (!chosen.length) selectedBox.innerHTML = '<input required name="cat_description" placeholder="Choose a cat below, or enter its name/color/location" class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm">';
        var grid = document.querySelector('#catChooser .grid');
        cats.forEach(function (cat) { var active = selected.indexOf(cat.id) !== -1; grid.insertAdjacentHTML('beforeend', '<button type="button" data-pick="' + cat.id + '" class="flex items-center gap-3 rounded-lg border bg-white p-2 text-left ' + (active ? 'border-rescue-500' : 'border-slate-200') + '"><img src="' + esc(cat.image) + '" class="h-10 w-10 rounded object-cover"><span><b class="block text-sm">' + esc(cat.name) + '</b><small class="text-slate-500">' + esc(cat.breed) + '</small></span></button>'); });
        document.getElementById('addAnotherCat').onclick = function () { document.getElementById('catChooser').classList.toggle('hidden'); };
        holder.querySelectorAll('[data-remove]').forEach(function (button) { button.onclick = function () { selected = selected.filter(function (id) { return id !== Number(button.dataset.remove); }); render(); }; });
        holder.querySelectorAll('[data-pick]').forEach(function (button) { button.onclick = function () { var id = Number(button.dataset.pick); if (selected.indexOf(id) === -1) selected.push(id); render(); document.getElementById('catChooser').classList.remove('hidden'); }; });
    }
    render();
});
</script>

<script><?= file_get_contents(FCPATH . 'js/adoption-live-capture.js') ?></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('adoptionApplication');
    var button = form && form.querySelector('button[type="submit"]');
    if (!form || !button) return;
    button.addEventListener('click', function (event) {
        event.preventDefault(); event.stopImmediatePropagation();
        if (!form.reportValidity()) return;
        var cards = form.querySelectorAll('.verification-capture');
        var valid = cards.length === 5 && Array.prototype.every.call(cards, function (card) { return card.querySelector('.capture-file').files.length === 1; }) && document.getElementById('verificationMetadata').value;
        if (!valid) { var error = document.getElementById('captureError'); error.textContent = 'Capture all five live verification photos before submitting.'; error.classList.remove('hidden'); document.getElementById('liveVerification').scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
        button.disabled = true; button.querySelector('span').textContent = 'Submitting application…'; HTMLFormElement.prototype.submit.call(form);
    }, true);
});
</script>
<?= $this->endSection() ?>
