(function () {
    var form = document.querySelector('form[action*="adopter/apply"]');
    var occupation = form && form.querySelector('input[name="occupation"]');
    if (!form || !occupation) return;
    var grid = occupation.closest('.grid');
    if (!grid) return;
    var experience = document.createElement('div');
    experience.innerHTML = '<label class="mb-2 block text-sm font-medium text-gray-700">Pet Ownership Experience <span class="text-red-500">*</span></label><select required name="pet_ownership_experience" class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none"><option value="">Select experience</option><option value="First-time adopter">First-time adopter</option><option value="Previous pet owner">Previous pet owner</option><option value="Current pet owner">Current pet owner</option></select>';
    var budget = document.createElement('div');
    budget.innerHTML = '<label class="mb-2 block text-sm font-medium text-gray-700">Estimated Monthly Pet-care Budget (PHP) <span class="text-red-500">*</span></label><input required min="0" step="100" type="number" name="monthly_pet_budget" placeholder="e.g. 3000" class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none"><p class="mt-1 text-xs text-gray-500">Food, litter, preventive care, and basic veterinary needs.</p>';
    grid.appendChild(experience); grid.appendChild(budget);
})();
