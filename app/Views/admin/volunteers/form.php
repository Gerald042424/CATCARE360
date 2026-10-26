<?= $this->extend('admin/admin_layout') ?>
<?= $this->section('content') ?>
<?php
$v = $volunteer ?? [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$savedDays = array_filter(array_map('trim', explode(',', (string) ($v['availability'] ?? ''))));
$oldDays = old('availability_days');
$selectedDays = is_array($oldDays) ? $oldDays : $savedDays;
?>
<form method="post" enctype="multipart/form-data" class="card max-w-4xl" x-data="volunteerPhotoCapture()">
    <?= csrf_field() ?>
    <div class="mb-6 flex items-center justify-between"><div><h1 class="text-3xl font-bold tracking-[-.025em]"><?= esc($title) ?></h1><p class="text-slate-500">Create or update volunteer profile and availability.</p></div><a href="<?= site_url('admin/volunteers') ?>" class="btn btn-ghost">Back</a></div>
    <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ([['name', 'Name', 'text'], ['email', 'Email', 'email'], ['phone', 'Phone', 'text'], ['location', 'Location', 'text'], ['joined_date', 'Joined Date', 'date']] as [$field, $label, $type]): ?>
            <div><label class="label"><?= esc($label) ?></label><input class="field" type="<?= esc($type) ?>" name="<?= esc($field) ?>" value="<?= esc(old($field, $v[$field] ?? '')) ?>"></div>
        <?php endforeach; ?>
        <div><label class="label">Status</label><select class="field" name="status"><?php foreach (['Active', 'Busy', 'Inactive'] as $status): ?><option value="<?= $status ?>" <?= old('status', $v['status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></div>
    </div>
    <section class="mt-6 rounded-2xl border border-rescue-100 bg-rescue-50/50 p-5">
        <div class="flex items-start justify-between gap-4"><div><h2 class="font-bold text-slate-900">Volunteer Photo</h2><p class="mt-1 text-sm text-slate-600">Upload a photo or take one using this device’s camera.</p></div><button type="button" class="btn btn-ghost shrink-0" @click="openCamera()"><i data-lucide="camera" class="h-4 w-4"></i>Use Camera</button></div>
        <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto]"><div><label class="label">Upload photo</label><input x-ref="fileInput" @change="previewFile($event)" class="field bg-white" type="file" name="photo" accept="image/jpeg,image/png,image/webp" capture="user"></div><img x-show="preview" :src="preview" alt="Volunteer photo preview" class="h-24 w-24 rounded-xl object-cover" style="display:none"></div>
        <?php if (! empty($v['image_url'])): ?><p class="mt-3 text-xs text-slate-500">A saved photo will remain unless you upload or capture a new one.</p><?php endif; ?>
        <div x-show="cameraOpen" x-cloak class="mt-5 rounded-xl border border-rescue-200 bg-white p-4"><video x-ref="video" autoplay playsinline class="max-h-72 w-full rounded-lg bg-slate-900 object-cover"></video><canvas x-ref="canvas" class="hidden"></canvas><div class="mt-3 flex gap-2"><button type="button" class="btn btn-primary" @click="takePhoto()">Take Photo</button><button type="button" class="btn btn-ghost" @click="closeCamera()">Cancel</button></div><p x-show="cameraError" x-text="cameraError" class="mt-3 text-sm text-red-600"></p></div>
    </section>
    <section class="mt-6 rounded-2xl border border-rescue-100 bg-rescue-50/50 p-5">
        <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rescue-600 text-white"><i data-lucide="calendar-clock" class="h-5 w-5"></i></span><div><h2 class="font-bold text-slate-900">Weekly Availability</h2><p class="mt-1 text-sm text-slate-600">Tasks can only be assigned within these selected days and hours.</p></div></div>
        <div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4"><?php foreach ($days as $day): ?><label class="flex cursor-pointer items-center gap-2 rounded-lg border border-rescue-100 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:border-rescue-300"><input type="checkbox" name="availability_days[]" value="<?= $day ?>" <?= in_array($day, $selectedDays, true) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-rescue-600"><span><?= esc(substr($day, 0, 3)) ?></span></label><?php endforeach; ?></div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2"><div><label class="label">Available from</label><input class="field" type="time" name="availability_start_time" value="<?= esc(old('availability_start_time', substr((string) ($v['availability_start_time'] ?? ''), 0, 5))) ?>"></div><div><label class="label">Available until</label><input class="field" type="time" name="availability_end_time" value="<?= esc(old('availability_end_time', substr((string) ($v['availability_end_time'] ?? ''), 0, 5))) ?>"></div></div>
        <p class="mt-3 text-xs text-rescue-800">Active volunteers need at least one day and a start/end time before they can receive tasks.</p>
    </section>
    <div class="mt-4"><label class="label">Notes</label><textarea class="field min-h-32" name="notes"><?= esc(old('notes', $v['notes'] ?? '')) ?></textarea></div>
    <button class="btn btn-primary mt-6">Save Volunteer</button>
</form>
<script>
function volunteerPhotoCapture() { return { cameraOpen:false, cameraError:'', preview:'', stream:null,
    previewFile(event) { const file = event.target.files[0]; if (file) this.preview = URL.createObjectURL(file); },
    async openCamera() { this.cameraError=''; try { this.stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false}); this.cameraOpen=true; this.$nextTick(()=>{this.$refs.video.srcObject=this.stream;}); } catch (error) { this.cameraError='Camera access was not available. You can upload a photo instead.'; this.cameraOpen=true; } },
    closeCamera() { if(this.stream) this.stream.getTracks().forEach(track=>track.stop()); this.stream=null; this.cameraOpen=false; },
    takePhoto() { const video=this.$refs.video, canvas=this.$refs.canvas; canvas.width=video.videoWidth; canvas.height=video.videoHeight; if(!canvas.width || !canvas.height) { this.cameraError='Wait for the camera preview, then try again.'; return; } canvas.getContext('2d').drawImage(video,0,0); canvas.toBlob(blob=>{ const file=new File([blob],'volunteer-photo.jpg',{type:'image/jpeg'}); const files=new DataTransfer(); files.items.add(file); this.$refs.fileInput.files=files.files; this.preview=URL.createObjectURL(blob); this.closeCamera(); },'image/jpeg',0.9); }
}; }
</script>
<?= $this->endSection() ?>
