(function () {
    var catDetection = { threshold: 0.50, intervalMs: 350, consecutiveFrames: 3, staleMs: 1000 };
    var visionAssets = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@1.2.0';
    var catModel = 'https://storage.googleapis.com/mediapipe-models/object_detector/efficientdet_lite0/int8/1/efficientdet_lite0.tflite';
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
    var detectionStatus = document.getElementById('catDetectionStatus');
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
    var sceneReady = false;
    var lightingTimer = null;
    var capturePending = false;

    if (!startBtn) return;

    var attemptInput = form.elements.capture_attempt;
    var attemptUntil = 0;
    var detectorPromise;
    var detectionTimer = null;
    var detectedFrames = 0;
    var lastDetectionAt = -Infinity;
    var lastVideoTime = -1;
    var detectorCanvas = document.createElement('canvas');
    var detectorContext = detectorCanvas.getContext('2d', { willReadFrequently: true });
    var replayDebug = new URLSearchParams(window.location.search).get('replay_debug') === '1';
    var replayWorkerUrl = new URL('adopter-pet-replay-worker.js', document.currentScript.src);
    replayWorkerUrl.search = new URL(document.currentScript.src).search;
    var replayWorker = null;
    var replayCanvas = null;
    var replayPending = false;
    var replayUnavailable = false;
    var replayVideoTime = -Infinity;

    function stopReplayDiagnostics() {
        if (replayWorker) replayWorker.terminate();
        replayWorker = null;
        replayCanvas = null;
        replayPending = false;
        replayVideoTime = -Infinity;
    }

    function sampleReplayDiagnostics(box) {
        if (replayUnavailable || replayPending || video.currentTime - replayVideoTime < 0.7) return;
        try {
            if (!replayWorker) {
                if (typeof Worker !== 'function') {
                    replayUnavailable = true;
                    return;
                }
                var started = performance.now();
                replayWorker = new Worker(replayWorkerUrl);
                replayWorker.onmessage = function (event) {
                    if (event.data.type === 'ready') {
                        if (replayDebug) console.info('[CatCare 360 replay shadow] Worker startup (ms)', Math.round(performance.now() - started));
                        return;
                    }
                    replayPending = false;
                    if (event.data.type === 'result' && replayDebug) console.info('[CatCare 360 replay shadow]', event.data);
                };
                replayWorker.onerror = function () {
                    stopReplayDiagnostics();
                    replayUnavailable = true;
                    if (replayDebug) console.info('[CatCare 360 replay shadow] INCONCLUSIVE: diagnostics worker unavailable.');
                };
                replayCanvas = document.createElement('canvas');
            }

            var scaleX = video.videoWidth / detectorCanvas.width;
            var scaleY = video.videoHeight / detectorCanvas.height;
            var left = Math.max(0, Math.floor(box.originX * scaleX));
            var top = Math.max(0, Math.floor(box.originY * scaleY));
            var width = Math.min(video.videoWidth - left, Math.ceil(box.width * scaleX));
            var height = Math.min(video.videoHeight - top, Math.ceil(box.height * scaleY));
            if (width < 1 || height < 1) return;

            function pixels(x, y, w, h, maxSide) {
                var scale = Math.min(1, maxSide / Math.max(w, h));
                replayCanvas.width = Math.max(1, Math.round(w * scale));
                replayCanvas.height = Math.max(1, Math.round(h * scale));
                var context = replayCanvas.getContext('2d', { willReadFrequently: true });
                context.drawImage(video, x, y, w, h, 0, 0, replayCanvas.width, replayCanvas.height);
                return { width: replayCanvas.width, height: replayCanvas.height, rgba: context.getImageData(0, 0, replayCanvas.width, replayCanvas.height).data };
            }

            // Keep the entire scene for display boundaries and a closer cat crop
            // for texture. Neither pixel buffer is stored or uploaded.
            var scene = pixels(0, 0, video.videoWidth, video.videoHeight, 384);
            var subject = pixels(left, top, width, height, 192);
            var catBox = {
                left: Math.floor(left * scene.width / video.videoWidth),
                top: Math.floor(top * scene.height / video.videoHeight),
                right: Math.ceil((left + width) * scene.width / video.videoWidth),
                bottom: Math.ceil((top + height) * scene.height / video.videoHeight)
            };
            replayPending = true;
            replayVideoTime = video.currentTime;
            replayWorker.postMessage({ scene: scene, subject: subject, catBox: catBox, videoTime: replayVideoTime }, [scene.rgba.buffer, subject.rgba.buffer]);
        } catch (reason) {
            stopReplayDiagnostics();
            replayUnavailable = true;
            console.info('[CatCare 360 replay shadow] INCONCLUSIVE: camera sampling unavailable.');
        }
    }

    function loadDetector() {
        if (!detectorPromise) {
            detectorPromise = import(visionAssets + '/vision_bundle.mjs').then(async function (vision) {
                var files = await vision.FilesetResolver.forVisionTasks(visionAssets + '/wasm');
                return vision.ObjectDetector.createFromOptions(files, {
                    baseOptions: { modelAssetPath: catModel, delegate: 'CPU' },
                    runningMode: 'VIDEO',
                    categoryAllowlist: ['cat'],
                    scoreThreshold: catDetection.threshold
                });
            }).catch(function (reason) {
                detectorPromise = null;
                throw reason;
            });
        }
        return detectorPromise;
    }

    function detectCat(detector, frame, width, height) {
        var scale = Math.min(1, 320 / Math.max(width, height));
        var inputWidth = Math.round(width * scale), inputHeight = Math.round(height * scale);
        if (detectorCanvas.width !== inputWidth) detectorCanvas.width = inputWidth;
        if (detectorCanvas.height !== inputHeight) detectorCanvas.height = inputHeight;
        detectorContext.drawImage(frame, 0, 0, detectorCanvas.width, detectorCanvas.height);
        return detector.detectForVideo(detectorCanvas, performance.now()).detections.find(function (detection) {
            return detection.categories.some(function (category) {
                return category.categoryName === 'cat' && category.score >= catDetection.threshold;
            });
        });
    }

    function stopCatDetection() {
        stopReplayDiagnostics();
        window.clearInterval(detectionTimer);
        detectionTimer = null;
        detectedFrames = 0;
        lastDetectionAt = -Infinity;
        lastVideoTime = -1;
    }

    async function startCatDetection(cameraStream) {
        detectionStatus.textContent = 'Checking camera and loading cat detection…';
        try {
            var detector = await loadDetector();
            if (stream !== cameraStream) return;
            detectionTimer = window.setInterval(function () {
                try {
                    if (video.readyState >= 2 && video.videoWidth && video.currentTime !== lastVideoTime) {
                        lastVideoTime = video.currentTime;
                        var cat = detectCat(detector, video, video.videoWidth, video.videoHeight);
                        if (cat) {
                            if (performance.now() - lastDetectionAt > catDetection.staleMs) {
                                detectedFrames = 0;
                                if (replayWorker) stopReplayDiagnostics();
                            }
                            detectedFrames = Math.min(detectedFrames + 1, catDetection.consecutiveFrames);
                            lastDetectionAt = performance.now();
                            detectionStatus.textContent = detectedFrames >= catDetection.consecutiveFrames
                                ? 'Cat detected. Capture is available when lighting and GPS are ready.'
                                : 'Cat detected. Keep your cat in view…';
                            if (detectedFrames === catDetection.consecutiveFrames) sampleReplayDiagnostics(cat.boundingBox);
                        } else {
                            if (replayWorker) stopReplayDiagnostics();
                            detectedFrames = 0;
                            lastDetectionAt = -Infinity;
                            detectionStatus.textContent = 'No cat detected. Position your cat within the camera view.';
                        }
                    }
                    updateCaptureButton();
                } catch (reason) {
                    stopCatDetection();
                    detectionStatus.textContent = 'Cat detection stopped. Reopen the camera to try again.';
                    stopCamera(true);
                }
            }, catDetection.intervalMs);
        } catch (reason) {
            if (stream !== cameraStream) return;
            detectionStatus.textContent = 'Cat detection could not load. Check your connection and reopen the camera.';
            stopCamera(true);
        }
    }

    async function authorizeCapture(blob, captureStream) {
        var digest = await crypto.subtle.digest('SHA-256', await blob.arrayBuffer());
        var photoHash = Array.from(new Uint8Array(digest), function (byte) {
            return byte.toString(16).padStart(2, '0');
        }).join('');
        if (stream !== captureStream) return null;
        // The nonce authorizes this capture attempt. It does not attest to liveness.
        var challenge = await requestCameraAuthorization(form.dataset.cameraChallengeUrl, {});
        if (stream !== captureStream) return null;
        if (!challenge.nonce) throw new Error('Capture authorization was unavailable.');
        return requestCameraAuthorization(form.dataset.captureAttemptUrl, {
            photo_sha256: photoHash, challenge_nonce: challenge.nonce
        });
    }

    async function requestCameraAuthorization(url, fields) {
        var data = new FormData();
        data.set('cat_id', form.elements.cat_id.value);
        Object.keys(fields).forEach(function (field) { data.set(field, fields[field]); });
        var csrf = form.elements.namedItem(form.dataset.csrfField);
        data.set(csrf.name, csrf.value);
        var response = await fetch(url, {
            method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' }
        });
        if (response.redirected) throw new Error('Sign in again if your session expired.');
        var result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Camera authorization was unavailable.');
        return result;
    }

    var lightingStatus = document.createElement('p');
    lightingStatus.className = 'mt-2 hidden text-sm font-semibold';
    video.insertAdjacentElement('afterend', lightingStatus);
    var qualityCanvas = document.createElement('canvas');
    qualityCanvas.width = 160;
    qualityCanvas.height = 120;
    function catDetectionReady() {
        return detectedFrames >= catDetection.consecutiveFrames
            && performance.now() - lastDetectionAt <= catDetection.staleMs;
    }

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

    function cameraCaptureReady() {
        return cameraReady && locationReady && sceneReady && catDetectionReady();
    }

    function updateCaptureButton() {
        captureBtn.disabled = !cameraCaptureReady() || capturePending;
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
            showLightingStatus('No clear scene detected — uncover the camera and point it at the cat or area.', false);
        } else {
            sceneReady = true;
            showLightingStatus('Lighting and camera view look clear.', true);
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

    function showFullCameraFrame() {
        video.style.width = '100%';
        video.style.height = 'auto';
        video.style.maxHeight = '70vh';
        video.style.objectFit = 'contain';
        video.style.aspectRatio = video.videoWidth + ' / ' + video.videoHeight;
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
        locationStatusText.textContent = 'Reading current address…';
        currentPlace = await resolveGpsLocation(pos.coords);
        locationReady = true;
        locationStatusText.textContent = currentPlace.location + ' (GPS ±' + Math.round(pos.coords.accuracy) + ' m) ✓';
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
        locationStatusText.textContent = 'Getting current location…';
        locationStatus.classList.remove('hidden');
        locationStatus.classList.remove('text-red-600');
        locationStatus.classList.add('text-gray-500');

        navigator.geolocation.getCurrentPosition(saveLocation, function (err) {
            showLocationError('Location is required: ' + err.message + '. Allow location access in the browser, then try again.');
            locationBtn.disabled = false;
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    }

    async function requestCamera() {
        if (startBtn.disabled) return;
        if (helpEl) helpEl.classList.add('hidden');
        captureLocation();

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (helpEl) {
                helpEl.textContent = 'This browser does not support live camera capture. Please use a current Chrome, Edge, Firefox, or Safari browser.';
                helpEl.classList.remove('hidden');
            }
            return;
        }

        startBtn.disabled = true;
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
            var openedStream = stream;
            stream.getVideoTracks().forEach(function (track) {
                track.addEventListener('ended', function () {
                    if (stream === openedStream) {
                        stopCamera();
                        showPhotoError('The camera disconnected. Reopen it to continue.');
                    }
                });
            });
            video.srcObject = stream;
            video.classList.remove('hidden');
            await video.play();
            showFullCameraFrame();
            cameraReady = true;
            startLightingMonitor();
            startCatDetection(stream);
            updateCaptureButton();
            startBtn.classList.add('hidden');
            switchBtn.classList.remove('hidden');
        } catch (err) {
            stopCamera();
            if (helpEl) {
                helpEl.textContent = err && err.message === 'Camera permission timed out.'
                    ? 'Camera permission did not respond. Open this site in Chrome or Edge, allow Camera from the lock icon, then try again.'
                    : err && err.name === 'NotFoundError' ? 'No camera was found on this device.'
                    : err && err.name === 'NotReadableError' ? 'The camera is unavailable. Close other apps using it and try again.'
                    : 'Camera access is required for verification. Please allow camera permission and try again.';
                helpEl.classList.remove('hidden');
            }
        } finally {
            startBtn.disabled = false;
        }
    }

    function stopCamera(keepDetectionMessage) {
        stopCatDetection();
        stopLightingMonitor();
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
        video.srcObject = null;
        video.classList.add('hidden');
        cameraReady = false;
        if (!keepDetectionMessage) detectionStatus.textContent = '';
        lightingStatus.classList.add('hidden');
        updateCaptureButton();
        startBtn.classList.remove('hidden');
        switchBtn.classList.add('hidden');
    }

    startBtn.addEventListener('click', requestCamera);

    switchBtn.addEventListener('click', function () {
        if (capturePending) return;
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        stopCamera();
        requestCamera();
    });

    captureBtn.addEventListener('click', function () {
        updateCaptureButton();
        if (captureBtn.disabled) return;
        if (!video.videoWidth || !video.videoHeight) {
            showPhotoError('Camera is still starting. Please wait a moment and try Capture again.');
            return;
        }
        if (!analyzeCameraFrame()) {
            showPhotoError('The photo cannot be captured until the camera view is bright and clear.');
            return;
        }
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        var context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        // Verify the actual still before the GPS stamp is applied.
        var captureStream = stream;
        capturePending = true;
        updateCaptureButton();
        finishCapture(context, captureStream).catch(function (reason) {
            if (stream === captureStream) showPhotoError(reason.message || 'Could not save the camera photo. Please try again.');
        }).finally(function () {
            capturePending = false;
            updateCaptureButton();
        });
    });

    async function finishCapture(context, captureStream) {
        var detector = await loadDetector();
        if (stream !== captureStream) return;
        if (!cameraCaptureReady()) return;
        if (!detectCat(detector, canvas, canvas.width, canvas.height)) {
            detectedFrames = 0;
            lastDetectionAt = -Infinity;
            detectionStatus.textContent = 'No cat detected in the photo. Position your cat and capture again.';
            return;
        }
        var now = new Date();
        var fontSize = Math.max(16, Math.round(canvas.width / 48));
        var stampLines = [
            'CATCARE 360 • LIVE CAMERA',
            now.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'medium' }),
            'ADDRESS: ' + (currentPlace ? currentPlace.location : coordinateLocation({ latitude: Number(latitudeInput.value), longitude: Number(longitudeInput.value) })),
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
        var blob = await new Promise(function (resolve) { canvas.toBlob(resolve, 'image/jpeg', 0.92); });
        if (!blob) throw new Error('Could not create the camera photo. Please retake it.');
        if (stream !== captureStream) return;
        var attempt = await authorizeCapture(blob, captureStream);
        if (stream !== captureStream || !attempt) return;
        if (!attempt.token || !attempt.expires_in) throw new Error('Capture authorization was unavailable.');
        var file = new File([blob], 'live-update-' + Date.now() + '.jpg', { type: 'image/jpeg' });
        var dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        hiddenInput.files = dataTransfer.files;
        attemptInput.value = attempt.token;
        attemptUntil = Date.now() + attempt.expires_in * 1000;
        captureSourceInput.value = 'in_app_camera';
        deviceInfoInput.value = JSON.stringify({
            platform: navigator.platform || '', language: navigator.language || '',
            screen: (window.screen.width || '') + 'x' + (window.screen.height || ''),
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '', capturedAt: new Date().toISOString(),
            location: currentPlace ? currentPlace.location : '', locationSource: currentPlace ? currentPlace.source : 'gps_coordinates'
        });
        var previewReader = new FileReader();
        previewReader.onload = function () {
            preview.src = previewReader.result;
            preview.classList.remove('hidden');
            preview.style.cssText = 'display:block!important;width:100%;min-height:220px;max-height:420px;object-fit:contain';
        };
        previewReader.onerror = function () {
            showPhotoError('The photo was captured but its preview could not be displayed. Please retake it.');
        };
        previewReader.readAsDataURL(file);
        preview.classList.remove('hidden');
        preview.style.cssText = 'display:block!important;width:100%;min-height:220px;max-height:420px;object-fit:contain';
        captureBtn.classList.add('hidden');
        retakeBtn.classList.remove('hidden');
        if (photoError) photoError.classList.add('hidden');
        stopCamera();
        startBtn.classList.add('hidden');
    }

    retakeBtn.addEventListener('click', function () {
        hiddenInput.value = '';
        captureSourceInput.value = '';
        attemptInput.value = '';
        attemptUntil = 0;
        preview.removeAttribute('src');
        preview.classList.add('hidden');
        preview.style.cssText = '';
        retakeBtn.classList.add('hidden');
        captureBtn.classList.remove('hidden');
        requestCamera();
    });

    preview.addEventListener('click', showFullscreenPreview);
    preview.addEventListener('touchend', function (event) { event.preventDefault(); showFullscreenPreview(); }, { passive: false });

    locationBtn.addEventListener('click', captureLocation);
    window.addEventListener('pagehide', function () { stopCamera(); });

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
            return;
        }
        if (!attemptInput.value || Date.now() >= attemptUntil) {
            event.preventDefault();
            showPhotoError('Your capture authorization expired. Retake the photo before submitting.');
        }
    });
})();
