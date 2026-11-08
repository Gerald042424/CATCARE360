(function () {
    var form = document.querySelector('form[action*="/report"]');
    var fileInput = form && form.querySelector('input[name="photo"]');
    if (!form || !fileInput || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;

    fileInput.accept = 'image/jpeg';
    fileInput.classList.add('hidden');
    var area = document.createElement('div');
    area.className = 'rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4';
    area.innerHTML = '<video class="hidden w-full max-h-72 rounded-lg bg-black object-cover" playsinline muted></video><p class="lighting-status mt-2 hidden text-sm font-semibold"></p><canvas class="hidden"></canvas><div class="preview-wrap hidden overflow-hidden rounded-lg border border-emerald-200 bg-white"><img class="capture-preview block w-full max-h-72 object-contain" alt="Captured live task report photo"><p class="preview-caption border-t border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800"></p></div><div class="mt-3 flex flex-wrap gap-2"><button type="button" class="start-camera btn btn-ghost">Open Camera</button><button type="button" class="switch-camera btn btn-ghost hidden">Switch to Front Camera</button><button type="button" class="capture-photo btn bg-emerald-600 text-white" disabled>Capture Photo</button><button type="button" class="retake-photo btn btn-ghost hidden">Retake</button></div><p class="camera-status mt-3 text-sm text-gray-500">A live photo and current GPS location are required.</p><p class="geotag-status mt-1 break-words text-xs text-gray-500"></p>';
    fileInput.parentNode.insertBefore(area, fileInput);
    ['latitude', 'longitude', 'gps_accuracy', 'capture_source', 'captured_at_client'].forEach(function (name) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
    });

    var video = area.querySelector('video');
    var canvas = area.querySelector('canvas');
    var previewWrap = area.querySelector('.preview-wrap');
    var preview = area.querySelector('.capture-preview');
    var previewCaption = area.querySelector('.preview-caption');
    var start = area.querySelector('.start-camera');
    var switchCamera = area.querySelector('.switch-camera');
    var capture = area.querySelector('.capture-photo');
    var retake = area.querySelector('.retake-photo');
    var status = area.querySelector('.camera-status');
    var geotag = area.querySelector('.geotag-status');
    var lightingStatus = area.querySelector('.lighting-status');
    var stream;
    var coords;
    var currentPlace;
    var cameraFacing = 'environment';
    var cameraReady = false;
    var sceneReady = false;
    var lightingTimer = null;
    var qualityCanvas = document.createElement('canvas');
    qualityCanvas.width = 160;
    qualityCanvas.height = 120;

    function updateCaptureButton() {
        capture.disabled = !(cameraReady && coords && currentPlace && sceneReady);
    }

    function showLightingStatus(message, passed) {
        lightingStatus.textContent = message;
        lightingStatus.classList.remove('hidden', 'text-red-600', 'text-emerald-600');
        lightingStatus.classList.add(passed ? 'text-emerald-600' : 'text-red-600');
    }

    function analyzeCameraFrame() {
        if (!cameraReady || video.readyState < 2 || !video.videoWidth) {
            sceneReady = false;
            updateCaptureButton();
            return false;
        }

        var qualityContext = qualityCanvas.getContext('2d', { willReadFrequently: true });
        qualityContext.drawImage(video, 0, 0, qualityCanvas.width, qualityCanvas.height);
        var pixels = qualityContext.getImageData(0, 0, qualityCanvas.width, qualityCanvas.height).data;
        var total = 0;
        var totalSquares = 0;
        var darkPixels = 0;
        var samples = 0;
        for (var index = 0; index < pixels.length; index += 4) {
            var brightness = (pixels[index] * 0.2126) + (pixels[index + 1] * 0.7152) + (pixels[index + 2] * 0.0722);
            total += brightness;
            totalSquares += brightness * brightness;
            if (brightness < 42) darkPixels++;
            samples++;
        }
        var average = total / Math.max(samples, 1);
        var deviation = Math.sqrt(Math.max(0, (totalSquares / Math.max(samples, 1)) - (average * average)));
        var darkRatio = darkPixels / Math.max(samples, 1);

        if (average < 48 || darkRatio > 0.82) {
            sceneReady = false;
            showLightingStatus('Too dark — turn on a light or move to a brighter area before capturing.', false);
        } else if (deviation < 8) {
            sceneReady = false;
            showLightingStatus('No clear scene detected — uncover the camera and point it at the cat or task area.', false);
        } else {
            sceneReady = true;
            showLightingStatus('Lighting and camera view look clear. You can capture now.', true);
        }
        updateCaptureButton();
        return sceneReady;
    }

    function startLightingMonitor() {
        if (lightingTimer) window.clearInterval(lightingTimer);
        analyzeCameraFrame();
        lightingTimer = window.setInterval(analyzeCameraFrame, 600);
    }

    function stopLightingMonitor() {
        if (lightingTimer) window.clearInterval(lightingTimer);
        lightingTimer = null;
        sceneReady = false;
    }

    function stop() {
        stopLightingMonitor();
        if (stream) stream.getTracks().forEach(function (track) { track.stop(); });
        stream = null;
        video.srcObject = null;
        video.classList.add('hidden');
        cameraReady = false;
        lightingStatus.classList.add('hidden');
        updateCaptureButton();
    }

    function location() {
        return new Promise(function (resolve, reject) {
            navigator.geolocation.getCurrentPosition(function (position) {
                resolve(position.coords);
            }, function () {
                reject(new Error('Location permission is required.'));
            }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
        });
    }

    function coordinateLocation(position) {
        return 'GPS ' + position.latitude.toFixed(6) + ', ' + position.longitude.toFixed(6);
    }

    async function resolveGpsLocation(position) {
        var fallback = { location: coordinateLocation(position), source: 'gps_coordinates', attribution: null };
        try {
            var endpoint = new URL(form.action);
            endpoint.pathname = endpoint.pathname.replace(/\/volunteer\/task\/\d+\/report\/?$/, '/volunteer/location/reverse');
            endpoint.search = '';
            endpoint.searchParams.set('latitude', position.latitude);
            endpoint.searchParams.set('longitude', position.longitude);
            var response = await fetch(endpoint.toString(), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) return fallback;
            var data = await response.json();
            if (!data || !data.location) return fallback;
            return {
                location: data.location,
                source: data.source || 'gps_coordinates',
                attribution: data.attribution || null
            };
        } catch (error) {
            return fallback;
        }
    }

    function wrapLine(context, line, maxWidth) {
        var words = line.split(/\s+/);
        var lines = [];
        var current = '';
        words.forEach(function (word) {
            var candidate = current ? current + ' ' + word : word;
            if (!current || context.measureText(candidate).width <= maxWidth) {
                current = candidate;
                return;
            }
            lines.push(current);
            current = word;
        });
        if (current) lines.push(current);
        return lines;
    }

    async function openCamera() {
        try {
            stop();
            currentPlace = null;
            status.textContent = 'Getting current GPS location…';
            coords = await location();
            status.textContent = 'Reading current address…';
            currentPlace = await resolveGpsLocation(coords);
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: cameraFacing }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false
            });
            video.srcObject = stream;
            video.classList.remove('hidden');
            await video.play();
            cameraReady = true;
            startLightingMonitor();
            updateCaptureButton();
            start.classList.add('hidden');
            switchCamera.classList.remove('hidden');
            switchCamera.textContent = cameraFacing === 'environment' ? 'Switch to Front Camera' : 'Switch to Back Camera';
            status.textContent = 'Camera, address, and location are ready.';
            geotag.textContent = currentPlace.location + ' · GPS ' + coords.latitude.toFixed(6) + ', ' + coords.longitude.toFixed(6) + ' (±' + Math.round(coords.accuracy) + ' m)';
        } catch (error) {
            status.textContent = error.message || 'Allow camera and location access, then try again.';
        }
    }

    start.addEventListener('click', openCamera);
    switchCamera.addEventListener('click', function () {
        cameraFacing = cameraFacing === 'environment' ? 'user' : 'environment';
        openCamera();
    });

    capture.addEventListener('click', function () {
        if (!coords || !currentPlace || video.readyState < 2 || !video.videoWidth) {
            status.textContent = 'Camera or address is still loading. Wait a moment, then capture again.';
            return;
        }
        if (!analyzeCameraFrame()) {
            status.textContent = 'The photo cannot be captured until the camera view is bright and clear.';
            return;
        }

        var capturedAt = new Date();
        var capturedAtLabel = capturedAt.toLocaleString('en-PH', {
            timeZone: 'Asia/Manila',
            dateStyle: 'medium',
            timeStyle: 'medium'
        });
        var gpsLine = 'GPS: ' + coords.latitude.toFixed(6) + ', ' + coords.longitude.toFixed(6) + ' (±' + Math.round(coords.accuracy) + ' m)';
        var stampLines = [
            'CATCARE 360 • LIVE VOLUNTEER REPORT',
            'ADDRESS: ' + currentPlace.location,
            gpsLine,
            'CAPTURED: ' + capturedAtLabel
        ];
        if (currentPlace.attribution) stampLines.push(currentPlace.attribution);

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        var context = canvas.getContext('2d');
        var fontSize = Math.max(16, Math.round(canvas.width / 52));
        var padding = Math.round(fontSize * .8);
        var lineHeight = Math.round(fontSize * 1.45);
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        context.font = '600 ' + fontSize + 'px Arial, sans-serif';
        context.textBaseline = 'middle';

        var lines = [];
        stampLines.forEach(function (line) {
            lines = lines.concat(wrapLine(context, line, canvas.width - (padding * 2)));
        });
        var boxHeight = (lineHeight * lines.length) + (padding * 2);
        context.fillStyle = 'rgba(0,0,0,.76)';
        context.fillRect(0, canvas.height - boxHeight, canvas.width, boxHeight);
        context.fillStyle = '#ffffff';
        lines.forEach(function (line, index) {
            context.fillText(line, padding, canvas.height - boxHeight + padding + (lineHeight * index) + (lineHeight / 2));
        });

        var previewData = canvas.toDataURL('image/jpeg', .9);
        canvas.toBlob(function (blob) {
            if (!blob) {
                status.textContent = 'Could not create the photo. Please retake it.';
                return;
            }
            var file = new File([blob], 'live-volunteer-report-' + Date.now() + '.jpg', { type: 'image/jpeg' });
            var transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;
            form.elements.latitude.value = coords.latitude;
            form.elements.longitude.value = coords.longitude;
            form.elements.gps_accuracy.value = coords.accuracy;
            form.elements.capture_source.value = 'in_app_camera';
            form.elements.captured_at_client.value = capturedAt.toISOString();
            preview.onload = function () {
                previewWrap.classList.remove('hidden');
                previewWrap.style.cssText = 'display:block!important;min-height:220px';
                preview.style.cssText = 'display:block!important;width:100%;min-height:220px;max-height:420px;object-fit:contain';
            };
            preview.src = previewData;
            previewCaption.textContent = currentPlace.location + ' · ' + gpsLine + ' · Captured: ' + capturedAtLabel;
            previewWrap.classList.remove('hidden');
            previewWrap.style.cssText = 'display:block!important;min-height:220px';
            preview.style.cssText = 'display:block!important;width:100%;min-height:220px;max-height:420px;object-fit:contain';
            retake.classList.remove('hidden');
            capture.classList.add('hidden');
            switchCamera.classList.add('hidden');
            stop();
            status.textContent = 'Live photo captured with address, GPS, date, and time.';
            geotag.textContent = currentPlace.location + ' · ' + gpsLine + ' · Captured: ' + capturedAtLabel;
        }, 'image/jpeg', .9);
    });

    retake.addEventListener('click', function () {
        fileInput.value = '';
        preview.removeAttribute('src');
        previewWrap.classList.add('hidden');
        previewWrap.style.display = '';
        retake.classList.add('hidden');
        capture.classList.remove('hidden');
        start.classList.remove('hidden');
        switchCamera.classList.add('hidden');
        geotag.textContent = '';
        coords = null;
        currentPlace = null;
        form.elements.latitude.value = '';
        form.elements.longitude.value = '';
        form.elements.gps_accuracy.value = '';
        form.elements.capture_source.value = '';
        form.elements.captured_at_client.value = '';
    });

    form.addEventListener('submit', function (event) {
        if (!fileInput.files.length || form.elements.capture_source.value !== 'in_app_camera') {
            event.preventDefault();
            status.textContent = 'Capture a live photo before submitting the report.';
        }
    });
    window.addEventListener('beforeunload', stop);
})();
