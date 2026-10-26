<?php

if (! function_exists('badgeClass')) {
    function badgeClass(?string $status): string
    {
        return match ($status) {
            'Available', 'Verified' => 'bg-blue-50 text-blue-700',
            'Adopted', 'Approved', 'Active', 'Healthy', 'Completed' => 'bg-emerald-50 text-emerald-700',
            'Medical', 'Rejected', 'High', 'WANTED', 'MISSING', 'Critical', 'Flagged', 'Overdue' => 'bg-red-50 text-red-700 border border-red-100',
            'Foster', 'Medium', 'Busy', 'Recovering', 'In Progress', 'Under Review' => 'bg-amber-50 text-amber-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }
}

if (! function_exists('statusBadge')) {
    function statusBadge(?string $status): string
    {
        $label = esc($status ?: 'Pending');
        return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . badgeClass($status) . '">' . $label . '</span>';
    }
}
