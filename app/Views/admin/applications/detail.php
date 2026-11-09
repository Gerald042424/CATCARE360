<?= $this->extend('admin/admin_layout') ?>
<?= $this->section('content') ?>
<?php $score = $application['assessment_score']; $verificationMetadata = json_decode((string) ($application['verification_metadata'] ?? ''), true) ?: []; $idVerification = is_array($verificationMetadata['gov_id']['id_verification'] ?? null) ? $verificationMetadata['gov_id']['id_verification'] : []; $manualIdReview = is_array($verificationMetadata['manual_id_review'] ?? null) ? $verificationMetadata['manual_id_review'] : []; $preliminaryIdScreeningPassed = ($application['ocr_status'] ?? '') === 'Passed'; $idTypeMatched = ($idVerification['selected_type'] ?? '') !== '' && ($idVerification['selected_type'] ?? '') === ($idVerification['detected_type'] ?? ''); $idNameMatched = ($idVerification['name_match'] ?? false) === true; $liveIdCapture = ($verificationMetadata['gov_id']['capture_source'] ?? '') === 'in_app_camera'; $manualReviewCompleted = ($manualIdReview['status'] ?? '') === 'Staff Reviewed'; $documents = ['Verified Live Selfie' => $application['selfie_path'] ?? null, 'Valid Government-Issued ID' => $application['gov_id_path'] ?? null, 'Proof of Address' => $application['address_proof_path'] ?? null, 'Indoor Living Area' => $application['home_photos_path'] ?? null, 'Windows / Outdoor Access Area' => $verificationMetadata['home_access']['stored_path'] ?? null]; ?>
<section class="card mb-6"><div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-bold">ID Screening</h2><p class="mt-1 text-xs leading-5 text-slate-500">Screening assists staff review and does not authenticate government issuance.</p></div><span class="rounded-full px-2.5 py-1 text-xs font-bold <?= $preliminaryIdScreeningPassed ? 'bg-adopted-50 text-adopted-700' : 'bg-amber-50 text-amber-700' ?>"><?= $preliminaryIdScreeningPassed ? 'Preliminary screening passed' : 'Review required' ?></span></div><div class="mt-4 grid gap-2 text-sm sm:grid-cols-2"><?php foreach ([['Document type matched', $idTypeMatched], ['Applicant name matched', $idNameMatched], ['Live camera capture completed', $liveIdCapture], ['Server OCR readability passed', $preliminaryIdScreeningPassed]] as [$label, $passed]): ?><div class="flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full <?= $passed ? 'bg-adopted-100 text-adopted-700' : 'bg-amber-100 text-amber-700' ?>"><?= $passed ? '✓' : '!' ?></span><span class="font-medium text-slate-700"><?= esc($label) ?></span></div><?php endforeach ?></div><div class="mt-4 rounded-xl border p-3 <?= $manualReviewCompleted ? 'border-adopted-200 bg-adopted-50' : 'border-amber-200 bg-amber-50' ?>"><p class="text-xs font-bold uppercase tracking-wide <?= $manualReviewCompleted ? 'text-adopted-700' : 'text-amber-700' ?>">Authenticity status</p><p class="mt-1 text-sm font-semibold <?= $manualReviewCompleted ? 'text-adopted-900' : 'text-amber-900' ?>"><?= $manualReviewCompleted ? 'Manually reviewed by staff' : 'Needs staff review' ?></p><?php if ($manualReviewCompleted): ?><p class="mt-1 text-xs text-slate-600"><?= esc($manualIdReview['reviewed_by_name'] ?? 'Authorized staff') ?> · <?= esc(date('M d, Y g:i A', strtotime((string) ($manualIdReview['reviewed_at'] ?? 'now')))) ?></p><?php else: ?><p class="mt-1 text-xs leading-5 text-amber-800">Review the submitted document and confirm the applicant identity during document review or interview before approval.</p><?php endif ?></div></section>
<div x-data="{ documentUrl: '', documentLabel: '' }"><div class="mb-6 flex flex-wrap items-start justify-between gap-4"><div><a href="<?= site_url('admin/applications') ?>" class="text-sm font-semibold text-rescue-600">Applications</a><h1 class="mt-2 text-3xl font-bold tracking-[-.025em]"><?= esc($application['full_name']) ?></h1><p class="text-slate-500"><?= esc($application['phone']) ?> · <?= esc($application['occupation']) ?></p></div><?= statusBadge($application['status']) ?></div><div class="grid gap-6 xl:grid-cols-[1.35fr_.65fr]"><section class="space-y-6"><div class="card"><div class="grid gap-4 md:grid-cols-3"><div class="rounded-xl p-4 <?= (float) $score >= 80 ? 'bg-adopted-50' : ((float) $score >= 60 ? 'bg-amber-50' : 'bg-red-50') ?>"><div class="text-2xl font-bold"><?= esc(number_format((float) $score, 2)) ?>%</div><div class="mt-1 text-xs font-semibold uppercase">Preliminary score</div></div><div class="rounded-xl bg-rescue-50 p-4"><div class="font-bold text-rescue-900"><?= esc($application['assessment_status']) ?></div><div class="mt-1 text-xs font-semibold uppercase text-rescue-700">Result</div></div><div class="rounded-xl bg-slate-50 p-4"><div class="font-bold"><?= esc($application['cat_description']) ?></div><div class="mt-1 text-xs font-semibold uppercase text-slate-500">Requested cat</div></div></div></div><div class="card"><h2 class="text-xl font-bold">Personal Information</h2><dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2"><div><dt class="text-slate-400">Full name</dt><dd class="mt-1 font-semibold"><?= esc($application['full_name']) ?></dd></div><div><dt class="text-slate-400">Phone</dt><dd class="mt-1 font-semibold"><?= esc($application['phone']) ?></dd></div><div><dt class="text-slate-400">Occupation</dt><dd class="mt-1 font-semibold"><?= esc($application['occupation']) ?></dd></div><div class="sm:col-span-2"><dt class="text-slate-400">Address</dt><dd class="mt-1 font-semibold"><?= esc($application['address']) ?></dd></div></dl></div><div class="card"><h2 class="text-xl font-bold">Application Answers</h2><p class="mt-2 text-sm text-slate-500">Review relevance, practicality, and completeness. Do not score essay length or grammar. Indoor, outdoor, and combined arrangements are evaluated by their safety measures.</p><div class="mt-5 space-y-4"><?php foreach ($application['assessment_rows'] as $row): ?><article class="rounded-xl border border-slate-200 p-4"><div class="flex items-start justify-between gap-3"><h3 class="font-semibold text-slate-900"><?= esc($row['label']) ?></h3><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"><?= esc($row['rating']) ?>/4 · <?= esc(number_format((float) $row['earned_points'], 2)) ?> pts</span></div><?php if (! empty($row['indicators'])): ?><details class="mt-3 text-xs text-slate-600"><summary class="cursor-pointer font-semibold text-rescue-700">Evaluation indicators</summary><ul class="mt-2 list-disc space-y-1 pl-5"><?php foreach ($row['indicators'] as $indicator): ?><li><?= esc($indicator) ?></li><?php endforeach ?></ul></details><?php endif ?><p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700"><?= esc($row['response']) ?></p></article><?php endforeach; ?></div></div></section><aside class="space-y-6"><div class="card"><details><summary class="cursor-pointer text-lg font-bold text-slate-900">Documents</summary><div class="mt-4 space-y-2"><?php foreach ($documents as $label => $path): ?><?php if ($path): ?><button type="button" @click="documentUrl = '<?= esc(base_url($path), 'js') ?>'; documentLabel = '<?= esc($label, 'js') ?>'" class="flex w-full items-center gap-2 rounded-lg border border-slate-200 p-3 text-left text-sm font-semibold hover:border-rescue-300 hover:bg-rescue-50"><i data-lucide="file-text" class="h-4 w-4 text-rescue-600"></i><?= esc($label) ?><i data-lucide="maximize-2" class="ml-auto h-4 w-4 text-slate-400"></i></button><?php endif; ?><?php endforeach; ?></div></details></div><div class="card"><h2 class="text-lg font-bold">Decision</h2><p class="mt-1 text-xs text-slate-500">A rejection reason is required and will be shown to the adopter in their email and application progress.</p><div class="mt-4 space-y-3"><?php foreach ($application['requested_cats'] as $cat): ?><div class="rounded-xl border border-slate-200 p-3"><p class="font-bold"><?= esc($cat['name']) ?></p><form method="post" action="<?= site_url('admin/applications/' . $application['id'] . '/cats/' . $cat['id']) ?>" class="mt-3 space-y-3" x-data="{ decisionStatus: '<?= esc($cat['status'], 'js') ?>' }"><?= csrf_field() ?><select name="status" x-model="decisionStatus" class="field text-sm"><?php foreach (['Under Review', 'Approved', 'Rejected'] as $status): ?><option <?= $cat['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select><div x-cloak x-show="decisionStatus === 'Rejected'"><label class="label">Reason for rejection</label><textarea name="decision_reason" :required="decisionStatus === 'Rejected'" minlength="10" maxlength="1000" class="field min-h-28" placeholder="Explain clearly why this application cannot be approved and what may be improved."><?= esc($application['final_decision_reason'] ?? '') ?></textarea><p class="mt-1 text-xs text-slate-500">This message will be sent directly to the adopter.</p></div><button class="btn btn-primary w-full justify-center">Save decision</button></form></div><?php endforeach; ?></div></div></aside></div><div x-cloak x-show="documentUrl" class="fixed inset-0 z-[200] bg-black/90 p-4" @keydown.escape.window="documentUrl = ''"><button type="button" @click="documentUrl = ''" class="absolute right-5 top-5 rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-800">Close</button><div class="flex h-full items-center justify-center"><img :src="documentUrl" :alt="documentLabel" class="max-h-full max-w-full object-contain"></div></div></div>
<section id="consolidatedAnswers" class="mt-6 hidden card"><h2 class="text-xl font-bold">Consolidated Application Answers</h2><p class="mt-1 text-sm text-slate-500">Each main question and all of its related subanswers are shown together. Points are the combined weighted contribution for that group.</p><div id="consolidatedAnswerCards" class="mt-5 space-y-5"></div></section>
<?php if (! empty($application['assessment_flags'])): ?><section class="mt-6 card"><h2 class="text-xl font-bold">Rule-based and Consistency Flags</h2><p class="mt-1 text-sm text-slate-500">Review prompts only. They never automatically reject an applicant or change the score.</p><?php foreach ($application['assessment_flags'] as $flag): ?><article class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4"><p class="font-bold text-red-800"><?= esc($flag['status']) ?> · Affected criterion: <?= esc($flag['criterion_key']) ?></p><p class="mt-1 text-sm text-red-900"><?= esc($flag['reason']) ?></p><?php if (! empty($flag['related_answers'])): ?><div class="mt-3 rounded-lg border border-red-200 bg-white p-3"><p class="text-xs font-bold uppercase tracking-wide text-red-700">Compared answers</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700"><?= esc($flag['related_answers']) ?></p></div><?php endif; ?></article><?php endforeach ?></section><?php endif; ?>
<script>document.addEventListener('DOMContentLoaded',()=>{const legacy=[...document.querySelectorAll('article')],map={'Long-Term Commitment and Care Readiness':['1. Long-Term Commitment, Daily Care, and Household Readiness',23],'Love, Attention, and Daily Care':['1. Long-Term Commitment, Daily Care, and Household Readiness',23],'Family Readiness and Support':['1. Long-Term Commitment, Daily Care, and Household Readiness',23],'Housing Status and Permission':['2. Housing, Indoor Living, Escape Prevention, and Humane Space',30],'Indoor Living and Escape Prevention':['2. Housing, Indoor Living, Escape Prevention, and Humane Space',30],'Humane Housing and No Permanent Cage Confinement':['2. Housing, Indoor Living, Escape Prevention, and Humane Space',30],'Healthy Food and Clean Water':['3. Food, Water, Veterinary Care, and Parasite Prevention',28],'Vaccinations and Veterinary Expenses':['3. Food, Water, Veterinary Care, and Parasite Prevention',28],'Deworming Schedule':['3. Food, Water, Veterinary Care, and Parasite Prevention',28],'Tick and Flea Prevention':['3. Food, Water, Veterinary Care, and Parasite Prevention',28],'Spaying/Neutering Plan':['4. Spaying/Neutering and Existing Pets',14],'Existing Pets and Sterilization Status':['4. Spaying/Neutering and Existing Pets',14],'Backup Caregiver and Contingency Plan':['5. Backup Caregiver and Emergency Plan',5]};const bucket={};legacy.forEach(card=>{const title=card.querySelector('h3')?.textContent.trim(),answer=card.querySelector('p.whitespace-pre-line')?.textContent||'',badge=card.querySelector('span.rounded-full')?.textContent||'';if(!map[title])return;const [group,weight]=map[title],points=parseFloat((badge.match(/·\s*([\d.]+)\s*pts/)||[])[1])||0;const answers=answer.split(/\n\s*\n/);(bucket[group]??={weight,points:0,answers:[]}).points+=points;answers.forEach(value=>{if(value&&!bucket[group].answers.includes(value))bucket[group].answers.push(value)})});const output=document.getElementById('consolidatedAnswerCards');Object.entries(bucket).forEach(([title,data])=>{const card=document.createElement('article'),head=document.createElement('div'),h=document.createElement('h3'),badge=document.createElement('span');card.className='rounded-xl border border-slate-200 p-4';head.className='flex items-start justify-between gap-3';h.className='font-semibold text-slate-900';h.textContent=title;badge.className='rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700';badge.textContent=`${data.points.toFixed(2)} / ${data.weight.toFixed(2)} pts`;head.append(h,badge);card.append(head);data.answers.sort((a,b)=>a.localeCompare(b,undefined,{numeric:true})).forEach((value,i)=>{const [label,...body]=value.split(': '),box=document.createElement('div'),strong=document.createElement('p'),text=document.createElement('p');box.className=i===0?'mt-4 rounded-lg bg-rescue-50 p-3':'mt-3 border-l-2 border-slate-200 pl-3';strong.className='text-sm font-bold text-rescue-800';strong.textContent=label;text.className='mt-1 whitespace-pre-line text-sm leading-6 text-slate-700';text.textContent=body.join(': ');box.append(strong,text);card.append(box)});output.append(card)});const old=[...document.querySelectorAll('.card')].find(c=>c.querySelector('h2')?.textContent.trim()==='Application Answers');if(old)old.classList.add('hidden');document.getElementById('consolidatedAnswers').classList.remove('hidden')})</script>
<script>document.addEventListener('DOMContentLoaded',()=>{const legacy=[...document.querySelectorAll('article')].find(c=>c.querySelector('h3')?.textContent.trim().startsWith('6. Overall Readiness'));const output=document.getElementById('consolidatedAnswerCards');if(!legacy||!output)return;const raw=legacy.querySelector('p.whitespace-pre-line')?.textContent||'Not provided.';const card=document.createElement('article'),head=document.createElement('div'),title=document.createElement('h3'),badge=document.createElement('span');card.className='rounded-xl border border-slate-200 p-4';head.className='flex items-start justify-between gap-3';title.className='font-semibold text-slate-900';title.textContent='6. Overall Readiness and Responsible Follow-Through';badge.className='rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700';badge.textContent='Unweighted reflection';head.append(title,badge);card.append(head);raw.split(/\n\s*\n/).forEach((value,index)=>{const [label,...body]=value.split(': '),box=document.createElement('div'),strong=document.createElement('p'),text=document.createElement('p');box.className=index===0?'mt-4 rounded-lg bg-violet-50 p-3':'mt-3 border-l-2 border-violet-200 pl-3';strong.className='text-sm font-bold text-violet-800';strong.textContent=label;text.className='mt-1 whitespace-pre-line text-sm leading-6 text-slate-700';text.textContent=body.join(': ');box.append(strong,text);card.append(box)});output.append(card)})</script>
<?php $documentViewerItems = []; foreach ($documents as $label => $path) { if ($path) { $documentViewerItems[] = ['label' => $label, 'url' => base_url($path)]; } } ?>
<?= view('components/document_viewer', ['documents' => $documentViewerItems]) ?>
<div id="interviewInvitationModal" class="fixed inset-0 z-[350] hidden items-center justify-center bg-slate-950/70 p-4" role="dialog" aria-modal="true" aria-labelledby="interviewInvitationTitle">
    <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <header class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-6 py-5"><div><p class="text-xs font-bold uppercase tracking-wider text-rescue-600">Email adopter</p><h2 id="interviewInvitationTitle" class="mt-1 text-xl font-bold text-slate-900">Interview Invitation</h2><p class="mt-1 text-sm text-slate-500">Choose up to three schedules. The professional invitation message and Google Meet link are included automatically.</p></div><button id="closeInterviewInvitation" type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close interview invitation"><i data-lucide="x" class="h-5 w-5"></i></button></header>
        <form method="post" action="<?= site_url('admin/applications/' . $application['id'] . '/send-interview-invitation') ?>" class="min-h-0 overflow-y-auto p-6" id="interviewInvitationForm">
            <?= csrf_field() ?>
            <div class="rounded-xl border border-rescue-100 bg-rescue-50 p-4 text-sm leading-6 text-rescue-900"><strong>Fixed email message:</strong> The applicant will be thanked for their interest and invited to an online interview to discuss and finalize the application assessment. They will be asked to reply and confirm one preferred schedule.</div>
            <div id="interviewScheduleChoices" class="mt-5 space-y-3"></div>
            <button id="addInterviewSchedule" type="button" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-rescue-200 bg-rescue-50 px-3 py-2 text-sm font-semibold text-rescue-700 hover:bg-rescue-100"><i data-lucide="plus" class="h-4 w-4"></i>Add another schedule</button>
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-emerald-800">Google Meet</p><a href="https://meet.google.com/meet/thj-tvep-mwz" target="_blank" rel="noopener" class="mt-1 block break-all text-sm font-semibold text-emerald-700 hover:underline">https://meet.google.com/meet/thj-tvep-mwz</a></div>
            <p class="mt-4 text-xs leading-5 text-slate-500">All displayed schedules use Philippine Time. Sending this email does not approve the application.</p>
            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5"><button id="cancelInterviewInvitation" type="button" class="btn btn-ghost">Cancel</button><button class="btn btn-primary"><i data-lucide="send" class="h-4 w-4"></i>Send email invitation</button></div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('interviewInvitationModal');
    const scheduleContainer = document.getElementById('interviewScheduleChoices');
    const addButton = document.getElementById('addInterviewSchedule');
    const decisionHeading = [...document.querySelectorAll('.card h2')].find((heading) => heading.textContent.trim() === 'Decision');
    if (!modal || !scheduleContainer || !addButton || !decisionHeading) return;

    const decisionCard = decisionHeading.closest('.card');
    const decisionForms = [...decisionCard.querySelectorAll('form[action*="/cats/"]')];
    const recordedStatuses = <?= json_encode(array_values(array_column($application['requested_cats'] ?? [], 'status')), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const manualIdReviewCompleted = <?= json_encode($manualReviewCompleted) ?>;
    let hasPendingDecision = false;
    decisionForms.forEach((form, index) => {
        const statusSelect = form.querySelector('select[name="status"]');
        const status = recordedStatuses[index] || statusSelect?.value || '';
        if (status === 'Under Review') {
            hasPendingDecision = true;
            if (!manualIdReviewCompleted && statusSelect) {
                const reviewBlock = document.createElement('div');
                reviewBlock.className = 'hidden rounded-xl border border-amber-200 bg-amber-50 p-3';
                reviewBlock.innerHTML = '<label class="flex items-start gap-2 text-sm leading-5 text-amber-950"><input type="checkbox" name="manual_id_review" value="1" class="mt-1 rounded border-amber-300 text-rescue-600"><span>I manually reviewed the submitted ID and confirmed the applicant\'s identity during document review or interview.</span></label>';
                const reviewCheckbox = reviewBlock.querySelector('input');
                const updateReviewRequirement = () => {
                    const approvalSelected = statusSelect.value === 'Approved';
                    reviewBlock.classList.toggle('hidden', !approvalSelected);
                    reviewCheckbox.required = approvalSelected;
                    if (!approvalSelected) reviewCheckbox.checked = false;
                };
                const saveButton = form.querySelector('button.btn-primary');
                if (saveButton) form.insertBefore(reviewBlock, saveButton);
                statusSelect.addEventListener('change', updateReviewRequirement);
                updateReviewRequirement();
            }
            return;
        }
        if (!['Approved', 'Rejected', 'Unavailable'].includes(status)) return;
        const reason = form.querySelector('textarea[name="decision_reason"]')?.value.trim() || '';
        const locked = document.createElement('div');
        locked.className = status === 'Approved'
            ? 'mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3'
            : (status === 'Unavailable' ? 'mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3' : 'mt-3 rounded-xl border border-red-200 bg-red-50 p-3');
        const badge = document.createElement('p');
        badge.className = status === 'Approved' ? 'font-bold text-emerald-800' : (status === 'Unavailable' ? 'font-bold text-amber-800' : 'font-bold text-red-800');
        badge.textContent = status;
        const notice = document.createElement('p');
        notice.className = 'mt-1 text-xs text-slate-600';
        notice.textContent = 'Final decision recorded. This decision can no longer be modified.';
        locked.append(badge, notice);
        if (status === 'Rejected' && reason) {
            const reasonLabel = document.createElement('p');
            reasonLabel.className = 'mt-3 text-xs font-bold uppercase tracking-wide text-red-700';
            reasonLabel.textContent = 'Reason for rejection';
            const reasonText = document.createElement('p');
            reasonText.className = 'mt-1 whitespace-pre-line text-sm leading-6 text-red-900';
            reasonText.textContent = reason;
            locked.append(reasonLabel, reasonText);
        }
        form.replaceWith(locked);
    });

    const emailButton = document.createElement('button');
    emailButton.type = 'button';
    emailButton.className = 'inline-flex items-center gap-2 rounded-lg border border-rescue-200 bg-rescue-50 px-3 py-2 text-sm font-semibold text-rescue-700 hover:bg-rescue-100';
    emailButton.innerHTML = '<i data-lucide="mail" class="h-4 w-4"></i>Email interview invitation';
    const headingRow = document.createElement('div');
    headingRow.className = 'flex flex-wrap items-center justify-between gap-3';
    decisionHeading.replaceWith(headingRow);
    headingRow.append(decisionHeading);
    if (hasPendingDecision) headingRow.append(emailButton);

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.documentElement.classList.remove('overflow-hidden');
    };
    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.documentElement.classList.add('overflow-hidden');
        scheduleContainer.querySelector('input')?.focus();
    };
    const updateControls = () => {
        const slots = [...scheduleContainer.children];
        addButton.disabled = slots.length >= 3;
        addButton.classList.toggle('opacity-50', slots.length >= 3);
        slots.forEach((slot) => {
            const remove = slot.querySelector('[data-remove-schedule]');
            remove.classList.toggle('hidden', slots.length === 1);
        });
    };
    const addSchedule = () => {
        if (scheduleContainer.children.length >= 3) return;
        const number = scheduleContainer.children.length + 1;
        const slot = document.createElement('fieldset');
        slot.className = 'rounded-xl border border-slate-200 p-4';
        slot.innerHTML = `<div class="mb-3 flex items-center justify-between"><legend class="font-bold text-slate-800">Schedule option ${number}</legend><button type="button" data-remove-schedule class="text-xs font-semibold text-red-600 hover:underline">Remove</button></div><div class="grid gap-3 sm:grid-cols-3"><div><label class="label">Date</label><input type="date" name="interview_date[]" required class="field"></div><div><label class="label">Start time</label><input type="time" name="interview_start[]" required class="field"></div><div><label class="label">End time</label><input type="time" name="interview_end[]" required class="field"></div></div>`;
        slot.querySelector('[data-remove-schedule]').addEventListener('click', () => { slot.remove(); [...scheduleContainer.querySelectorAll('legend')].forEach((legend, index) => { legend.textContent = `Schedule option ${index + 1}`; }); updateControls(); });
        scheduleContainer.append(slot);
        updateControls();
        if (window.lucide) window.lucide.createIcons();
    };

    emailButton.addEventListener('click', openModal);
    document.getElementById('closeInterviewInvitation').addEventListener('click', closeModal);
    document.getElementById('cancelInterviewInvitation').addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
    addButton.addEventListener('click', addSchedule);
    addSchedule();
    if (window.lucide) window.lucide.createIcons();
});
</script>
<?= $this->endSection() ?>
