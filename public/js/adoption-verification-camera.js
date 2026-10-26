(function () {
    function enhanceDarkCapture(canvas) {
        var context = canvas.getContext('2d'), image = context.getImageData(0, 0, canvas.width, canvas.height), pixels = image.data, total = 0, count = 0;
        for (var i = 0; i < pixels.length; i += 16) { total += (pixels[i] * .2126) + (pixels[i + 1] * .7152) + (pixels[i + 2] * .0722); count++; }
        if ((total / Math.max(count, 1)) >= 72) return;
        for (var j = 0; j < pixels.length; j += 4) { pixels[j] = Math.min(255, Math.pow(pixels[j] / 255, .62) * 255); pixels[j + 1] = Math.min(255, Math.pow(pixels[j + 1] / 255, .62) * 255); pixels[j + 2] = Math.min(255, Math.pow(pixels[j + 2] / 255, .62) * 255); }
        context.putImageData(image, 0, 0);
    }
    var form = document.querySelector('form[action*="adopter/apply"]');
    var errorBox = document.getElementById('captureError');
    var metadataInput = document.getElementById('verificationMetadata');
    var captures = {};
    var activeCard = null;

    if (!form || !metadataInput) return;

    function showError(message) { errorBox.textContent = message; errorBox.classList.remove('hidden'); }
    function clearError() { errorBox.classList.add('hidden'); }
    function stopCard(card) {
        var stream = card._stream;
        if (stream) stream.getTracks().forEach(function (track) { track.stop(); });
        card._stream = null;
        card.querySelector('.camera-stream').srcObject = null;
        card.querySelector('.camera-stream').classList.add('hidden');
        card.querySelector('.capture-photo').disabled = true;
        if (activeCard === card) activeCard = null;
    }
    function locationFor(card) {
        return new Promise(function (resolve, reject) {
            if (!navigator.geolocation) return reject(new Error('Geolocation is not supported by this browser.'));
            var status = card.querySelector('.location-status');
            status.textContent = 'Getting your current location…'; status.className = 'location-status mt-3 text-xs font-medium text-gray-500';
            navigator.geolocation.getCurrentPosition(function (position) {
                status.textContent = 'Location ready (±' + Math.round(position.coords.accuracy) + ' m)'; status.className = 'location-status mt-3 text-xs font-medium text-emerald-700';
                resolve(position.coords);
            }, function (err) { reject(new Error('Location is required: ' + err.message)); }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
        });
    }
    async function start(card) {
        clearError();
        if (activeCard && activeCard !== card) stopCard(activeCard);
        var photoButton = card.querySelector('.capture-photo');
        try {
            var coords = await locationFor(card);
            card._coords = coords;
            var stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: card._facingMode || 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } }, audio: false });
            card._stream = stream; activeCard = card;
            var video = card.querySelector('.camera-stream'); video.srcObject = stream; video.classList.remove('hidden'); await video.play();
            photoButton.disabled = false; card.querySelector('.start-camera').classList.add('hidden'); card.querySelector('.switch-camera').classList.remove('hidden');
        } catch (err) { showError(err.message || 'Camera access is required. Allow camera and location access, then try again.'); }
    }
    function capture(card) {
        var video = card.querySelector('.camera-stream'); var canvas = card.querySelector('.capture-canvas'); var coords = card._coords;
        if (!video.videoWidth || !coords) return showError('Wait for both camera and location before capturing.');
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        var context = canvas.getContext('2d'); context.drawImage(video, 0, 0, canvas.width, canvas.height);
        var now = new Date(), size = Math.max(16, Math.round(canvas.width / 48));
        var addressInput = form.querySelector('input[name="address"]');
        var address = addressInput ? addressInput.value.trim() : '';
        if (!address) return showError('Enter your complete address before taking verification photos.');
        var lines = ['CATCARE 360 • LIVE VERIFICATION', now.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'medium' }), 'ADDRESS: ' + address, 'GPS: ' + coords.latitude.toFixed(6) + ', ' + coords.longitude.toFixed(6) + '  ±' + Math.round(coords.accuracy) + ' m'];
        var height = size * (lines.length * 1.45 + 1.5); context.fillStyle = 'rgba(0,0,0,.7)'; context.fillRect(0, canvas.height - height, canvas.width, height); context.fillStyle = '#fff'; context.font = '600 ' + size + 'px Arial';
        lines.forEach(function (line, index) { context.fillText(line, size, canvas.height - height + size * (1.35 + index * 1.45)); });
        enhanceDarkCapture(canvas);
        canvas.toBlob(function (blob) {
            if (!blob) return showError('Could not create the photo. Please retake it.');
            var key = card.dataset.key, file = new File([blob], 'live-' + key + '-' + Date.now() + '.jpg', { type: 'image/jpeg' }), transfer = new DataTransfer(); transfer.items.add(file); card.querySelector('.capture-file').files = transfer.files;
            captures[key] = { capture_source: 'in_app_camera', latitude: coords.latitude, longitude: coords.longitude, accuracy: coords.accuracy, captured_at: now.toISOString(), device_info: { platform: navigator.platform || '', timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '' } };
            metadataInput.value = JSON.stringify(captures);
            var preview = card.querySelector('.capture-preview'); preview.src = URL.createObjectURL(file); preview.classList.remove('hidden'); card.querySelector('.retake-photo').classList.remove('hidden'); card.querySelector('.capture-photo').classList.add('hidden'); card.querySelector('.switch-camera').classList.add('hidden'); card.querySelector('.capture-state').textContent = 'Captured'; card.querySelector('.capture-state').className = 'capture-state rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700'; stopCard(card); clearError();
        }, 'image/jpeg', .92);
    }
    document.querySelectorAll('.verification-capture').forEach(function (card) {
        card.querySelector('.start-camera').addEventListener('click', function () { start(card); });
        card.querySelector('.switch-camera').addEventListener('click', function () { card._facingMode = (card._facingMode || 'environment') === 'environment' ? 'user' : 'environment'; stopCard(card); start(card); });
        card.querySelector('.capture-photo').addEventListener('click', function () { capture(card); });
        card.querySelector('.retake-photo').addEventListener('click', function () { var key = card.dataset.key; delete captures[key]; metadataInput.value = JSON.stringify(captures); card.querySelector('.capture-file').value = ''; card.querySelector('.capture-preview').classList.add('hidden'); card.querySelector('.retake-photo').classList.add('hidden'); card.querySelector('.capture-photo').classList.remove('hidden'); card.querySelector('.capture-state').textContent = 'Not captured'; card.querySelector('.capture-state').className = 'capture-state rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600'; start(card); });
    });
    form.addEventListener('submit', function (event) { if (Object.keys(captures).length !== 4) { event.preventDefault(); showError('Capture all four live verification photos before submitting.'); document.querySelector('.verification-capture').scrollIntoView({ behavior: 'smooth', block: 'center' }); } else metadataInput.value = JSON.stringify(captures); });
    window.addEventListener('beforeunload', function () { document.querySelectorAll('.verification-capture').forEach(stopCard); });
})();
