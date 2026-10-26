(function () {
    'use strict';

    var form = document.querySelector('form[action*="adopter/apply"]');
    var errorBox = document.getElementById('captureError');
    var metadataInput = document.getElementById('verificationMetadata');
    var captures = {};
    var activeCard = null;

    if (!form || !errorBox || !metadataInput || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;

    function showError(message) { errorBox.textContent = message; errorBox.classList.remove('hidden'); }
    function clearError() { errorBox.textContent = ''; errorBox.classList.add('hidden'); }
    function status(card, message, tone) {
        var item = card.querySelector('.location-status');
        item.textContent = message;
        item.className = 'location-status mt-3 text-xs font-medium ' + (tone || 'text-gray-500');
    }
    function stop(card) {
        if (card._stream) card._stream.getTracks().forEach(function (track) { track.stop(); });
        card._stream = null;
        var video = card.querySelector('.camera-stream');
        video.pause(); video.srcObject = null; video.classList.add('hidden');
        card.querySelector('.capture-photo').disabled = true;
        if (activeCard === card) activeCard = null;
    }
    function freshLocation(card) {
        return new Promise(function (resolve, reject) {
            if (!navigator.geolocation) return reject(new Error('This browser does not support GPS location.'));
            status(card, 'Getting your current GPS location…', 'text-gray-500');
            navigator.geolocation.getCurrentPosition(function (position) {
                status(card, 'Location ready (±' + Math.round(position.coords.accuracy) + ' m)', 'text-emerald-700');
                resolve(position.coords);
            }, function (error) {
                reject(new Error('Location is required. Allow location access and try again. (' + error.message + ')'));
            }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
        });
    }
    async function openCamera(card) {
        clearError();
        if (activeCard && activeCard !== card) stop(activeCard);
        stop(card);
        try {
            var stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: card._facingMode || 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false
            });
            card._stream = stream; activeCard = card;
            var video = card.querySelector('.camera-stream');
            video.srcObject = stream; video.classList.remove('hidden');
            await video.play();
            card.querySelector('.capture-photo').disabled = false;
            card.querySelector('.start-camera').classList.add('hidden');
            card.querySelector('.switch-camera').classList.remove('hidden');
            status(card, 'Camera ready. Press Capture Photo, then allow current location access.', 'text-gray-500');
        } catch (error) {
            showError(error.message || 'Camera access is required. Allow it in the browser and try again.');
        }
    }
    function drawWatermark(context, width, height, address, coords, now) {
        var size = Math.max(16, Math.round(width / 52));
        var lines = [
            'CATCARE 360 • LIVE VERIFICATION',
            now.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'medium' }),
            'ADDRESS: ' + address,
            'GPS: ' + coords.latitude.toFixed(6) + ', ' + coords.longitude.toFixed(6) + '  ±' + Math.round(coords.accuracy) + ' m'
        ];
        var panelHeight = Math.ceil(size * (lines.length * 1.45 + 1.45));
        context.fillStyle = 'rgba(0, 0, 0, 0.76)';
        context.fillRect(0, height - panelHeight, width, panelHeight);
        context.fillStyle = '#ffffff'; context.font = '600 ' + size + 'px Arial, sans-serif';
        lines.forEach(function (line, index) { context.fillText(line, size, height - panelHeight + size * (1.35 + index * 1.45)); });
    }
    async function capture(card) {
        clearError();
        var video = card.querySelector('.camera-stream');
        var address = (form.querySelector('input[name="address"]').value || '').trim();
        if (!address) return showError('Enter your complete address before taking verification photos.');
        if (!card._stream || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA || !video.videoWidth || !video.videoHeight) {
            return showError('Camera is still starting. Wait a moment, then capture again.');
        }
        var captureButton = card.querySelector('.capture-photo');
        captureButton.disabled = true;
        try {
            var coords = await freshLocation(card);
            var canvas = card.querySelector('.capture-canvas');
            canvas.width = video.videoWidth; canvas.height = video.videoHeight;
            var context = canvas.getContext('2d', { willReadFrequently: false });
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            drawWatermark(context, canvas.width, canvas.height, address, coords, new Date());
            canvas.toBlob(function (blob) {
                if (!blob || blob.size < 4096) { captureButton.disabled = false; return showError('The photo could not be created. Please retake it.'); }
                if (typeof DataTransfer === 'undefined') { captureButton.disabled = false; return showError('Your browser cannot attach a live camera photo. Use the latest Chrome or Edge.'); }
                var key = card.dataset.key;
                var file = new File([blob], 'live-' + key + '-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                var transfer = new DataTransfer(); transfer.items.add(file);
                var fileInput = card.querySelector('.capture-file'); fileInput.files = transfer.files;
                if (!fileInput.files || !fileInput.files.length) { captureButton.disabled = false; return showError('The photo could not be attached. Please capture it again.'); }
                captures[key] = { capture_source: 'in_app_camera', latitude: coords.latitude, longitude: coords.longitude, accuracy: coords.accuracy, captured_at: new Date().toISOString(), device_info: { platform: navigator.platform || '', language: navigator.language || '', timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '' } };
                metadataInput.value = JSON.stringify(captures);
                var preview = card.querySelector('.capture-preview'); preview.src = URL.createObjectURL(file); preview.classList.remove('hidden');
                card.querySelector('.retake-photo').classList.remove('hidden');
                captureButton.classList.add('hidden'); card.querySelector('.switch-camera').classList.add('hidden');
                card.querySelector('.capture-state').textContent = 'Captured';
                card.querySelector('.capture-state').className = 'capture-state rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700';
                status(card, 'Live photo captured with GPS, date, and time stamp.', 'text-emerald-700'); stop(card);
            }, 'image/jpeg', 0.92);
        } catch (error) {
            captureButton.disabled = false;
            showError(error.message || 'Could not capture the verification photo.');
        }
    }
    document.querySelectorAll('.verification-capture').forEach(function (card) {
        card._facingMode = 'environment';
        card.querySelector('.start-camera').addEventListener('click', function () { openCamera(card); });
        card.querySelector('.switch-camera').addEventListener('click', function () { card._facingMode = card._facingMode === 'environment' ? 'user' : 'environment'; openCamera(card); });
        card.querySelector('.capture-photo').addEventListener('click', function () { capture(card); });
        card.querySelector('.retake-photo').addEventListener('click', function () {
            delete captures[card.dataset.key]; metadataInput.value = JSON.stringify(captures);
            card.querySelector('.capture-file').value = ''; card.querySelector('.capture-preview').removeAttribute('src'); card.querySelector('.capture-preview').classList.add('hidden');
            card.querySelector('.retake-photo').classList.add('hidden'); card.querySelector('.capture-photo').classList.remove('hidden'); card.querySelector('.switch-camera').classList.remove('hidden');
            card.querySelector('.capture-state').textContent = 'Not captured'; card.querySelector('.capture-state').className = 'capture-state rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600';
            openCamera(card);
        });
    });
    form.addEventListener('submit', function (event) {
        var cards = document.querySelectorAll('.verification-capture');
        var complete = Object.keys(captures).length === cards.length && Array.prototype.every.call(cards, function (card) { return card.querySelector('.capture-file').files.length === 1; });
        if (!complete) { event.preventDefault(); showError('Capture all four live verification photos before submitting.'); cards[0].scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
        metadataInput.value = JSON.stringify(captures);
    });
    window.addEventListener('beforeunload', function () { document.querySelectorAll('.verification-capture').forEach(stop); });
})();
