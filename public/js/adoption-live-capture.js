document.addEventListener('DOMContentLoaded', function () {
  'use strict';
  var form = document.getElementById('adoptionApplication');
  if (!form) return;
  var section = document.getElementById('liveVerification');
  if (!section) return;
  var locationIntro = section.querySelector('h2 + p');
  if (locationIntro) locationIntro.textContent = 'Camera only. Each photo is stamped with date, time, and the device’s live GPS location—not the profile address.';
  var docs = [
    ['selfie', 'Live Selfie Verification', 'Use the front camera and complete the blink or head-turn challenge.', 'scan-face'],
    ['gov_id', 'Government-Issued ID', 'Capture your passport, driver’s license, or national ID.', 'credit-card'],
    ['address_proof', 'Proof of Address', 'Capture a current utility bill, lease, or bank statement.', 'file-text'],
    ['home_photos', 'Home / Living Space Photos', 'Capture the area where the cat will live.', 'house']
  ];
  if (section.querySelector('.verification-capture')) { docs = []; }
  else section.innerHTML = '<div class="mb-2 flex items-center gap-3 border-b border-gray-100 pb-4"><i data-lucide="shield-check" class="h-5 w-5 text-blue-600"></i><div><h2 class="text-xl font-semibold text-gray-900">Live Verification Photos</h2><p class="mt-1 text-sm text-gray-500">Use the in-app camera. Every photo is stamped with capture date, time, and the device’s current GPS location—not the profile address.</p></div></div><div id="captureError" class="mb-4 hidden rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700" role="alert"></div><input id="verificationMetadata" type="hidden" name="verification_metadata"><div id="captureCards" class="space-y-4"></div>';
  var cardList = document.getElementById('captureCards');
  docs.forEach(function (doc) {
    cardList.insertAdjacentHTML('beforeend', '<article class="verification-capture rounded-xl border border-gray-200 bg-gray-50 p-4" data-key="' + doc[0] + '"><div class="flex flex-wrap items-start justify-between gap-3"><div class="flex gap-3"><div class="rounded-lg bg-blue-100 p-2 text-blue-700"><i data-lucide="' + doc[3] + '" class="h-5 w-5"></i></div><div><h3 class="font-semibold text-gray-900">' + doc[1] + ' <span class="text-red-500">*</span></h3><p class="mt-1 text-xs leading-5 text-gray-500">' + doc[2] + '</p></div></div><span class="capture-state rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600">Not captured</span></div><input class="capture-file" type="file" name="verification_documents[' + doc[0] + ']" accept="image/jpeg" hidden><video class="camera-stream mt-4 hidden max-h-80 w-full rounded-lg bg-black object-contain" autoplay playsinline muted></video><canvas class="capture-canvas hidden"></canvas><button type="button" class="capture-preview-wrap mt-4 hidden w-full overflow-hidden rounded-lg border border-emerald-200 bg-white text-left"><img class="capture-preview block max-h-80 w-full object-contain" alt="Verification photo preview"><span class="block border-t border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800">Photo captured. Click to view full size and double-check it.</span></button><div class="mt-4 flex flex-wrap gap-2"><button type="button" class="start-camera inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="camera" class="h-4 w-4"></i>Open camera</button><button type="button" class="switch-camera hidden inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100"><i data-lucide="repeat-2" class="h-4 w-4"></i>Switch front/back</button><button type="button" class="capture-photo inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50" disabled><i data-lucide="aperture" class="h-4 w-4"></i>Capture photo</button><button type="button" class="retake-photo hidden inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100"><i data-lucide="refresh-cw" class="h-4 w-4"></i>Retake</button></div><p class="location-status mt-3 text-xs font-medium text-gray-500">Open the camera, capture the photo, then allow location access.</p></article>');
  });
  if (window.lucide) window.lucide.createIcons();
  var errorBox = document.getElementById('captureError'), meta = document.getElementById('verificationMetadata'), captures = {}, active = null, submitting = false, resolvedLocation = null;
  var faceLandmarkerPromise = null;
  var FACE_VISION_URL = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@1.2.0/vision_bundle.mjs';
  var FACE_WASM_URL = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@1.2.0/wasm';
  var FACE_MODEL_URL = 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task';
  function error(message) { errorBox.textContent = message; errorBox.classList.remove('hidden'); }
  function clearError() { errorBox.textContent = ''; errorBox.classList.add('hidden'); }
  function note(card, message, color) { var el = card.querySelector('.location-status'); el.textContent = message; el.className = 'location-status mt-3 text-xs font-medium ' + color; }
  function isSelfie(card) { return card.dataset.key === 'selfie'; }
  function ensureFacePanel(card) {
    if (!isSelfie(card) || card.querySelector('.face-liveness-panel')) return;
    var panel = document.createElement('div');
    panel.className = 'face-liveness-panel mt-3 hidden rounded-xl border border-blue-200 bg-blue-50 p-3';
    panel.innerHTML = '<div class="flex items-center gap-2"><span class="face-liveness-dot h-2.5 w-2.5 rounded-full bg-blue-500"></span><strong class="face-liveness-title text-sm text-blue-900">Face verification</strong></div><p class="face-liveness-message mt-1 text-xs leading-5 text-blue-800">Keep your face centered in the camera.</p>';
    card.querySelector('.camera-stream').insertAdjacentElement('afterend', panel);
  }
  function faceMessage(card, title, message, tone) {
    var panel = card.querySelector('.face-liveness-panel');
    if (!panel) return;
    var colors = tone === 'success'
      ? ['border-emerald-200', 'bg-emerald-50', 'bg-emerald-500', 'text-emerald-900', 'text-emerald-800']
      : tone === 'warning'
        ? ['border-amber-200', 'bg-amber-50', 'bg-amber-500', 'text-amber-900', 'text-amber-800']
        : ['border-blue-200', 'bg-blue-50', 'bg-blue-500', 'text-blue-900', 'text-blue-800'];
    panel.className = 'face-liveness-panel mt-3 rounded-xl border p-3 ' + colors[0] + ' ' + colors[1];
    panel.querySelector('.face-liveness-dot').className = 'face-liveness-dot h-2.5 w-2.5 rounded-full ' + colors[2];
    panel.querySelector('.face-liveness-title').className = 'face-liveness-title text-sm ' + colors[3];
    panel.querySelector('.face-liveness-message').className = 'face-liveness-message mt-1 text-xs leading-5 ' + colors[4];
    panel.querySelector('.face-liveness-title').textContent = title;
    panel.querySelector('.face-liveness-message').textContent = message;
  }
  async function getFaceLandmarker() {
    if (faceLandmarkerPromise) return faceLandmarkerPromise;
    faceLandmarkerPromise = (async function () {
      var visionModule = await import(FACE_VISION_URL);
      var vision = await visionModule.FilesetResolver.forVisionTasks(FACE_WASM_URL);
      var options = {
        baseOptions: { modelAssetPath: FACE_MODEL_URL, delegate: 'GPU' },
        runningMode: 'VIDEO',
        numFaces: 2,
        minFaceDetectionConfidence: 0.65,
        minFacePresenceConfidence: 0.65,
        minTrackingConfidence: 0.6,
        outputFaceBlendshapes: true
      };
      try {
        return await visionModule.FaceLandmarker.createFromOptions(vision, options);
      } catch (gpuError) {
        options.baseOptions = { modelAssetPath: FACE_MODEL_URL, delegate: 'CPU' };
        return visionModule.FaceLandmarker.createFromOptions(vision, options);
      }
    })().catch(function (reason) {
      faceLandmarkerPromise = null;
      throw reason;
    });
    return faceLandmarkerPromise;
  }
  function blendScore(result, name) {
    var groups = result.faceBlendshapes || [];
    if (!groups.length || !groups[0].categories) return 0;
    var category = groups[0].categories.find(function (item) { return item.categoryName === name; });
    return category ? category.score : 0;
  }
  function faceGeometry(landmarks) {
    var xs = landmarks.map(function (point) { return point.x; });
    var ys = landmarks.map(function (point) { return point.y; });
    var minX = Math.min.apply(Math, xs), maxX = Math.max.apply(Math, xs), minY = Math.min.apply(Math, ys), maxY = Math.max.apply(Math, ys);
    var width = maxX - minX, height = maxY - minY;
    var leftCheek = landmarks[234], rightCheek = landmarks[454], nose = landmarks[1];
    var cheekWidth = leftCheek && rightCheek ? Math.abs(rightCheek.x - leftCheek.x) : width;
    var cheekMiddle = leftCheek && rightCheek ? (leftCheek.x + rightCheek.x) / 2 : (minX + maxX) / 2;
    return { width: width, height: height, centerX: (minX + maxX) / 2, centerY: (minY + maxY) / 2, noseOffset: nose && cheekWidth > 0 ? (nose.x - cheekMiddle) / cheekWidth : 0 };
  }
  // Some mobile browsers deliver the user-facing camera as a mirrored stream.
  // Apply one controlled horizontal flip to both preview and canvas so the
  // applicant sees the same, correctly oriented result that is submitted.
  function setCameraOrientation(video, facing) { var transform = facing === 'user' ? 'scaleX(-1)' : 'none'; video.style.setProperty('transform', transform, 'important'); video.style.setProperty('-webkit-transform', transform, 'important'); }
  function resetFaceVerification(card) {
    if (card._faceAnimation) cancelAnimationFrame(card._faceAnimation);
    card._faceAnimation = null;
    card._faceRunning = false;
    card._faceStableFrames = 0;
    card._faceChallenge = null;
    card._faceChallengeStartedAt = 0;
    card._faceBaselineNose = 0;
    card._faceEyesWereOpen = false;
    card._faceLastDetectedAt = 0;
    card._livenessVerified = false;
    card._livenessVerifiedAt = 0;
    card._livenessChallenge = null;
    var button = card.querySelector('.capture-photo');
    button.disabled = true;
    button.textContent = isSelfie(card) ? 'Complete face check first' : 'Capture photo';
    var panel = card.querySelector('.face-liveness-panel');
    if (panel) panel.classList.add('hidden');
  }
  function stop(card) { if (card._stream) card._stream.getTracks().forEach(function (track) { track.stop(); }); card._stream = null; if (isSelfie(card)) resetFaceVerification(card); var video = card.querySelector('.camera-stream'); video.pause(); video.srcObject = null; setCameraOrientation(video, 'environment'); video.classList.add('hidden'); card.querySelector('.capture-photo').disabled = true; if (active === card) active = null; }
  function passFaceChallenge(card) {
    card._livenessVerified = true;
    card._livenessVerifiedAt = Date.now();
    card._livenessChallenge = card._faceChallenge;
    card._faceChallenge = null;
    card.querySelector('.capture-photo').disabled = false;
    card.querySelector('.capture-photo').textContent = 'Capture verified selfie';
    card.querySelector('.capture-state').textContent = 'Face verified';
    card.querySelector('.capture-state').className = 'capture-state rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700';
    faceMessage(card, 'Face verified', 'Challenge completed. Keep your face visible and capture within 30 seconds.', 'success');
  }
  function chooseFaceChallenge(card, geometry, result) {
    card._faceChallenge = Math.random() < 0.5 ? 'blink' : 'turn';
    card._faceChallengeStartedAt = Date.now();
    card._faceBaselineNose = geometry.noseOffset;
    card._faceEyesWereOpen = blendScore(result, 'eyeBlinkLeft') < 0.25 && blendScore(result, 'eyeBlinkRight') < 0.25;
    if (card._faceChallenge === 'blink') faceMessage(card, 'Liveness challenge', 'Blink both eyes once while keeping your face centered.', 'warning');
    else faceMessage(card, 'Liveness challenge', 'Turn your head slightly to either side, then face the camera again.', 'warning');
  }
  async function startFaceVerification(card) {
    ensureFacePanel(card);
    resetFaceVerification(card);
    faceMessage(card, 'Loading face verification', 'Preparing secure on-device face detection. This may take a few seconds on first use.', 'info');
    var landmarker = await getFaceLandmarker();
    if (!card._stream) return;
    card._faceRunning = true;
    var video = card.querySelector('.camera-stream');
    var lastProcessedAt = 0;
    function nextFrame() { if (card._faceRunning && card._stream) card._faceAnimation = requestAnimationFrame(processFrame); }
    function processFrame(timestamp) {
      if (!card._faceRunning || !card._stream) return;
      if (timestamp - lastProcessedAt < 160) return nextFrame();
      lastProcessedAt = timestamp;
      var result;
      try { result = landmarker.detectForVideo(video, timestamp); }
      catch (reason) { faceMessage(card, 'Face check paused', 'Hold still and keep your face visible. Detection will retry automatically.', 'warning'); return nextFrame(); }
      var faces = result.faceLandmarks || [];
      if (faces.length !== 1) {
        card._faceStableFrames = 0;
        card.querySelector('.capture-photo').disabled = true;
        faceMessage(card, 'Position your face', faces.length > 1 ? 'Only one person should be visible in the live selfie.' : 'No clear face detected. Face the camera in good lighting.', 'warning');
        return nextFrame();
      }
      var geometry = faceGeometry(faces[0]);
      var wellPositioned = geometry.width >= 0.20 && geometry.height >= 0.24 && geometry.centerX >= 0.25 && geometry.centerX <= 0.75 && geometry.centerY >= 0.22 && geometry.centerY <= 0.72;
      if (!wellPositioned) {
        card._faceStableFrames = 0;
        card.querySelector('.capture-photo').disabled = true;
        faceMessage(card, 'Center your face', 'Move closer and keep your full face inside the middle of the frame.', 'warning');
        return nextFrame();
      }
      card._faceLastDetectedAt = Date.now();
      if (card._livenessVerified) {
        if (Date.now() - card._livenessVerifiedAt > 30000) {
          card._livenessVerified = false;
          card._livenessVerifiedAt = 0;
          card._livenessChallenge = null;
          card._faceStableFrames = 0;
          card.querySelector('.capture-photo').disabled = true;
          card.querySelector('.capture-photo').textContent = 'Complete face check first';
          faceMessage(card, 'Verification expired', 'Hold still to receive a new liveness challenge.', 'warning');
        } else {
          card.querySelector('.capture-photo').disabled = false;
        }
        return nextFrame();
      }
      if (!card._faceChallenge) {
        card._faceStableFrames += 1;
        if (card._faceStableFrames < 5) faceMessage(card, 'Face detected', 'Hold still while we prepare your liveness challenge.', 'info');
        else chooseFaceChallenge(card, geometry, result);
        return nextFrame();
      }
      if (Date.now() - card._faceChallengeStartedAt > 20000) {
        card._faceChallenge = null;
        card._faceStableFrames = 0;
        faceMessage(card, 'Let’s try again', 'Keep your face centered to receive a new challenge.', 'warning');
        return nextFrame();
      }
      if (card._faceChallenge === 'blink') {
        var leftBlink = blendScore(result, 'eyeBlinkLeft'), rightBlink = blendScore(result, 'eyeBlinkRight');
        if (leftBlink < 0.25 && rightBlink < 0.25) card._faceEyesWereOpen = true;
        if (card._faceEyesWereOpen && leftBlink > 0.45 && rightBlink > 0.45) passFaceChallenge(card);
      } else if (Math.abs(geometry.noseOffset - card._faceBaselineNose) > 0.075) {
        passFaceChallenge(card);
      }
      nextFrame();
    }
    card._faceAnimation = requestAnimationFrame(processFrame);
  }
  function ready(video) { return new Promise(function (resolve, reject) { if (video.videoWidth && video.videoHeight) return resolve(); var timer = setTimeout(function () { reject(new Error('Camera frame did not load. Please reopen the camera.')); }, 6000); video.addEventListener('loadeddata', function done() { clearTimeout(timer); video.removeEventListener('loadeddata', done); resolve(); }); }); }
  function location(card) { return new Promise(function (resolve, reject) { if (!navigator.geolocation) return reject(new Error('GPS location is not supported by this browser.')); note(card, 'Getting current location…', 'text-gray-500'); navigator.geolocation.getCurrentPosition(function (position) { resolve(position.coords); }, function (reason) { reject(new Error('Location is required. Allow location access, then capture again. (' + reason.message + ')')); }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }); }); }
  function metersBetween(first, second) { var toRad = Math.PI / 180, lat1 = first.latitude * toRad, lat2 = second.latitude * toRad, dLat = (second.latitude - first.latitude) * toRad, dLon = (second.longitude - first.longitude) * toRad, a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) * Math.sin(dLon / 2); return 6371000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)); }
  function coordinateLocation(coords) { return 'GPS ' + coords.latitude.toFixed(6) + ', ' + coords.longitude.toFixed(6); }
  async function resolveGpsLocation(coords, card) {
    if (resolvedLocation && Date.now() - resolvedLocation.resolvedAt < 600000 && metersBetween(resolvedLocation, coords) <= 100) return resolvedLocation;
    var fallback = { location: coordinateLocation(coords), source: 'gps_coordinates', attribution: null, latitude: coords.latitude, longitude: coords.longitude, resolvedAt: Date.now() };
    var endpoint = form.dataset.reverseGeocodeUrl;
    if (!endpoint) return fallback;
    note(card, 'Reading current address…', 'text-gray-500');
    try {
      var url = new URL(endpoint, window.location.origin);
      url.searchParams.set('latitude', coords.latitude);
      url.searchParams.set('longitude', coords.longitude);
      var response = await fetch(url.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      if (!response.ok) return fallback;
      var data = await response.json();
      if (!data || typeof data.location !== 'string' || !data.location.trim()) return fallback;
      resolvedLocation = { location: data.location.trim(), source: data.source || 'gps_coordinates', attribution: data.attribution || null, latitude: coords.latitude, longitude: coords.longitude, resolvedAt: Date.now() };
      return resolvedLocation;
    } catch (reason) { return fallback; }
  }
  function wrapLine(ctx, text, maxWidth) { var words = text.split(/\s+/), lines = [], current = ''; words.forEach(function (word) { var candidate = current ? current + ' ' + word : word; if (!current || ctx.measureText(candidate).width <= maxWidth) { current = candidate; return; } lines.push(current); current = word; }); if (current) lines.push(current); return lines; }
  function stamp(ctx, w, h, place, coords, when) { var size = Math.max(15, Math.round(w / 55)), rawLines = ['CATCARE 360 • LIVE VERIFICATION', when.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'medium' }), 'ADDRESS: ' + place.location, 'GPS: ' + coords.latitude.toFixed(6) + ', ' + coords.longitude.toFixed(6) + '  ±' + Math.round(coords.accuracy) + ' m']; if (place.attribution) rawLines.push(place.attribution); ctx.font = '600 ' + size + 'px Arial'; var lines = []; rawLines.forEach(function (line) { lines = lines.concat(wrapLine(ctx, line, w - size * 2)); }); var box = Math.ceil(size * (lines.length * 1.5 + 1.4)); ctx.fillStyle = 'rgba(0,0,0,.8)'; ctx.fillRect(0, h - box, w, box); ctx.fillStyle = '#fff'; lines.forEach(function (line, i) { ctx.fillText(line, size, h - box + size * (1.35 + i * 1.5)); }); }
  async function streamFor(facing) { var settings = { width: { ideal: 1920 }, height: { ideal: 1080 } }; try { return await navigator.mediaDevices.getUserMedia({ video: Object.assign({}, settings, { facingMode: { exact: facing } }), audio: false }); } catch (reason) { if (reason.name !== 'OverconstrainedError' && reason.name !== 'NotFoundError') throw reason; return navigator.mediaDevices.getUserMedia({ video: Object.assign({}, settings, { facingMode: { ideal: facing } }), audio: false }); } }
  async function open(card) {
    clearError();
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return error('Use a current Chrome, Edge, Firefox, or Safari browser for live camera capture.');
    if (active && active !== card) stop(active);
    stop(card);
    try {
      if (isSelfie(card)) card._facing = 'user';
      var stream = await streamFor(card._facing || 'environment');
      card._stream = stream;
      active = card;
      var video = card.querySelector('.camera-stream');
      setCameraOrientation(video, card._facing);
      video.srcObject = stream;
      video.classList.remove('hidden');
      await video.play();
      await ready(video);
      card.querySelector('.start-camera').classList.add('hidden');
      if (isSelfie(card)) {
        card.querySelector('.switch-camera').classList.add('hidden');
        note(card, 'Front camera ready. Complete the face challenge before capturing.', 'text-blue-700');
        await startFaceVerification(card);
      } else {
        card.querySelector('.capture-photo').disabled = false;
        card.querySelector('.switch-camera').classList.remove('hidden');
        note(card, 'Camera ready in ' + (card._facing === 'user' ? 'front' : 'back') + ' mode. The preview and saved photo use the same orientation.', 'text-emerald-700');
      }
    } catch (reason) {
      stop(card);
      card.querySelector('.start-camera').classList.remove('hidden');
      error(isSelfie(card) ? 'Face verification could not start. Check your connection, then reopen the camera.' : (reason.message || 'Camera access is required. Allow it in browser permissions and try again.'));
    }
  }
  async function capture(card) {
    clearError();
    var video = card.querySelector('.camera-stream');
    if (!card._stream || !video.videoWidth || !video.videoHeight) return error('Camera is not ready. Open it again and wait for the preview.');
    if (isSelfie(card) && (!card._livenessVerified || Date.now() - card._livenessVerifiedAt > 30000 || Date.now() - card._faceLastDetectedAt > 1500)) {
      card.querySelector('.capture-photo').disabled = true;
      return error('Complete the live face challenge and keep your face visible before capturing the selfie.');
    }
    var button = card.querySelector('.capture-photo'), canvas = card.querySelector('.capture-canvas');
    button.disabled = true;
    var liveness = isSelfie(card) ? { verified: true, challenge: card._livenessChallenge, verified_at: new Date(card._livenessVerifiedAt).toISOString(), provider: 'mediapipe_face_landmarker', version: '1.2.0' } : null;
    var longest = Math.max(video.videoWidth, video.videoHeight), scale = Math.min(1, 1280 / longest);
    canvas.width = Math.round(video.videoWidth * scale);
    canvas.height = Math.round(video.videoHeight * scale);
    var ctx = canvas.getContext('2d'), when = new Date();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    if (card._facing === 'user') { ctx.translate(canvas.width, 0); ctx.scale(-1, 1); }
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    try {
      var coords = await location(card), place = await resolveGpsLocation(coords, card);
      stamp(ctx, canvas.width, canvas.height, place, coords, when);
      canvas.toBlob(function (blob) {
        if (!blob || blob.size < 8192) { button.disabled = false; return error('Could not create a usable photo. Please retake it.'); }
        var key = card.dataset.key, transfer = new DataTransfer();
        transfer.items.add(new File([blob], 'live-' + key + '-' + Date.now() + '.jpg', { type: 'image/jpeg' }));
        card.querySelector('.capture-file').files = transfer.files;
        var captureMetadata = { capture_source: 'in_app_camera', latitude: coords.latitude, longitude: coords.longitude, accuracy: coords.accuracy, captured_at: when.toISOString(), location: place.location, location_source: place.source, location_attribution: place.attribution };
        if (liveness) captureMetadata.face_liveness = liveness;
        captures[key] = captureMetadata;
        meta.value = JSON.stringify(captures);
        var preview = card.querySelector('.capture-preview');
        preview.src = canvas.toDataURL('image/jpeg', .82);
        card.querySelector('.capture-preview-wrap').classList.remove('hidden');
        video.classList.add('hidden');
        card.querySelector('.retake-photo').classList.remove('hidden');
        button.classList.add('hidden');
        card.querySelector('.switch-camera').classList.add('hidden');
        card.querySelector('.capture-state').textContent = isSelfie(card) ? 'Live selfie captured' : 'Captured';
        card.querySelector('.capture-state').className = 'capture-state rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700';
        note(card, (isSelfie(card) ? 'Verified live selfie captured at ' : 'Captured at ') + place.location + ' (GPS ±' + Math.round(coords.accuracy) + ' m)', 'text-emerald-700');
        stop(card);
      }, 'image/jpeg', .82);
    } catch (reason) {
      button.disabled = false;
      error(reason.message || 'Could not attach GPS information to this photo.');
    }
  }
  function zoom(card) { var source = card.querySelector('.capture-preview').src; if (!source) return; var modal = document.createElement('div'); modal.className = 'fixed inset-0 z-[200] flex items-center justify-center bg-black/80 p-4'; modal.innerHTML = '<button type="button" class="absolute right-4 top-4 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-800">Close</button><img class="max-h-full max-w-full rounded-lg object-contain" alt="Full-size verification capture">'; modal.querySelector('img').src = source; modal.querySelector('button').onclick = function () { modal.remove(); }; modal.onclick = function (event) { if (event.target === modal) modal.remove(); }; document.body.appendChild(modal); }
  document.querySelectorAll('.verification-capture').forEach(function (card) {
    card._facing = isSelfie(card) ? 'user' : 'environment';
    ensureFacePanel(card);
    if (isSelfie(card)) card.querySelector('.capture-photo').textContent = 'Complete face check first';
    card.querySelector('.start-camera').onclick = function () { open(card); };
    card.querySelector('.switch-camera').onclick = function () {
      if (isSelfie(card)) return;
      card._facing = card._facing === 'environment' ? 'user' : 'environment';
      open(card);
    };
    card.querySelector('.capture-photo').onclick = function () { capture(card); };
    card.querySelector('.capture-preview-wrap').onclick = function () { zoom(card); };
    card.querySelector('.retake-photo').onclick = function () {
      delete captures[card.dataset.key];
      meta.value = JSON.stringify(captures);
      card.querySelector('.capture-file').value = '';
      card.querySelector('.capture-preview-wrap').classList.add('hidden');
      card.querySelector('.retake-photo').classList.add('hidden');
      card.querySelector('.capture-photo').classList.remove('hidden');
      if (!isSelfie(card)) card.querySelector('.switch-camera').classList.remove('hidden');
      card.querySelector('.capture-state').textContent = 'Not captured';
      card.querySelector('.capture-state').className = 'capture-state rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600';
      open(card);
    };
  });
  form.addEventListener('submit', function (event) { var cards = document.querySelectorAll('.verification-capture'), valid = Object.keys(captures).length === cards.length && Array.prototype.every.call(cards, function (card) { return card.querySelector('.capture-file').files.length === 1; }); if (!valid) { event.preventDefault(); error('Capture all four live verification photos before submitting.'); cards[0].scrollIntoView({ behavior: 'smooth', block: 'center' }); return; } if (submitting) { event.preventDefault(); return; } submitting = true; meta.value = JSON.stringify(captures); var button = form.querySelector('button[type="submit"]'); button.disabled = true; button.querySelector('span').textContent = 'Submitting application…'; });
  window.addEventListener('beforeunload', function () { document.querySelectorAll('.verification-capture').forEach(stop); });
});
