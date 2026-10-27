<?= $this->extend('admin/admin_layout') ?>
<?= $this->section('content') ?>
<?php $taskData = $task ?? []; $categories = ['Feeding', 'Cleaning', 'Medical', 'Transport', 'Socialization', 'Walking']; ?>
<form method="post" class="card max-w-4xl">
    <?= csrf_field() ?>
    <div class="mb-6 flex items-center justify-between"><div><h1 class="text-3xl font-bold tracking-[-.025em]"><?= esc($title) ?></h1><p class="text-slate-500">Task details and assignment.</p></div><a href="<?= site_url('admin/tasks') ?>" class="btn btn-ghost">Back</a></div>
    <input type="hidden" name="status" value="<?= esc($taskData['status'] ?? 'Pending') ?>">
    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2"><label class="label">Title</label><input required class="field" name="title" value="<?= esc(old('title', $taskData['title'] ?? '')) ?>"></div>
        <div><label class="label">Category</label><select required class="field" name="category"><?php foreach ($categories as $category): ?><option value="<?= esc($category) ?>" <?= old('category', $taskData['category'] ?? 'Feeding') === $category ? 'selected' : '' ?>><?= esc($category) ?></option><?php endforeach ?></select></div>
        <div><label class="label">Priority</label><select class="field" name="priority"><?php foreach (['Low', 'Medium', 'High'] as $priority): ?><option value="<?= esc($priority) ?>" <?= old('priority', $taskData['priority'] ?? 'Medium') === $priority ? 'selected' : '' ?>><?= esc($priority) ?></option><?php endforeach ?></select></div>
        <div><label class="label">Status</label><input readonly class="field cursor-not-allowed bg-slate-100 text-slate-500" value="<?= esc($taskData['status'] ?? 'Pending') ?>"><p class="mt-1 text-xs text-slate-500">New tasks automatically start as Pending.</p></div>
        <div><label class="label">Location</label><input class="field" name="location" value="<?= esc(old('location', $taskData['location'] ?? '')) ?>"></div>
        <div><label class="label">Date</label><input required type="date" class="field" name="task_date" value="<?= esc(old('task_date', $taskData['task_date'] ?? date('Y-m-d'))) ?>"></div>
        <div><label class="label">Time</label><input required type="time" class="field" name="task_time" value="<?= esc(old('task_time', substr((string) ($taskData['task_time'] ?? '08:00'), 0, 5))) ?>"></div>
        <div><label class="label">Assign To</label><select class="field" name="assigned_to"><option value="">Unassigned — automatic fair assignment</option><?php foreach ($volunteers as $volunteer): ?><?php $availability = trim((string) ($volunteer['availability'] ?? '')); ?><option value="<?= (int) $volunteer['id'] ?>" <?= (string) old('assigned_to', $taskData['assigned_to'] ?? '') === (string) $volunteer['id'] ? 'selected' : '' ?> <?= ($volunteer['status'] ?? '') !== 'Active' ? 'disabled' : '' ?>><?= esc($volunteer['name']) ?><?= $availability ? ' — ' . esc($availability) : '' ?></option><?php endforeach ?></select><p class="mt-1 text-xs text-slate-500">New tasks start as Pending. The system checks availability before assignment.</p></div>
        <div><label class="label">Link Cat</label><select class="field" name="animal_id"><option value="">None</option><?php foreach ($animals as $animal): ?><option value="<?= (int) $animal['id'] ?>" <?= (string) old('animal_id', $taskData['animal_id'] ?? '') === (string) $animal['id'] ? 'selected' : '' ?>><?= esc($animal['name']) ?></option><?php endforeach ?></select></div>
    </div>
    <div class="mt-4"><label class="label">Description</label><textarea class="field min-h-32" name="description"><?= esc(old('description', $taskData['description'] ?? '')) ?></textarea></div>
    <button class="btn btn-primary mt-6">Save Task</button>
</form>
<?= $this->endSection() ?>
