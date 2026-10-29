(function () {
    function enhanceDarkCapture(canvas) {
        var context = canvas.getContext('2d'), image = context.getImageData(0, 0, canvas.width, canvas.height), pixels = image.data, total = 0, count = 0;
        for (var i = 0; i < pixels.length; i += 16) { total += (pixels[i] * .2126) + (pixels[i + 1] * .7152) + (pixels[i + 2] * .0722); count++; }
        if ((total / Math.max(count, 1)) >= 72) return;
        for (var j = 0; j < pixels.length; j += 4) { pixels[j] = Math.min(255, Math.pow(pixels[j] / 255, .62) * 255); pixels[j + 1] = Math.min(255, Math.pow(pixels[j + 1] / 255, .62) * 255); pixels[j + 2] = Math.min(255, Math.pow(pixels[j + 2] / 255, .62) * 255); }
        context.putImageData(image, 0, 0);
    }
    var startBtn = document.getElementById('startCameraBtn');
    var switchBtn = document.getElementById('switchCameraBtn');
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
    var photoError = document.getElementById('photoError');
    var form = document.getElementById('updateForm');
    var currentPlace = null;
    var stream = null;
    var facingMode = 'environment';
    var cameraReady = false;
    var locationReady = false;

    if (!startBtn) return;

    function showFullscreenPreview() {
        if (!preview || !preview.src) return;
        var modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-[200] flex items-center justify-center bg-black/90 p-4';
        modal.innerHTML = '<button type="button" class="absolute right-5 top-5 rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-800">Close</button><img class="max-h-full max-w-full rounded-lg object-contain" alt="Captured update preview">';
        modal.querySelector('img').src = preview.src;
        modal.querySelector('button').onclick = function () { modal.remove(); };
        modal.onclick = function (event) { if (event.target === modal) modal.remove(); };
        document.body.appendChild(modal);
    }

    function showPhotoError(message) {
        if (!photoError) return;
        photoError.textContent = message;
        photoError.classList.remove('hidden');
    }

    function showLocationError(message) {
        locationStatusText.textContent = message;
        locationStatus.classList.remove('hidden');
        locationStatus.classList.remove('text-emerald-600');
        locationStatus.classList.add('text-red-600');
        locationReady = false;
        updateCaptureButton();
    }

    function updateCaptureButton() {
        captureBtn.disabled = !(cameraReady && locationReady);
    }

    function coordinateLocation(coords) {
        return 'GPS ' + coords.latitude.toFixed(6) + ', ' + coords.longitude.toFixed(6);
    }

    async function resolveGpsLocation(coords) {
        var fallback = { location: coordinateLocation(coords), source: 'gps_coordinates', attribution: null };
        if (!form || !form.dataset.reverseGeocodeUrl) return fallback;
        try {
            var url = new URL(form.dataset.reverseGeocodeUrl, window.location.origin);
            url.searchParams.set('latitude', coords.latitude);
            url.searchParams.set('longitude', coords.longitude);
            var response = await fetch(url.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!response.ok) return fallback;
            var data = await response.json();
            return data && data.location ? { location: data.location, source: data.source || 'gps_coordinates', attribution: data.attribution || null } : fallback;
        } catch (reason) { return fallback; }
    }

    async function saveLocation(pos) {
        latitudeInput.value = pos.coords.latitude;
        longitudeInput.value = pos.coords.longitude;
        accuracyInput.value = pos.coords.accuracy;
        locationStatusText.textContent = 'GPS found. Reading the current place/address…';
        currentPlace = await resolveGpsLocation(pos.coords);
        locationReady = true;
        locationStatusText.textContent = currentPlace.location + ' (±' + Math.round(pos.coords.accuracy) + ' m) ✓';
        locationStatus.classList.remove('text-gray-500', 'text-red-600');
        locationStatus.classList.add('text-emerald-600');
        locationBtn.disabled = false;
        updateCaptureButton();
    }

    function captureLocation() {
        if (!navigator.geolocation) {
            showLocationError('Geolocation is not supported by this browser.');
            return;
        }
        locationReady = false;
        latitudeInput.value = '';
        longitudeInput.value = '';
        accuracyInput.value = '';
        currentPlace = null;
        updateCaptureButton();
        locationBtn.disabled = true;
        locationStatusText.textContent = 'Getting your current location…';
        locationStatus.classList.remove('hidden');
        locationStatus.classList.remove('text-red-600');
        locationStatus.classList.add('text-gray-500');

        navigator.geolocation.getCurrentPosition(saveLocation, function () {
            locationStatusText.textContent = 'Finding a fresh location…';
            navigator.geolocation.getCurrentPosition(saveLocation, function (err) {
                showLocationError('Location is required: ' + err.message + '. Turn on Windows Location and allow this site in the browser lock icon.');
                locationBtn.disabled = false;
            }, { enableHighAccuracy: false, timeout: 12000, maximumAge: 0 });
        }, { enableHighAccuracy: false, timeout: 5000, maximumAge: 300000 });
    }

    async function requestCamera() {
        if (helpEl) helpEl.classList.add('hidden');
        captureLocation();

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (helpEl) {
                helpEl.textContent = 'This browser does not support live camera capture. Please use a current Chrome, Edge, Firefox, or Safari browser.';
                helpEl.classList.remove('hidden');
            }
            return;
        }

        try {
            var cameraRequest = navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: facingMode }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false
            });
            var permissionTimedOut = false;
            var timeoutRequest = new Promise(function (_, reject) {
                window.setTimeout(function () {
                    permissionTimedOut = true;
                    reject(new Error('Camera permission timed out.'));
                }, 15000);
            });
            cameraRequest.then(function (lateStream) {
                if (permissionTimedOut) lateStream.getTracks().forEach(function (track) { track.stop(); });
            });
            stream = await Promise.race([cameraRequest, timeoutRequest]);
            video.srcObject = stream;
            video.classList.remove('hidden');
            await video.play();
            cameraReady = true;
            updateCaptureButton();
            startBtn.classList.add('hidden');
            switchBtn.classList.remove('hidden');
        } catch (err) {
            if (helpEl) {
                helpEl.textContent = err && err.message === 'Camera permission timed out.'
                    ? 'Camera permission did not respond. Open this site in Chrome or Edge, allow Camera from the lock icon, then try again.'
                    : 'Camera access is required. Allow it from the browser permission prompt or lock icon, then try again.';
                helpEl.classList.remove('hidden');
            }
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
        video.srcObject = null;
        video.classList.add('hidden');
        cameraReady = false;
        updateCaptureButton();
        startBtn.classList.remove('hidden');
        switchBtn.classList.add('hidden');
    }

    startBtn.addEventListener('click', requestCamera);

    switchBtn.addEventListener('click', function () {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        stopCamera();
        requestCamera();
    });

    captureBtn.addEventListener('click', function () {
        if (!video.videoWidth || !video.videoHeight) {
            showPhotoError('Camera is still starting. Please wait a moment and try Capture again.');
            return;
        }
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        var context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        var now = new Date();
        var fontSize = Math.max(16, Math.round(canvas.width / 48));
        var stampLines = [
            'CATCARE 360 • LIVE CAMERA',
            now.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'medium' }),
            'CURRENT GPS LOCATION: ' + (currentPlace ? currentPlace.location : coordinateLocation({ latitude: Number(latitudeInput.value), longitude: Number(longitudeInput.value) })),
            'GPS: ' + Number(latitudeInput.value).toFixed(6) + ', ' + Number(longitudeInput.value).toFixed(6) + '  ±' + Math.round(Number(accuracyInput.value)) + ' m'
        ];
        if (currentPlace && currentPlace.attribution) stampLines.push(currentPlace.attribution);
        context.font = '600 ' + fontSize + 'px Arial, sans-serif';
        var wrappedStampLines = [];
        stampLines.forEach(function (line) {
            var words = line.split(/\s+/), current = '';
            words.forEach(function (word) {
                var candidate = current ? current + ' ' + word : word;
                if (!current || context.measureText(candidate).width <= canvas.width - fontSize * 2) { current = candidate; return; }
                wrappedStampLines.push(current);
                current = word;
            });
            if (current) wrappedStampLines.push(current);
        });
        var stampHeight = (fontSize * 1.45 * wrappedStampLines.length) + (fontSize * 1.5);
        context.fillStyle = 'rgba(0, 0, 0, 0.68)';
        context.fillRect(0, canvas.height - stampHeight, canvas.width, stampHeight);
        context.fillStyle = '#ffffff';
        wrappedStampLines.forEach(function (line, index) {
            context.fillText(line, fontSize, canvas.height - stampHeight + fontSize * (1.35 + (index * 1.45)));
        });
        enhanceDarkCapture(canvas);
        canvas.toBlob(function (blob) {
            if (!blob) {
                showPhotoError('Could not create the camera photo. Please retake it.');
                return;
            }
            var file = new File([blob], 'live-update-' + Date.now() + '.jpg', { type: 'image/jpeg' });
            var dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            hiddenInput.files = dataTransfer.files;
            captureSourceInput.value = 'in_app_camera';
            deviceInfoInput.value = JSON.stringify({
                platform: navigator.platform || '', language: navigator.language || '',
                screen: (window.screen.width || '') + 'x' + (window.screen.height || ''),
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '', capturedAt: new Date().toISOString(),
                location: currentPlace ? currentPlace.location : '', locationSource: currentPlace ? currentPlace.source : 'gps_coordinates'
            });
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            captureBtn.classList.add('hidden');
            retakeBtn.classList.remove('hidden');
            if (photoError) photoError.classList.add('hidden');
            stopCamera();
        }, 'image/jpeg', 0.92);
    });

    retakeBtn.addEventListener('click', function () {
        hiddenInput.value = '';
        captureSourceInput.value = '';
        preview.removeAttribute('src');
        preview.classList.add('hidden');
        retakeBtn.classList.add('hidden');
        captureBtn.classList.remove('hidden');
        requestCamera();
    });

    preview.addEventListener('click', showFullscreenPreview);
    preview.addEventListener('touchend', function (event) { event.preventDefault(); showFullscreenPreview(); }, { passive: false });

    locationBtn.addEventListener('click', captureLocation);

    form.addEventListener('submit', function (event) {
        if (!hiddenInput.files.length || captureSourceInput.value !== 'in_app_camera') {
            event.preventDefault();
            showPhotoError('Please capture a live photo using the in-app camera before submitting.');
            startBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        if (!latitudeInput.value || !longitudeInput.value || !accuracyInput.value) {
            event.preventDefault();
            showPhotoError('Please allow and capture your current location before submitting.');
            locationBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();
