<?= $this->extend('layouts/adopter') ?>
<?= $this->section('adopter_content') ?>
<?php
$verificationMetadata = ! empty($portalApplication)
    ? (json_decode((string) ($portalApplication['verification_metadata'] ?? ''), true) ?: [])
    : [];
$applicationDocuments = ! empty($portalApplication) ? [
    'Verified Live Selfie' => $portalApplication['selfie_path'] ?? null,
    'Valid Government-Issued ID' => $portalApplication['gov_id_path'] ?? null,
    'Proof of Address' => $portalApplication['address_proof_path'] ?? null,
    'Indoor Living Area' => $portalApplication['home_photos_path'] ?? null,
    'Windows / Outdoor Access Area' => $verificationMetadata['home_access']['stored_path'] ?? null,
] : [];
?>

<div x-data="{ applicationOpen: false, catOpen: false, updatePhoto: '', updatePhotoLabel: '' }" class="max-w-6xl mx-auto px-6 py-12">
    <a href="<?= site_url('adopter/portal') ?>" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-rescue-600 hover:text-rescue-700"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to My Adoptions</a>
    <div class="mb-10">
        <h1 class="text-3xl font-semibold text-slate-900 mb-2">My Adoption Dashboard</h1>
        <p class="text-slate-500 font-medium">Track your adopted cat's journey and submit updates</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="bg-white border-2 border-adopted-200 bg-adopted-50 rounded-2xl shadow-sm p-6">
            <div class="flex items-start justify-between mb-4"><i data-lucide="award" class="w-8 h-8 text-adopted-600"></i><span class="text-xs font-semibold uppercase tracking-wider px-2 py-1 rounded bg-adopted-50 text-adopted-600">Status</span></div>
            <p class="text-sm font-medium text-slate-500 mb-1">Compliance Score</p>
            <p class="text-4xl font-bold text-adopted-600"><?= esc($adopter['compliance_score'] ?? 92) ?>%</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6"><i data-lucide="check-circle-2" class="w-8 h-8 text-rescue-600 mb-4"></i><p class="text-sm font-medium text-slate-500 mb-1">Total Updates</p><p class="text-4xl font-bold text-slate-900"><?= esc($totalUpdates ?? 0) ?></p></div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6"><i data-lucide="calendar" class="w-8 h-8 text-purple-600 mb-4"></i><p class="text-sm font-medium text-slate-500 mb-1">Next Check-In</p><p class="text-lg font-semibold text-slate-900"><?= esc($nextCheckIn ?? date('M d')) ?></p></div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6"><i data-lucide="alert-circle" class="w-8 h-8 text-green-600 mb-4"></i><p class="text-sm font-medium text-slate-500 mb-1">Pending</p><p class="text-4xl font-bold text-green-600"><?= esc($pendingCount ?? 0) ?></p></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden group">
                <div class="aspect-[4/3] relative overflow-hidden group"><img src="<?= esc(!empty($cat['image_url']) ? base_url($cat['image_url']) : 'https://images.unsplash.com/photo-1574158622682-e40ad296ce3f?auto=format&fit=crop&w=400&q=80') ?>" class="w-full h-full object-cover"><div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent flex flex-col justify-end p-4"><h3 class="text-xl font-semibold text-white"><?= esc($cat['name'] ?? 'No pet') ?></h3><p class="text-sm text-rescue-100"><?= esc($cat['breed'] ?? '') ?></p></div><div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-full text-xs font-semibold text-slate-900 shadow-sm">CAT-<?= str_pad($cat['id'] ?? 0,5,'0',STR_PAD_LEFT) ?></div></div>
                <div class="p-5">
                    <div class="flex items-center gap-2 mb-4 flex-wrap"><span class="inline-flex items-center gap-1 text-xs font-medium text-rescue-700 bg-rescue-50 border border-rescue-100 px-2 py-0.5 rounded-full"><?= esc($cat['gender'] ?? '') ?></span><span class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full"><?= esc($cat['age'] ?? '') ?></span></div>
                    <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden text-sm"><div class="flex justify-between p-3 bg-slate-50"><span class="text-slate-500 font-medium">Adoption Date</span><span class="text-slate-900 font-semibold"><?= ! empty($cat['adoption_date']) ? esc(date('M d, Y', strtotime($cat['adoption_date']))) : 'Not recorded' ?></span></div><div class="flex justify-between p-3"><span class="text-slate-500 font-medium">Vaccinated</span><i data-lucide="check-circle-2" class="w-5 h-5 text-adopted-600"></i></div><div class="flex justify-between p-3 bg-slate-50"><span class="text-slate-500 font-medium">Neutered</span><i data-lucide="check-circle-2" class="w-5 h-5 text-adopted-600"></i></div><div class="flex justify-between p-3"><span class="text-slate-500 font-medium">Microchipped</span><i data-lucide="check-circle-2" class="w-5 h-5 text-adopted-600"></i></div></div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <button type="button" @click="applicationOpen = true" <?= empty($portalApplication) ? 'disabled' : '' ?> class="w-full bg-rescue-50 text-rescue-700 py-3 rounded-lg font-medium text-sm hover:bg-rescue-100 transition-colors flex items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-50"><i data-lucide="file-text" class="w-4 h-4"></i><?= empty($portalApplication) ? 'Application Unavailable' : 'View Full Application' ?></button>
                    <button type="button" @click="catOpen = true" class="w-full bg-rescue-50 text-rescue-700 py-3 rounded-lg font-medium text-sm hover:bg-rescue-100 transition-colors flex items-center justify-center gap-2"><i data-lucide="cat" class="w-4 h-4"></i>View Cat Profile</button>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">3-3-3 Check-ins</h3>
                <div class="grid grid-cols-3 gap-3 text-center text-xs"><?php foreach (['3-day','3-week','3-month'] as $checkpoint): ?><?php $done = in_array($checkpoint, $completedCheckpoints ?? [], true); ?><div class="rounded-xl border p-3 <?= $done ? 'border-adopted-100 bg-adopted-50 text-adopted-700' : 'border-slate-200 bg-slate-50 text-slate-600' ?>"><i data-lucide="<?= $done ? 'check-circle-2' : 'clock-3' ?>" class="mx-auto mb-1 h-5 w-5"></i><p class="font-semibold"><?= esc($checkpoint) ?></p><p class="mt-1 text-slate-500"><?= !empty($checkpoints[$checkpoint]) ? date('M d', strtotime($checkpoints[$checkpoint])) : '—' ?></p></div><?php endforeach; ?></div>
                <?php if (!empty($nextCheckpoint)): ?><p class="mt-4 rounded-lg bg-rescue-50 p-3 text-sm text-rescue-800">Your next required update is <strong><?= esc($nextCheckpoint) ?></strong>, due <?= esc(date('F d, Y', strtotime($nextCheckIn))) ?>.</p><?php endif; ?>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Submit Update</h3>
                <form id="updateForm" method="post" action="<?= site_url('adopter/updates/submit') ?>" enctype="multipart/form-data" data-reverse-geocode-url="<?= esc(site_url('adopter/location/reverse')) ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="cat_id" value="<?= esc($cat['id'] ?? '') ?>">
                    <div>
                        <label class="text-sm font-medium text-slate-700 mb-2 block">Update Type</label>
                        <select name="update_type" class="w-full bg-white border border-slate-200 rounded-lg py-2.5 px-4 text-sm text-slate-900 shadow-sm">
                            <option value="photo">Photo Update</option>
                            <option value="health">Health Check</option>
                            <option value="behavior">Behavior Report</option>
                            <option value="general">General Update</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700 mb-2 block">Live Camera Photo (Required)</label>
                        <input id="photoInput" type="file" name="photo" accept="image/*" class="hidden">

                        <div id="cameraArea" class="mt-2">
                            <video id="cameraStream" class="w-full max-h-64 rounded-lg object-cover border border-slate-200 bg-black hidden" autoplay playsinline></video>
                            <canvas id="captureCanvas" class="hidden"></canvas>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" id="startCameraBtn" class="flex items-center gap-2 bg-rescue-50 text-rescue-700 px-4 py-2 rounded-lg font-medium text-sm hover:bg-rescue-100"><i data-lucide="camera" class="w-4 h-4"></i>Open Camera</button>
                                <button type="button" id="switchCameraBtn" class="hidden flex items-center gap-2 bg-violet-50 text-violet-700 px-4 py-2 rounded-lg font-medium text-sm hover:bg-violet-100"><i data-lucide="switch-camera" class="w-4 h-4"></i>Switch Front / Back</button>
                                <button type="button" id="captureBtn" disabled class="bg-adopted-600 text-white px-4 py-2 rounded-lg font-medium text-sm hover:bg-adopted-700">Capture</button>
                                <button type="button" id="retakeBtn" class="hidden bg-slate-100 text-slate-800 px-4 py-2 rounded-lg font-medium text-sm hover:bg-slate-200">Retake</button>
                            </div>
                            <div id="cameraHelp" class="mt-2 text-sm text-red-600 hidden">Camera access is required. Allow it from the lock icon in the address bar, then try again. Gallery uploads are disabled for verification.</div>
                            <div id="photoError" class="mt-2 text-sm text-red-600 hidden"></div>

                            <div class="mt-3">
                                <img id="photoPreview" src="" alt="Preview — click to view fullscreen" class="hidden w-full max-h-64 cursor-zoom-in rounded-lg border border-slate-200 object-cover">
                            </div>

                            <p class="mt-2 text-xs text-slate-500">For authenticity, only a live photo captured on this page is accepted. The final photo is stamped with the device’s current GPS place, coordinates, date, time, and measured accuracy—not the profile address.</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700 mb-2 block">Description</label>
                        <textarea name="description" rows="4" class="w-full bg-white border border-slate-200 rounded-lg py-2.5 px-4 text-sm text-slate-900 shadow-sm" placeholder="Tell us how your cat is doing..."></textarea>
                    </div>
                    <input type="hidden" name="latitude" id="latitudeInput">
                    <input type="hidden" name="longitude" id="longitudeInput">
                    <input type="hidden" name="gps_accuracy" id="gpsAccuracyInput">
                    <input type="hidden" name="capture_source" id="captureSourceInput">
                    <input type="hidden" name="device_info" id="deviceInfoInput">
                    <div class="flex items-center gap-3">
                        <button type="button" id="getLocationBtn" class="flex items-center gap-2 text-sm font-medium text-rescue-600 bg-rescue-50 px-4 py-2 rounded-lg hover:bg-rescue-100 transition-colors">
                            <i data-lucide="map-pin" class="w-4 h-4"></i>Get My Location
                        </button>
                        <p id="locationStatus" class="text-xs text-slate-500 hidden flex items-center gap-1"><i data-lucide="check-circle-2" class="w-4 h-4"></i><span id="locationStatusText">Location captured ✓</span></p>
                    </div>
                    <button type="submit" class="w-full bg-rescue-600 hover:bg-rescue-700 text-white px-6 py-3 rounded-lg font-medium">Submit Update</button>
                </form>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6"><h3 class="text-lg font-semibold text-slate-900">Recent Updates</h3><i data-lucide="trending-up" class="w-5 h-5 text-adopted-600"></i></div>
                <div class="space-y-3">
                    <?php if (! empty($recentUpdates)): foreach ($recentUpdates as $update): ?>
                    <div class="overflow-hidden rounded-xl border border-slate-200 hover:border-slate-300 transition-colors <?= ! empty($update['photo_url']) ? 'sm:grid sm:grid-cols-[11rem_1fr]' : '' ?>">
                        <?php if (! empty($update['photo_url'])): ?>
                        <button type="button" data-photo-url="<?= esc(base_url($update['photo_url']), 'attr') ?>" data-photo-label="<?= esc($update['checkpoint'] . ' monitoring photo', 'attr') ?>" @click="updatePhoto = $el.dataset.photoUrl; updatePhotoLabel = $el.dataset.photoLabel" class="group relative min-h-40 overflow-hidden bg-slate-100 text-left" aria-label="View <?= esc($update['checkpoint']) ?> monitoring photo fullscreen">
                            <img src="<?= esc(base_url($update['photo_url'])) ?>" alt="<?= esc($update['checkpoint']) ?> monitoring update" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            <span class="absolute bottom-2 right-2 rounded-lg bg-black/65 p-2 text-white"><i data-lucide="maximize-2" class="h-4 w-4"></i></span>
                        </button>
                        <?php endif; ?>
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-2"><p class="text-sm font-semibold text-slate-900"><?= esc($update['checkpoint']) ?> · <?= esc(ucfirst($update['health_status'])) ?></p><?= statusBadge($update['status']) ?></div>
                            <p class="text-xs text-slate-500 mb-2"><?= date('M d, Y h:i A', strtotime($update['submission_date'])) ?></p>
                            <p class="text-sm leading-6 text-slate-700"><?= esc($update['behavior_notes']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <p class="text-center py-8 text-slate-500 text-sm">No updates yet. Submit your first update!</p>
                    <?php endif; ?>
                </div>
                <?php if (! empty($recentUpdates) && count($recentUpdates) > 3): ?><button class="w-full mt-4 py-2.5 border border-slate-200 text-slate-700 rounded-lg font-medium text-sm hover:bg-slate-50 transition-colors">View All Updates</button><?php endif; ?>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-rescue-50 border border-purple-100 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Compliance Guidelines</h3>
                <div class="space-y-3">
                    <?php $guidelines = ['Submit monthly updates during the 3-3-3 monitoring period','Include at least one photo per update to verify the cat\'s wellbeing','Enable location services for geo-tagged verification','Report any health or behavioral concerns immediately']; foreach ($guidelines as $i=>$g): ?>
                    <div class="flex items-start gap-3"><div class="w-6 h-6 bg-white rounded-full flex items-center justify-center"><p class="text-xs font-bold text-purple-600"><?= $i+1 ?></p></div><p class="text-sm text-slate-700"><?= $g ?></p></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (! empty($portalApplication)): ?>
    <div x-cloak x-show="applicationOpen" x-transition.opacity @keydown.escape.window="applicationOpen = false" @click.self="applicationOpen = false" class="fixed inset-0 z-[180] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
        <section class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="application-modal-title">
            <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6"><div><p class="text-xs font-bold uppercase tracking-[.14em] text-rescue-600"><?= esc($portalApplication['application_id'] ?? 'Application') ?></p><h2 id="application-modal-title" class="mt-1 text-xl font-semibold text-slate-900">Full Adoption Application</h2><p class="mt-1 text-sm text-slate-500">Submitted <?= ! empty($portalApplication['submitted_at']) ? esc(date('F d, Y h:i A', strtotime($portalApplication['submitted_at']))) : 'date unavailable' ?></p></div><button type="button" @click="applicationOpen = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close full application"><i data-lucide="x" class="h-5 w-5"></i></button></header>
            <div class="space-y-6 overflow-y-auto p-5 sm:p-6">
                <section><div class="flex items-center justify-between gap-3"><h3 class="font-semibold text-slate-900">Application Summary</h3><?= statusBadge($portalApplication['status'] ?? 'Under Review') ?></div><dl class="mt-4 grid gap-4 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2"><div><dt class="text-slate-500">Full name</dt><dd class="mt-1 font-semibold text-slate-900"><?= esc($portalApplication['full_name'] ?? '—') ?></dd></div><div><dt class="text-slate-500">Phone</dt><dd class="mt-1 font-semibold text-slate-900"><?= esc($portalApplication['phone'] ?? '—') ?></dd></div><div><dt class="text-slate-500">Occupation</dt><dd class="mt-1 font-semibold text-slate-900"><?= esc($portalApplication['occupation'] ?? '—') ?></dd></div><div><dt class="text-slate-500">Assessment score</dt><dd class="mt-1 font-semibold text-slate-900"><?= isset($portalApplication['assessment_score']) ? esc(number_format((float) $portalApplication['assessment_score'], 2)) . '%' : '—' ?></dd></div><div class="sm:col-span-2"><dt class="text-slate-500">Address</dt><dd class="mt-1 font-semibold text-slate-900"><?= esc($portalApplication['address'] ?? '—') ?></dd></div></dl></section>
                <?php if (! empty($portalApplication['question_groups'])): ?>
                <section>
                    <h3 class="font-semibold text-slate-900">Application Answers</h3>
                    <p class="mt-1 text-sm text-slate-500">Each numbered section shows one main question followed by its four subquestions.</p>
                    <div class="mt-4 space-y-5">
                        <?php foreach ($portalApplication['question_groups'] as $group): ?>
                        <article class="overflow-hidden rounded-2xl border border-slate-200">
                            <header class="border-b border-rescue-100 bg-rescue-50 px-4 py-3 sm:px-5"><h4 class="font-semibold text-rescue-900"><?= esc($group['title']) ?></h4></header>
                            <div class="space-y-4 p-4 sm:p-5">
                                <div class="rounded-xl border border-rescue-100 bg-rescue-50/50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wider text-rescue-600">Main Question</p>
                                    <p class="mt-1 text-sm font-semibold leading-6 text-slate-900"><?= esc($group['question']) ?></p>
                                    <div class="mt-3 rounded-lg bg-white p-3 text-sm leading-6 text-slate-700 shadow-sm"><?= esc($group['main_answer'] ?: 'Not provided') ?></div>
                                </div>
                                <ol class="space-y-3">
                                    <?php foreach ($group['subquestions'] as $subquestionIndex => $subquestion): ?>
                                    <li class="rounded-xl border border-slate-200 p-4">
                                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Subquestion <?= $subquestionIndex + 1 ?></p>
                                        <p class="mt-1 text-sm font-semibold leading-6 text-slate-900"><?= esc($subquestion['question']) ?></p>
                                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700"><?= esc($subquestion['answer'] ?: 'Not provided') ?></p>
                                    </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                <?php if (array_filter($applicationDocuments)): ?><section><h3 class="font-semibold text-slate-900">Verification Documents</h3><div class="mt-3 grid gap-3 sm:grid-cols-2"><?php foreach ($applicationDocuments as $label => $path): ?><?php if ($path): ?><button type="button" data-photo-url="<?= esc(base_url($path), 'attr') ?>" data-photo-label="<?= esc($label, 'attr') ?>" @click="updatePhoto = $el.dataset.photoUrl; updatePhotoLabel = $el.dataset.photoLabel" class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-left text-sm font-semibold text-slate-800 hover:border-rescue-300 hover:bg-rescue-50"><i data-lucide="file-check-2" class="h-5 w-5 text-rescue-600"></i><span class="min-w-0 flex-1"><?= esc($label) ?></span><i data-lucide="maximize-2" class="h-4 w-4 text-slate-400"></i></button><?php endif; ?><?php endforeach; ?></div></section><?php endif; ?>
            </div>
            <footer class="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-4"><button type="button" @click="applicationOpen = false" class="rounded-lg bg-rescue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rescue-700">Close</button></footer>
        </section>
    </div>
    <?php endif; ?>

    <div x-cloak x-show="catOpen" x-transition.opacity @keydown.escape.window="catOpen = false" @click.self="catOpen = false" class="fixed inset-0 z-[180] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
        <section class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="cat-modal-title">
            <div class="relative aspect-[16/8] overflow-hidden bg-slate-100"><img src="<?= esc(! empty($cat['image_url']) ? base_url($cat['image_url']) : 'https://images.unsplash.com/photo-1574158622682-e40ad296ce3f?auto=format&fit=crop&w=1000&q=80') ?>" alt="<?= esc($cat['name']) ?>" class="h-full w-full object-cover"><div class="absolute inset-0 bg-gradient-to-t from-black/65 via-transparent to-black/20"></div><div class="absolute bottom-5 left-5 text-white"><p class="text-xs font-bold uppercase tracking-wider text-rescue-100">Adopted cat profile</p><h2 id="cat-modal-title" class="mt-1 text-3xl font-semibold"><?= esc($cat['name']) ?></h2><p class="text-sm text-white/80"><?= esc($cat['breed'] ?: 'Mixed breed') ?></p></div><button type="button" @click="catOpen = false" class="absolute right-4 top-4 rounded-lg bg-white/90 p-2 text-slate-700 shadow" aria-label="Close cat profile"><i data-lucide="x" class="h-5 w-5"></i></button></div>
            <div class="space-y-5 p-5 sm:p-6"><div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4"><?php foreach (['Age' => ($cat['age'] ?: '—'), 'Gender' => ($cat['gender'] ?: '—'), 'Color' => ($cat['color'] ?: '—'), 'Weight' => ($cat['weight'] ?: '—')] as $label => $value): ?><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500"><?= esc($label) ?></p><p class="mt-1 font-semibold text-slate-900"><?= esc($value) ?></p></div><?php endforeach; ?></div><div class="grid gap-4 sm:grid-cols-2"><div class="rounded-xl border border-slate-200 p-4"><h3 class="text-sm font-semibold text-slate-900">Behavior notes</h3><p class="mt-2 text-sm leading-6 text-slate-600"><?= esc($cat['behavior_notes'] ?: 'No behavior notes recorded.') ?></p></div><div class="rounded-xl border border-rose-100 bg-rose-50 p-4"><h3 class="text-sm font-semibold text-rose-800">Medical notes</h3><p class="mt-2 text-sm leading-6 text-slate-700"><?= esc($cat['medical_notes'] ?: 'No medical notes recorded.') ?></p></div></div><?php if (! empty($galleryPhotos)): ?><div><h3 class="text-sm font-semibold text-slate-900">More photos</h3><div class="mt-3 grid grid-cols-3 gap-3"><?php foreach ($galleryPhotos as $photo): ?><?php $photoCaption = trim((string) ($photo['caption'] ?? '')) ?: $cat['name'] . ' photo'; ?><button type="button" data-photo-url="<?= esc(base_url($photo['photo_url']), 'attr') ?>" data-photo-label="<?= esc($photoCaption, 'attr') ?>" @click="updatePhoto = $el.dataset.photoUrl; updatePhotoLabel = $el.dataset.photoLabel" class="aspect-square overflow-hidden rounded-xl border border-slate-200"><img src="<?= esc(base_url($photo['photo_url'])) ?>" alt="<?= esc($photoCaption) ?>" loading="lazy" class="h-full w-full object-cover transition hover:scale-105"></button><?php endforeach; ?></div></div><?php endif; ?></div>
        </section>
    </div>

    <div x-cloak x-show="updatePhoto" x-transition.opacity @keydown.escape.window="updatePhoto = ''" @click.self="updatePhoto = ''" class="fixed inset-0 z-[220] flex items-center justify-center bg-black/90 p-4"><button type="button" @click="updatePhoto = ''" class="absolute right-4 top-4 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-800">Close</button><img :src="updatePhoto" :alt="updatePhotoLabel" class="max-h-full max-w-full rounded-lg object-contain"></div>
</div>

<script src="<?= base_url('js/adopter-portal-camera.js') ?>?v=<?= filemtime(FCPATH . 'js/adopter-portal-camera.js') ?>"></script>
<?= $this->endSection() ?>

<script type="text/plain">
(function () {
    var startBtn = document.getElementById('startCameraBtn');
    var captureBtn = document.getElementById('captureBtn');
    var retakeBtn = document.getElementById('retakeBtn');
    var video = document.getElementById('cameraStream');
    var canvas = document.getElementById('captureCanvas');
    var preview = document.getElementById('photoPreview');
    var hiddenInput = document.getElementById('photoInput');
    var locationBtn = document.getElementById('getLocationBtn');
    var locationStatus = document.getElementById('locationStatus');
    var locationStatusText = document.getElementById('locationStatusText');
    var latitudeInput = document.getElementById('latitudeInput');
    var longitudeInput = document.getElementById('longitudeInput');
    var accuracyInput = document.getElementById('gpsAccuracyInput');
    var captureSourceInput = document.getElementById('captureSourceInput');
    var deviceInfoInput = document.getElementById('deviceInfoInput');
    var helpEl = document.getElementById('cameraHelp');
    var stream = null;

    if (!startBtn) {
        console.warn('startCameraBtn not found');
        return;
    }

    function hasGetUserMedia() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    function resetCameraHelp() {
        if (helpEl) {
            helpEl.classList.add('hidden');
        }
    }

    async function requestCamera() {
        resetCameraHelp();
        console.log('portal camera request start');
        captureLocation();

        if (!hasGetUserMedia()) {
            console.warn('getUserMedia not available');
            if (helpEl) helpEl.classList.remove('hidden');
            return;
        }

        try {
            if (navigator.permissions && navigator.permissions.query) {
                try {
                    var status = await navigator.permissions.query({ name: 'camera' });
                    console.log('camera permission state', status.state);
                    if (status.state === 'denied') {
                        if (helpEl) helpEl.classList.remove('hidden');
                        return;
                    }
                } catch (permErr) {
                    console.warn('permissions query failed', permErr);
                }
            }
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false
            });
        } catch (err) {
            console.error('getUserMedia failed', err);
            if (helpEl && (err && (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError'))) {
                helpEl.classList.remove('hidden');
            }
            return;
        }

        video.srcObject = stream;
        video.classList.remove('hidden');
        captureBtn.disabled = true;
        video.play().catch(function(e){ console.warn('video play failed', e); });

        video.addEventListener('loadedmetadata', function () {
            captureBtn.disabled = false;
        }, { once: true });

        startBtn.classList.add('hidden');
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) {
            video.srcObject = null;
            video.classList.add('hidden');
        }
        captureBtn.disabled = true;
        startBtn.classList.remove('hidden');
    }

    startBtn.addEventListener('click', requestCamera);

    captureBtn.addEventListener('click', function () {
        if (!video || !canvas) return;
        var w = video.videoWidth || 1280;
        var h = video.videoHeight || 720;
        canvas.width = w;
        canvas.height = h;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, w, h);
        canvas.toBlob(function (blob) {
            if (!blob) return;
            var file = new File([blob], 'update_' + Date.now() + '.jpg', { type: 'image/jpeg' });
            try {
                var dt = new DataTransfer();
                dt.items.add(file);
                hiddenInput.files = dt.files;
            } catch (e) {
                if (photoError) { photoError.textContent = 'Your browser cannot attach the live camera capture. Please use a current Chrome, Edge, Firefox, or Safari browser.'; photoError.classList.remove('hidden'); }
                return;
            }
            captureSourceInput.value = 'in_app_camera';
            deviceInfoInput.value = JSON.stringify({ platform: navigator.platform || '', language: navigator.language || '', screen: (window.screen.width || '') + 'x' + (window.screen.height || ''), timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '', capturedAt: new Date().toISOString() });
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            captureBtn.classList.add('hidden');
            retakeBtn.classList.remove('hidden');
            stopCamera();
        }, 'image/jpeg', 0.92);
    });

    retakeBtn.addEventListener('click', function () {
            try { hiddenInput.value = ''; } catch (e) {}
            captureSourceInput.value = '';
        preview.src = '';
        preview.classList.add('hidden');
        retakeBtn.classList.add('hidden');
        captureBtn.classList.remove('hidden');
        startBtn.classList.remove('hidden');
    });

    function captureLocation() {
        if (!navigator.geolocation) {
            locationStatusText.textContent = 'Geolocation not supported by browser.';
            locationStatus.classList.remove('hidden');
            locationStatus.classList.add('text-red-600');
            return;
        }
        locationBtn.disabled = true;
        navigator.geolocation.getCurrentPosition(function (pos) {
            latitudeInput.value = pos.coords.latitude;
            longitudeInput.value = pos.coords.longitude;
            accuracyInput.value = pos.coords.accuracy;
            locationStatusText.textContent = 'Location captured (±' + Math.round(pos.coords.accuracy) + ' m) ✓';
            locationStatus.classList.remove('hidden');
            locationStatus.classList.remove('text-red-600');
            locationStatus.classList.add('text-adopted-600');
        }, function (err) {
            locationStatusText.textContent = 'Location error: ' + err.message;
            locationStatus.classList.remove('hidden');
            locationStatus.classList.remove('text-adopted-600');
            locationStatus.classList.add('text-red-600');
            locationBtn.disabled = false;
        }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
    }

    locationBtn.addEventListener('click', captureLocation);

    var form = document.getElementById('updateForm');
    var photoError = document.getElementById('photoError');

    if (form) {
        form.addEventListener('submit', function (event) {
            if (!hiddenInput.files || hiddenInput.files.length === 0 || captureSourceInput.value !== 'in_app_camera') {
                event.preventDefault();
                if (photoError) {
                    photoError.textContent = 'Please capture a live photo using the in-app camera before submitting.';
                    photoError.classList.remove('hidden');
                }
                if (startBtn) {
                    startBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            if (!latitudeInput.value || !longitudeInput.value || !accuracyInput.value) {
                event.preventDefault();
                if (photoError) { photoError.textContent = 'Please get and allow your current location before submitting.'; photoError.classList.remove('hidden'); }
                locationBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            if (photoError) {
                photoError.classList.add('hidden');
            }
            return true;
        });
    }

})();
</script>
