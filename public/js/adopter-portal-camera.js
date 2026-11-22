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
    var checkBtn = document.getElementById('cameraCheckBtn');
    var checkStatus = document.getElementById('cameraCheckStatus');
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
    var cameraCheckRunning = false;
    var cameraCheckUntil = 0;
    var challengeNonce = '';
    var latestScene = null;
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
                                invalidateCameraCheck();
                            }
                            latestScene = sampleCheckFrame(cat.boundingBox);
                            detectedFrames = Math.min(detectedFrames + 1, catDetection.consecutiveFrames);
                            lastDetectionAt = performance.now();
                            detectionStatus.textContent = detectedFrames >= catDetection.consecutiveFrames
                                ? 'Cat detected. Complete the camera check and GPS to capture.'
                                : 'Cat detected. Keep your cat in view…';
                        } else {
                            detectedFrames = 0;
                            lastDetectionAt = -Infinity;
                            latestScene = null;
                            invalidateCameraCheck();
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

    async function authorizeCapture(blob) {
        var digest = await crypto.subtle.digest('SHA-256', await blob.arrayBuffer());
        var photoHash = Array.from(new Uint8Array(digest), function (byte) {
            return byte.toString(16).padStart(2, '0');
        }).join('');
        return requestCameraAuthorization(form.dataset.captureAttemptUrl, {
            photo_sha256: photoHash, challenge_nonce: challengeNonce
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
    var checkCanvas = document.createElement('canvas');
    checkCanvas.width = 96;
    checkCanvas.height = 64;
    var checkContext = checkCanvas.getContext('2d', { willReadFrequently: true });

    function normalizedCatBox(box) {
        return {
            left: box.originX * 96 / detectorCanvas.width,
            top: box.originY * 64 / detectorCanvas.height,
            width: box.width * 96 / detectorCanvas.width,
            height: box.height * 64 / detectorCanvas.height
        };
    }

    function sampleCheckFrame(box) {
        checkContext.drawImage(detectorCanvas, 0, 0, 96, 64);
        var pixels = checkContext.getImageData(0, 0, 96, 64).data;
        return {
            box: normalizedCatBox(box),
            pixels: Uint8Array.from({ length: 96 * 64 }, function (_, i) {
                return (pixels[i * 4] + pixels[i * 4 + 1] * 2 + pixels[i * 4 + 2]) / 4;
            })
        };
    }

    function patchShift(before, after, x, y) {
        var stationaryError, bestError = Infinity, bestShift = 0;
        for (var shift = -16; shift <= 16; shift += 2) {
            var error = 0;
            for (var row = -4; row <= 4; row += 2) {
                for (var column = -4; column <= 4; column += 2) {
                    var offset = (y + row) * 96 + x + column;
                    error += Math.abs(before[offset] - after[offset + shift]);
                }
            }
            if (shift === 0) stationaryError = error;
            if (error < bestError) {
                bestError = error;
                bestShift = shift;
            }
        }
        return stationaryError >= 120 && bestError < stationaryError * 0.65 ? bestShift : 0;
    }

    function hasRequestedPan(before, after, direction) {
        var box = before.box, end = after.box;
        var catTravel = (end.left + end.width / 2 - box.left - box.width / 2) * direction;
        if (catTravel < 2 || catTravel > 20 || end.width < box.width * 0.65 || end.width > box.width * 1.5) return false;

        // Lateral translation should move nearby and distant detail by
        // different amounts. A single plane shifting uniformly fails.
        var subject = [], surroundings = [];
        for (var y = 10; y <= 55; y += 5) {
            for (var x = 21; x <= 74; x += 5) {
                var inside = x >= box.left + 4 && x <= box.left + box.width - 4
                    && y >= box.top + 4 && y <= box.top + box.height - 4;
                var outside = x <= box.left - 5 || x >= box.left + box.width + 5
                    || y <= box.top - 5 || y >= box.top + box.height + 5;
                if (!inside && !outside) continue;
                var movement = patchShift(before.pixels, after.pixels, x, y) * direction;
                if (movement >= 2) (inside ? subject : surroundings).push(movement);
            }
        }
        if (subject.length < 3 || surroundings.length < 4) return false;
        subject.sort(function (a, b) { return a - b; });
        surroundings.sort(function (a, b) { return a - b; });
        var catMotion = subject[Math.floor(subject.length / 2)];
        var sceneMotion = surroundings[Math.floor(surroundings.length / 2)];
        return catMotion >= sceneMotion + 2 && Math.abs(catMotion - catTravel) <= 5;
    }

    function catDetectionReady() {
        return detectedFrames >= catDetection.consecutiveFrames
            && performance.now() - lastDetectionAt <= catDetection.staleMs;
    }

    function invalidateCameraCheck() {
        if (cameraCheckUntil) {
            cameraCheckUntil = 0;
            challengeNonce = '';
            checkStatus.textContent = 'Camera check reset. Keep your cat visible and try again.';
        }
    }

    async function runCameraCheck() {
        if (!cameraReady || !sceneReady || !catDetectionReady() || cameraCheckRunning || capturePending) return;
        var checkStream = stream;
        cameraCheckRunning = true;
        cameraCheckUntil = 0;
        challengeNonce = '';
        checkStatus.textContent = 'Preparing camera check…';
        updateCaptureButton();
        if (photoError) photoError.classList.add('hidden');

        try {
            var challenge = await requestCameraAuthorization(form.dataset.cameraChallengeUrl, {});
            if (stream !== checkStream) return;
            if (!challenge.nonce || ![-1, 1].includes(challenge.direction)) throw new Error('Camera challenge was unavailable.');
            for (var direction of [challenge.direction, -challenge.direction]) {
                if (!catDetectionReady()) throw new Error('Keep your cat visible throughout the camera check.');
                var initial = latestScene, previous = initial, matches = 0;
                checkStatus.textContent = 'Slide your phone slightly to the ' + (direction === 1 ? 'left' : 'right') + '. Keep it facing your cat and show the area behind them.';
                var deadline = performance.now() + 4500;
                while (performance.now() < deadline && matches < 2) {
                    await new Promise(function (resolve) { window.setTimeout(resolve, 120); });
                    if (stream !== checkStream) return;
                    if (!sceneReady || !catDetectionReady()) throw new Error('Keep your cat visible in a well-lit scene and try again.');
                    var current = latestScene;
                    if (current === previous) continue;
                    previous = current;
                    matches = hasRequestedPan(initial, current, direction) ? matches + 1 : 0;
                }
                if (matches < 2) throw new Error('Could not verify the camera movement. Show more of the area behind your cat and slide the phone slowly.');
            }
            challengeNonce = challenge.nonce;
            cameraCheckUntil = Date.now() + 15000;
        } catch (reason) {
            if (stream === checkStream) checkStatus.textContent = reason.message || 'Could not check the live camera. Try again.';
        } finally {
            cameraCheckRunning = false;
            updateCaptureButton();
        }
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

    function updateCaptureButton() {
        if (cameraCheckUntil && Date.now() >= cameraCheckUntil) {
            cameraCheckUntil = 0;
            challengeNonce = '';
            checkStatus.textContent = 'Camera check expired. Start another check to capture your photo.';
        }
        var catReady = catDetectionReady();
        if (!catReady || !sceneReady) invalidateCameraCheck();
        checkBtn.disabled = !cameraReady || !sceneReady || !catReady || cameraCheckRunning || capturePending;
        if (cameraCheckUntil) {
            var message = catReady ? 'Camera check passed. Take the photo within 15 seconds.' : '';
            if (checkStatus.textContent !== message) checkStatus.textContent = message;
        }
        captureBtn.disabled = !(cameraReady && locationReady && sceneReady && cameraCheckUntil && catReady) || capturePending;
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
            checkBtn.hidden = false;
            checkStatus.textContent = 'Frame your cat and their surroundings, then start the camera check.';
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
        cameraCheckUntil = 0;
        challengeNonce = '';
        latestScene = null;
        checkBtn.hidden = true;
        checkStatus.textContent = '';
        if (!keepDetectionMessage) detectionStatus.textContent = '';
        lightingStatus.classList.add('hidden');
        updateCaptureButton();
        startBtn.classList.remove('hidden');
        switchBtn.classList.add('hidden');
    }

    startBtn.addEventListener('click', requestCamera);
    checkBtn.addEventListener('click', runCameraCheck);

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
        if (!cameraCheckUntil || Date.now() >= cameraCheckUntil || !challengeNonce || !catDetectionReady() || !sceneReady || !locationReady) return;
        var cat = detectCat(detector, canvas, canvas.width, canvas.height);
        var box = cat && normalizedCatBox(cat.boundingBox);
        var previousBox = latestScene.box;
        if (!box || Math.abs(box.left + box.width / 2 - previousBox.left - previousBox.width / 2) > 12
            || Math.abs(box.top + box.height / 2 - previousBox.top - previousBox.height / 2) > 8) {
            invalidateCameraCheck();
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
        if (!challengeNonce || Date.now() >= cameraCheckUntil || !catDetectionReady() || !sceneReady || !locationReady) {
            throw new Error('The camera check expired or lost the cat. Start another check.');
        }
        var attempt = await authorizeCapture(blob);
        if (stream !== captureStream) return;
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
