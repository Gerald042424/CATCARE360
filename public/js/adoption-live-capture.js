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
    ['gov_id', 'Valid Government-Issued ID', 'Scan an original ID that belongs to the applicant and is unexpired when an expiry date applies.', 'credit-card'],
    ['address_proof', 'Proof of Address', 'Capture a current utility bill, lease, or bank statement.', 'file-text'],
    ['home_photos', 'Indoor Living Area', 'Capture a wide, well-lit view of the actual indoor area where the cat will live.', 'house'],
    ['home_access', 'Windows / Outdoor Access Area', 'Show windows, doors, balcony, or outdoor access for escape-prevention review.', 'door-open']
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
  var TESSERACT_URL = 'https://cdn.jsdelivr.net/npm/tesseract.js@6.0.1/dist/tesseract.min.js';
  var tesseractWorkerPromise = null;
  function error(message) { errorBox.textContent = message; errorBox.classList.remove('hidden'); }
  function clearError() { errorBox.textContent = ''; errorBox.classList.add('hidden'); }
  function note(card, message, color) {
    var el = card.querySelector('.location-status');
    if (isSelfie(card) && card._stream && !captures.selfie) {
      el.textContent = '';
      el.className = 'location-status hidden';
      return;
    }
    el.textContent = message;
    el.className = 'location-status mt-3 text-xs font-medium ' + color;
  }
  function isSelfie(card) { return card.dataset.key === 'selfie'; }
  function isGovernmentId(card) { return card.dataset.key === 'gov_id'; }
  function isAddressProof(card) { return card.dataset.key === 'address_proof'; }
  function isHomeScene(card) { return card.dataset.key === 'home_photos' || card.dataset.key === 'home_access'; }
  function homeSceneType(card) { return card.dataset.key === 'home_access' ? 'windows_outdoor_access' : 'indoor_living_area'; }
  function stopCaptureQualityMonitor(card) {
    if (card._sceneTimer) window.clearInterval(card._sceneTimer);
    card._sceneTimer = null;
    card._sceneReady = false;
  }
  function ensureFacePanel(card) {
    if (!isSelfie(card) || card.querySelector('.face-liveness-panel')) return;
    var video = card.querySelector('.camera-stream'), frame = document.createElement('div');
    frame.className = 'selfie-camera-frame relative mt-4 hidden overflow-hidden rounded-xl bg-slate-950';
    video.parentNode.insertBefore(frame, video);
    frame.appendChild(video);
    video.classList.remove('rounded-lg');
    video.classList.add('block', 'w-full');
    frame.insertAdjacentHTML('beforeend', '<div class="pointer-events-none absolute inset-0 overflow-hidden"><div class="face-guide absolute left-1/2 top-[47%] h-[66%] w-[58%] max-w-[21rem] -translate-x-1/2 -translate-y-1/2 rounded-[50%] border-[3px] border-white/90 shadow-[0_0_0_999px_rgba(2,6,23,.30)] transition-colors duration-200"></div><div class="absolute left-1/2 top-3 -translate-x-1/2 whitespace-nowrap rounded-full border border-white/20 bg-slate-950/75 px-3 py-1.5 text-[11px] font-semibold text-white backdrop-blur-sm">Keep your full face inside the oval</div><div class="face-liveness-panel absolute inset-x-3 bottom-3 hidden rounded-xl border border-white/25 bg-slate-950/80 p-3 text-white shadow-lg backdrop-blur-sm"><div class="flex items-center gap-2"><span class="face-liveness-dot h-2.5 w-2.5 rounded-full bg-sky-400"></span><strong class="face-liveness-title text-sm text-white">Face verification</strong></div><p class="face-liveness-message mt-1 text-xs leading-5 text-slate-100">Keep your face centered in the camera.</p></div><div class="selfie-capture-flash absolute inset-0 hidden bg-white/80"></div></div>');
  }
  function faceMessage(card, title, message, tone) {
    var panel = card.querySelector('.face-liveness-panel');
    if (!panel) return;
    var success = tone === 'success', warning = tone === 'warning';
    panel.className = 'face-liveness-panel absolute inset-x-3 bottom-3 rounded-xl border p-3 text-white shadow-lg backdrop-blur-sm ' + (success ? 'border-emerald-300/70 bg-emerald-950/85' : (warning ? 'border-amber-300/70 bg-amber-950/85' : 'border-sky-300/60 bg-slate-950/85'));
    panel.querySelector('.face-liveness-dot').className = 'face-liveness-dot h-2.5 w-2.5 rounded-full ' + (success ? 'bg-emerald-400' : (warning ? 'bg-amber-400' : 'bg-sky-400'));
    panel.querySelector('.face-liveness-title').className = 'face-liveness-title text-sm text-white';
    panel.querySelector('.face-liveness-message').className = 'face-liveness-message mt-1 text-xs leading-5 text-slate-100';
    panel.querySelector('.face-liveness-title').textContent = title;
    panel.querySelector('.face-liveness-message').textContent = message;
    var guide = card.querySelector('.face-guide');
    if (guide) guide.className = 'face-guide absolute left-1/2 top-[47%] h-[66%] w-[58%] max-w-[21rem] -translate-x-1/2 -translate-y-1/2 rounded-[50%] border-[3px] shadow-[0_0_0_999px_rgba(2,6,23,.30)] transition-colors duration-200 ' + (success ? 'border-emerald-400' : (warning ? 'border-amber-300' : 'border-white/90'));
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
    card._faceBlinkDetected = false;
    card._faceTurnDetected = false;
    card._faceLastDetectedAt = 0;
    card._livenessVerified = false;
    card._livenessVerifiedAt = 0;
    card._livenessChallenge = null;
    card._faceAutoCapturePending = false;
    var button = card.querySelector('.capture-photo');
    button.disabled = true;
    button.textContent = isSelfie(card) ? 'Automatic capture after face check' : 'Capture photo';
    var panel = card.querySelector('.face-liveness-panel');
    if (panel) panel.classList.add('hidden');
  }
  function stop(card) {
    stopCaptureQualityMonitor(card);
    if (card._idCountdownTimer) window.clearTimeout(card._idCountdownTimer);
    card._idCountdownTimer = null;
    card._idAutoCapturePending = false;
    if (card._stream) card._stream.getTracks().forEach(function (track) { track.stop(); });
    card._stream = null;
    if (isSelfie(card)) resetFaceVerification(card);
    var video = card.querySelector('.camera-stream');
    video.pause();
    video.srcObject = null;
    setCameraOrientation(video, 'environment');
    video.classList.add('hidden');
    if (isSelfie(card)) {
      var selfieFrame = card.querySelector('.selfie-camera-frame');
      if (selfieFrame) selfieFrame.classList.add('hidden');
    }
    if (isGovernmentId(card)) {
      var frame = card.querySelector('.id-camera-frame'), scan = card.querySelector('.scan-id');
      if (frame) frame.classList.add('hidden');
      if (scan) scan.classList.add('hidden');
    }
    card.querySelector('.capture-photo').disabled = true;
    if (active === card) active = null;
  }
  function passFaceChallenge(card) {
    if (card._faceAutoCapturePending) return;
    card._livenessVerified = true;
    card._livenessVerifiedAt = Date.now();
    card._livenessChallenge = card._faceChallenge;
    card._faceChallenge = null;
    card._faceAutoCapturePending = true;
    card.querySelector('.capture-photo').disabled = true;
    card.querySelector('.capture-photo').textContent = 'Capturing verified selfie…';
    card.querySelector('.capture-state').textContent = 'Face verified';
    card.querySelector('.capture-state').className = 'capture-state rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700';
    faceMessage(card, 'Challenge complete', 'Stay still. Taking your verified selfie now…', 'success');
    var video = card.querySelector('.camera-stream'), verifiedFrame = frameCanvas(video, 1280), flash = card.querySelector('.selfie-capture-flash');
    if (flash) { flash.classList.remove('hidden'); window.setTimeout(function () { flash.classList.add('hidden'); }, 180); }
    capture(card, { sourceCanvas: verifiedFrame, automaticSelfieCapture: true });
  }
  function chooseFaceChallenge(card, geometry, result) {
    card._faceChallenge = Math.random() < 0.5 ? 'blink' : 'turn';
    card._faceChallengeStartedAt = Date.now();
    card._faceBaselineNose = geometry.noseOffset;
    card._faceEyesWereOpen = blendScore(result, 'eyeBlinkLeft') < 0.25 && blendScore(result, 'eyeBlinkRight') < 0.25;
    card._faceBlinkDetected = false;
    card._faceTurnDetected = false;
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
          card.querySelector('.capture-photo').disabled = card._faceAutoCapturePending;
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
        if (!card._faceBlinkDetected && card._faceEyesWereOpen && leftBlink > 0.45 && rightBlink > 0.45) {
          card._faceBlinkDetected = true;
          faceMessage(card, 'Blink detected', 'Open your eyes and look at the camera for the automatic snapshot.', 'info');
        } else if (card._faceBlinkDetected && leftBlink < 0.25 && rightBlink < 0.25) passFaceChallenge(card);
      } else {
        var turnDistance = Math.abs(geometry.noseOffset - card._faceBaselineNose);
        if (!card._faceTurnDetected && turnDistance > 0.075) {
          card._faceTurnDetected = true;
          faceMessage(card, 'Head turn detected', 'Face the camera again for the automatic snapshot.', 'info');
        } else if (card._faceTurnDetected && turnDistance < 0.035) passFaceChallenge(card);
      }
      nextFrame();
    }
    card._faceAnimation = requestAnimationFrame(processFrame);
  }
  function loadScriptOnce(url, marker) {
    if (marker()) return Promise.resolve();
    var existing = document.querySelector('script[data-catcare-src="' + url + '"]');
    if (existing && existing._loadPromise) return existing._loadPromise;
    var script = existing || document.createElement('script');
    script.dataset.catcareSrc = url;
    script.src = url;
    script.crossOrigin = 'anonymous';
    script._loadPromise = new Promise(function (resolve, reject) {
      if (marker()) return resolve();
      script.addEventListener('load', resolve, { once: true });
      script.addEventListener('error', function () { reject(new Error('The ID scanner could not be loaded. Check your connection and try again.')); }, { once: true });
    });
    if (!existing) document.head.appendChild(script);
    return script._loadPromise;
  }
  function getTesseractWorker() {
    if (tesseractWorkerPromise) return tesseractWorkerPromise;
    tesseractWorkerPromise = loadScriptOnce(TESSERACT_URL, function () { return !!window.Tesseract; }).then(function () {
      var creation = window.Tesseract.createWorker('eng', 1, {
        logger: function (progress) {
          var card = document.querySelector('.verification-capture[data-key="gov_id"]');
          if (!card) return;
          var percent = Math.round((progress.progress || 0) * 100), status = String(progress.status || 'Preparing OCR').replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
          idMessage(card, progress.status === 'recognizing text' ? 'Reading ID text' : 'Preparing local OCR', status + (percent ? ' — ' + percent + '%' : '') + '. First use may take several seconds.', 'info');
        }
      });
      return new Promise(function (resolve, reject) {
        var finished = false, timer = setTimeout(function () {
          finished = true;
          reject(new Error('Local OCR took too long to load. Check your internet connection, then reopen the camera.'));
        }, 60000);
        creation.then(function (worker) {
          if (finished) { worker.terminate(); return; }
          finished = true; clearTimeout(timer); resolve(worker);
        }).catch(function (reason) {
          if (finished) return;
          finished = true; clearTimeout(timer); reject(reason);
        });
      });
    }).catch(function (reason) { tesseractWorkerPromise = null; throw reason; });
    return tesseractWorkerPromise;
  }
  function ensureIdPanel(card) {
    if (!isGovernmentId(card) || card.querySelector('.id-verification-panel')) return;
    var panel = document.createElement('div');
    panel.className = 'id-verification-panel mt-4 rounded-xl border border-slate-200 bg-white p-3';
    panel.innerHTML = '<label class="block text-sm font-semibold text-slate-800">Select ID Type <span class="text-red-500">*</span></label><select class="id-type-select mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"><option value="">Select a valid ID type</option><option value="philhealth">PhilHealth ID</option><option value="national_id">Philippine National ID</option><option value="drivers_license">Philippine Driver’s License</option><option value="passport">Philippine Passport</option><option value="umid">UMID</option><option value="postal_id">Postal ID</option></select><div class="id-scan-message mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3"><strong class="id-scan-title block text-sm text-blue-900">Select ID Type</strong><p class="id-scan-detail mt-1 text-xs leading-5 text-blue-800">Use an original government-issued photo ID that belongs to the applicant and is unexpired when applicable.</p></div>';
    card.querySelector('.camera-stream').insertAdjacentElement('beforebegin', panel);
    var video = card.querySelector('.camera-stream'), frame = document.createElement('div');
    frame.className = 'id-camera-frame relative mt-4 hidden overflow-hidden rounded-lg bg-black';
    video.parentNode.insertBefore(frame, video);
    frame.appendChild(video);
    video.classList.remove('mt-4', 'rounded-lg');
    video.classList.add('h-full', 'w-full');
    frame.insertAdjacentHTML('beforeend', '<div class="pointer-events-none absolute inset-[8%] rounded-xl border-2 border-dashed border-white/90 shadow-[0_0_0_999px_rgba(0,0,0,.22)]"><span class="absolute -top-7 left-0 rounded bg-black/70 px-2 py-1 text-[11px] font-semibold text-white">Complete ID inside guide · portrait or landscape</span></div><div class="id-countdown-overlay pointer-events-none absolute inset-0 hidden items-center justify-center bg-slate-950/55 text-center text-white"><div><strong class="id-countdown-number block text-7xl font-black leading-none">3</strong><span class="mt-3 block px-5 text-sm font-semibold">ID verified. Hold it steady for automatic capture.</span></div></div>');
    var extractedPanel = document.createElement('section');
    extractedPanel.className = 'id-extracted-panel mt-4 hidden rounded-xl border border-rescue-200 bg-rescue-50/70 p-4';
    extractedPanel.innerHTML = '<div class="flex items-start gap-3"><span class="rounded-lg bg-white p-2 text-rescue-700 shadow-sm"><i data-lucide="scan-text" class="h-5 w-5"></i></span><div><h3 class="text-sm font-semibold text-slate-900">Extracted ID Information</h3><p class="mt-1 text-xs leading-5 text-slate-600">Text recognized locally from the verified camera frame.</p></div></div><dl class="id-extracted-fields mt-4 grid gap-3 sm:grid-cols-2"></dl><details class="mt-4 rounded-lg border border-rescue-200 bg-white"><summary class="cursor-pointer px-3 py-2 text-xs font-semibold text-rescue-800">Show OCR reading for verification</summary><div class="border-t border-rescue-100 px-3 py-2"><p class="mb-2 text-[11px] leading-5 text-slate-500">This shows the text seen by the scanner and helps explain fields it could not read. It is not saved with the application.</p><pre class="id-recognized-text max-h-40 overflow-auto whitespace-pre-wrap text-[11px] leading-5 text-slate-600"></pre></div></details><p class="mt-3 text-[11px] leading-5 text-slate-500">Preliminary OCR only. Extracted text does not certify that the ID is genuine and remains subject to staff review.</p>';
    card.querySelector('.capture-preview-wrap').insertAdjacentElement('afterend', extractedPanel);
    var scanButton = document.createElement('button');
    scanButton.type = 'button';
    scanButton.className = 'scan-id hidden rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50';
    scanButton.textContent = 'Scan ID';
    card.querySelector('.capture-photo').insertAdjacentElement('beforebegin', scanButton);
    panel.querySelector('.id-type-select').addEventListener('change', function () {
      resetIdVerification(card);
      if (card._stream) {
        scanButton.classList.remove('hidden');
        scanButton.disabled = !this.value;
        note(card, this.value ? 'Place the complete ID inside the guide, then tap Scan ID.' : 'Select a valid ID type before scanning.', this.value ? 'text-blue-700' : 'text-amber-700');
      }
    });
    scanButton.addEventListener('click', function () { scanGovernmentId(card); });
  }
  function idMessage(card, title, detail, tone) {
    var box = card.querySelector('.id-scan-message');
    if (!box) return;
    var colors = tone === 'success'
      ? ['border-emerald-200', 'bg-emerald-50', 'text-emerald-900', 'text-emerald-800']
      : tone === 'warning'
        ? ['border-amber-200', 'bg-amber-50', 'text-amber-900', 'text-amber-800']
        : ['border-blue-200', 'bg-blue-50', 'text-blue-900', 'text-blue-800'];
    box.className = 'id-scan-message mt-3 rounded-lg border p-3 ' + colors[0] + ' ' + colors[1];
    box.querySelector('.id-scan-title').className = 'id-scan-title block text-sm ' + colors[2];
    box.querySelector('.id-scan-detail').className = 'id-scan-detail mt-1 text-xs leading-5 ' + colors[3];
    box.querySelector('.id-scan-title').textContent = title;
    box.querySelector('.id-scan-detail').textContent = detail;
  }
  function cleanRecognizedLines(text) {
    var seen = {};
    return String(text || '').split(/\r?\n/).map(function (line) {
      return line.replace(/[^A-Za-z0-9<>,.\/:'()\-\s]/g, ' ').replace(/\s+/g, ' ').trim();
    }).filter(function (line) {
      if (line.length < 2 || seen[line]) return false;
      seen[line] = true;
      return true;
    }).slice(0, 80);
  }
  function labeledIdValue(lines, labels, pattern) {
    for (var index = 0; index < lines.length; index += 1) {
      if (!labels.test(lines[index])) continue;
      var sameLine = lines[index].match(pattern);
      if (sameLine) return sameLine[0];
      var nextLine = lines[index + 1] && lines[index + 1].match(pattern);
      if (nextLine) return nextLine[0];
    }
    return null;
  }
  function normalizeOcrDigits(value) {
    return String(value || '').toUpperCase()
      .replace(/[OQ]/g, '0').replace(/[IL|]/g, '1').replace(/Z/g, '2')
      .replace(/S/g, '5').replace(/G/g, '6').replace(/B/g, '8')
      .replace(/[^0-9]/g, '');
  }
  function philHealthNumber(lines, applicantName) {
    var nameParts = normalizeOcr(applicantName).split(/[^A-Z]+/).filter(function (part) { return part.length >= 3; });
    var nameLines = [];
    lines.forEach(function (line, index) {
      var normalizedLine = normalizeOcr(line);
      if (nameParts.some(function (part) { return normalizedLine.indexOf(part) !== -1; })) nameLines.push(index);
    });
    var candidates = [];
    lines.forEach(function (line, lineIndex) {
      var chunks = line.match(/[0-9OQIL|ZSBG][0-9OQIL|ZSBG\s.\-]{9,22}[0-9OQIL|ZSBG]/g) || [];
      chunks.forEach(function (chunk) {
        var digits = normalizeOcrDigits(chunk), actualDigits = (chunk.match(/[0-9]/g) || []).length;
        if (digits.length !== 12 || actualDigits < 11) return;
        var score = actualDigits * 2;
        if (/\b(?:PIN|PHILHEALTH\s*(?:ID|NUMBER|NO)|IDENTIFICATION\s*(?:NUMBER|NO)|ID\s*(?:NUMBER|NO))\b/i.test(line)) score += 20;
        if (nameLines.some(function (nameLine) { return lineIndex >= nameLine - 2 && lineIndex <= nameLine; })) score += 12;
        if (/^\s*\d{2}[-\s]?\d{9}[-\s]?\d\s*$/.test(chunk)) score += 10;
        candidates.push({ digits: digits, score: score });
      });
    });
    candidates.sort(function (first, second) { return second.score - first.score; });
    if (!candidates.length) return null;
    var best = candidates[0].digits;
    return best.slice(0, 2) + '-' + best.slice(2, 11) + '-' + best.slice(11);
  }
  function extractedAddress(lines, applicantName) {
    for (var index = 0; index < lines.length; index += 1) {
      if (!/\b(?:ADDRESS|TIRAHAN)\b/i.test(lines[index])) continue;
      var address = lines[index].replace(/^.*?\b(?:ADDRESS|TIRAHAN)\b\s*[:\-]?\s*/i, '').trim();
      for (var offset = 1; offset <= 2 && lines[index + offset]; offset += 1) {
        if (/\b(?:BIRTH|BIRTHDATE|DATE OF BIRTH|DOB|SEX|GENDER|EXPIR|VALID|SIGNATURE|PHILHEALTH|MEMBER)\b/i.test(lines[index + offset])) break;
        address += (address ? ', ' : '') + lines[index + offset];
      }
      if (address.length >= 5) return address;
    }
    var nameParts = normalizeOcr(applicantName).split(/[^A-Z]+/).filter(function (part) { return part.length >= 3; });
    for (var fallbackIndex = 0; fallbackIndex < lines.length; fallbackIndex += 1) {
      var candidate = lines[fallbackIndex], normalizedCandidate = normalizeOcr(candidate);
      if (nameParts.some(function (part) { return normalizedCandidate.indexOf(part) !== -1; })) continue;
      if (/PHILHEALTH|MEMBER|IDENTIFICATION|SIGNATURE|BIRTH|SEX|VALID|EXPIR|REPUBLIC OF (?:THE )?PHILIPPINES/i.test(candidate)) continue;
      var hasAddressCue = /\b(?:BRGY|BARANGAY|ST|STREET|AVE|AVENUE|ROAD|RD|CITY|MANILA|QUEZON|DISTRICT|PROVINCE|PHILIPPINES)\b/i.test(candidate);
      var startsWithHouseNumber = /^\s*\d{1,5}[A-Z]?\s+[A-Z]/i.test(candidate);
      if ((hasAddressCue || startsWithHouseNumber) && candidate.replace(/[^A-Za-z]/g, '').length >= 5) return candidate;
    }
    return null;
  }
  function extractIdInformation(text, detectedType, confidence, nameMatchCount) {
    var lines = cleanRecognizedLines(text), joined = lines.join(' ').toUpperCase();
    var fullNameInput = form.querySelector('[name="full_name"]'), applicantName = fullNameInput ? fullNameInput.value.trim() : '';
    var numberPatterns = {
      philhealth: /\b\d{2}[-\s]?\d{9}[-\s]?\d\b/,
      national_id: /\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b/,
      drivers_license: /\b[A-Z]\d{2}[-\s]?\d{2}[-\s]?\d{6}\b/,
      passport: /\b[A-Z]\d{7}\b/,
      umid: /\b\d{4}[-\s]?\d{7}[-\s]?\d\b/,
      postal_id: /\b(?:PRN\s*)?[A-Z0-9]{4}[-\s][A-Z0-9-]{5,12}\b/
    };
    var numberMatch = joined.match(numberPatterns[detectedType] || /$a/);
    var documentNumber = detectedType === 'philhealth' ? philHealthNumber(lines, applicantName) : (numberMatch ? numberMatch[0].replace(/\s+/g, '-') : null);
    var datePattern = /\b(?:\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}|\d{4}[\/\-.]\d{1,2}[\/\-.]\d{1,2}|(?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|SEPT|OCT|NOV|DEC)[A-Z]*\s+\d{1,2},?\s+\d{4})\b/i;
    var sexMatch = joined.match(/\b(?:SEX|GENDER)\s*[:\-]?\s*(MALE|FEMALE|M|F)\b/);
    var birthDate = labeledIdValue(lines, /BIRTH|BIRTHDATE|DATE OF BIRTH|DOB/i, datePattern);
    if (!birthDate && detectedType === 'philhealth') {
      var possibleDates = lines.map(function (line) { var match = line.match(datePattern); return match ? match[0] : null; }).filter(Boolean);
      if (possibleDates.length === 1) birthDate = possibleDates[0];
    }
    return {
      lines: lines,
      fields: [
        ['Detected ID type', idTypeLabel(detectedType)],
        ['Applicant name', applicantName + (nameMatchCount === 2 ? ' — first and last name matched' : ' — partial OCR match')],
        ['Document number', documentNumber || 'Not confidently read'],
        ['OCR text status', 'Readable for preliminary screening'],
        ['Birth date', birthDate || 'Not confidently read'],
        ['Address', extractedAddress(lines, applicantName) || 'Not confidently read'],
        ['Expiry date', labeledIdValue(lines, /EXPIR|VALID UNTIL|VALIDITY/i, datePattern) || 'Not confidently read'],
        ['Sex', sexMatch ? sexMatch[1] : 'Not confidently read']
      ]
    };
  }
  function showIdExtraction(card, extraction) {
    var panel = card.querySelector('.id-extracted-panel'), fields = panel && panel.querySelector('.id-extracted-fields');
    if (!panel || !fields) return;
    fields.innerHTML = '';
    extraction.fields.forEach(function (field) {
      var item = document.createElement('div');
      item.className = 'rounded-lg border border-rescue-100 bg-white px-3 py-2.5';
      var term = document.createElement('dt'), value = document.createElement('dd');
      term.className = 'text-[11px] font-medium text-slate-500'; term.textContent = field[0];
      value.className = 'mt-1 break-words text-sm font-semibold ' + (field[1] === 'Not confidently read' ? 'text-amber-700' : 'text-slate-900'); value.textContent = field[1];
      item.appendChild(term); item.appendChild(value); fields.appendChild(item);
    });
    panel.querySelector('.id-recognized-text').textContent = extraction.lines.length ? extraction.lines.join('\n') : 'No additional text was confidently recognized.';
    panel.classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function resetIdVerification(card) {
    if (!isGovernmentId(card)) return;
    if (card._idCountdownTimer) window.clearTimeout(card._idCountdownTimer);
    card._idCountdownTimer = null;
    card._idAutoCapturePending = false;
    card._idVerifiedFrame = null;
    var countdownOverlay = card.querySelector('.id-countdown-overlay');
    if (countdownOverlay) {
      countdownOverlay.classList.add('hidden');
      countdownOverlay.classList.remove('flex');
    }
    card._idVerified = false;
    card._idVerifiedAt = 0;
    card._idVerification = null;
    card._idExtraction = null;
    var extractedPanel = card.querySelector('.id-extracted-panel');
    if (extractedPanel) extractedPanel.classList.add('hidden');
    card.querySelector('.capture-photo').disabled = true;
    card.querySelector('.capture-photo').textContent = 'Automatic capture after scan';
    var scan = card.querySelector('.scan-id'), select = card.querySelector('.id-type-select');
    if (scan) scan.disabled = !card._stream || !select.value;
    var passportSelected = select && select.value === 'passport';
    var readyMessage = passportSelected
      ? 'Open the passport biodata page. Keep the word PASSPORT, photo, name, and both P<PHL lines at the bottom inside the guide.'
      : 'Place the complete ID inside the guide with all text facing the camera. Portrait or landscape phone orientation is supported.';
    idMessage(card, select && select.value ? 'Ready to scan' : 'Select your ID type', select && select.value ? readyMessage : 'The ID type must be selected before opening or scanning the camera.', 'info');
  }
  function frameCanvas(video, maximumSide) {
    var limit = maximumSide || 1280;
    var canvas = document.createElement('canvas'), longest = Math.max(video.videoWidth, video.videoHeight), scale = Math.min(1, limit / longest);
    canvas.width = Math.round(video.videoWidth * scale);
    canvas.height = Math.round(video.videoHeight * scale);
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    return canvas;
  }
  function analyzeIdFrame(canvas) {
    // Native Canvas analysis avoids downloading and compiling the 10 MB
    // OpenCV WebAssembly bundle on mobile Safari. OCR still confirms that the
    // guide contains the selected Philippine ID type and applicant name.
    var sample = document.createElement('canvas'), width = 320, height = Math.max(180, Math.round(width * canvas.height / canvas.width));
    sample.width = width; sample.height = height;
    var sampleContext = sample.getContext('2d', { willReadFrequently: true });
    sampleContext.drawImage(canvas, 0, 0, width, height);
    var pixels = sampleContext.getImageData(0, 0, width, height).data, gray = new Float32Array(width * height), sum = 0, sumSquares = 0, glare = 0;
    for (var pixel = 0, offset = 0; pixel < gray.length; pixel += 1, offset += 4) {
      var value = pixels[offset] * 0.299 + pixels[offset + 1] * 0.587 + pixels[offset + 2] * 0.114;
      gray[pixel] = value; sum += value; sumSquares += value * value;
      if (value >= 245) glare += 1;
    }
    var brightness = sum / gray.length, variance = Math.max(0, sumSquares / gray.length - brightness * brightness), edgeTotal = 0, edgeSamples = 0;
    for (var y = 1; y < height - 1; y += 2) {
      for (var x = 1; x < width - 1; x += 2) {
        var index = y * width + x;
        edgeTotal += Math.abs(gray[index + 1] - gray[index - 1]) + Math.abs(gray[index + width] - gray[index - width]);
        edgeSamples += 2;
      }
    }
    return {
      document: { x: Math.round(canvas.width * 0.08), y: Math.round(canvas.height * 0.08), width: Math.round(canvas.width * 0.84), height: Math.round(canvas.height * 0.84), areaRatio: 0.7056 },
      brightness: brightness,
      sharpness: edgeTotal / Math.max(1, edgeSamples),
      contrast: Math.sqrt(variance),
      glareRatio: glare / gray.length
    };
  }
  function analyzeCaptureQuality(card) {
    var video = card.querySelector('.camera-stream');
    var button = card.querySelector('.capture-photo');
    if (!card._stream || video.readyState < 2 || !video.videoWidth) {
      card._sceneReady = false;
      button.disabled = true;
      return false;
    }
    var analysis = analyzeIdFrame(frameCanvas(video, 320));
    card._sceneAnalysis = analysis;
    var subject = isAddressProof(card) ? 'document' : 'room';
    if (analysis.brightness < 45) {
      card._sceneReady = false;
      card._sceneMessage = 'Too dark — turn on a light or move to a brighter area before capturing.';
      note(card, card._sceneMessage, 'text-red-600');
    } else if (analysis.brightness > 245 || analysis.glareRatio > 0.50) {
      card._sceneReady = false;
      card._sceneMessage = 'Too bright or overexposed — adjust the camera away from direct light.';
      note(card, card._sceneMessage, 'text-red-600');
    } else if (analysis.contrast < 8 && analysis.sharpness < 2.5) {
      card._sceneReady = false;
      card._sceneMessage = 'No clear ' + subject + ' detail detected — uncover the camera and show the actual ' + subject + '.';
      note(card, card._sceneMessage, 'text-red-600');
    } else {
      card._sceneReady = true;
      card._sceneMessage = 'Lighting and ' + subject + ' view look clear. You can capture now.';
      note(card, card._sceneMessage, 'text-emerald-700');
    }
    button.disabled = !card._sceneReady;
    return card._sceneReady;
  }
  function startCaptureQualityMonitor(card) {
    stopCaptureQualityMonitor(card);
    analyzeCaptureQuality(card);
    card._sceneTimer = window.setInterval(function () { analyzeCaptureQuality(card); }, 700);
  }
  function cropCanvas(source, rect) {
    var padding = Math.round(Math.min(rect.width, rect.height) * 0.025), x = Math.max(0, rect.x - padding), y = Math.max(0, rect.y - padding), width = Math.min(source.width - x, rect.width + padding * 2), height = Math.min(source.height - y, rect.height + padding * 2);
    var target = document.createElement('canvas'), scale = Math.max(1, Math.min(2.5, 2200 / Math.max(width, height)));
    target.width = Math.round(width * scale); target.height = Math.round(height * scale);
    target.getContext('2d').drawImage(source, x, y, width, height, 0, 0, target.width, target.height);
    return target;
  }
  function highContrastCanvas(source) {
    var target = document.createElement('canvas'); target.width = source.width; target.height = source.height;
    var context = target.getContext('2d', { willReadFrequently: true }); context.drawImage(source, 0, 0);
    var image = context.getImageData(0, 0, target.width, target.height), pixels = image.data;
    for (var offset = 0; offset < pixels.length; offset += 4) {
      var gray = pixels[offset] * 0.299 + pixels[offset + 1] * 0.587 + pixels[offset + 2] * 0.114;
      var value = gray < 175 ? 0 : 255;
      pixels[offset] = value; pixels[offset + 1] = value; pixels[offset + 2] = value;
    }
    context.putImageData(image, 0, 0); return target;
  }
  function rotateCanvas(source, clockwise) {
    var target = document.createElement('canvas');
    target.width = source.height; target.height = source.width;
    var context = target.getContext('2d');
    if (clockwise) {
      context.translate(target.width, 0);
      context.rotate(Math.PI / 2);
    } else {
      context.translate(0, target.height);
      context.rotate(-Math.PI / 2);
    }
    context.drawImage(source, 0, 0);
    return target;
  }
  function passportMrzCanvas(source) {
    // Passport identity is strongest in the two machine-readable lines at the
    // bottom of the biodata page. Enlarge that area for a second OCR pass on
    // mobile cameras without weakening the applicant-name requirement.
    var x = Math.round(source.width * 0.06), y = Math.round(source.height * 0.58);
    var width = Math.round(source.width * 0.88), height = Math.round(source.height * 0.34);
    var target = document.createElement('canvas'), scale = Math.max(1.5, Math.min(3, 1600 / Math.max(width, height)));
    target.width = Math.round(width * scale); target.height = Math.round(height * scale);
    target.getContext('2d').drawImage(source, x, y, width, height, 0, 0, target.width, target.height);
    return target;
  }
  function normalizeOcr(value) { return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().replace(/[^A-Z0-9<]+/g, ' ').replace(/\s+/g, ' ').trim(); }
  function editDistance(first, second) {
    var previous = Array.from({ length: second.length + 1 }, function (_, index) { return index; });
    for (var row = 1; row <= first.length; row += 1) {
      var current = [row];
      for (var column = 1; column <= second.length; column += 1) current[column] = Math.min(current[column - 1] + 1, previous[column] + 1, previous[column - 1] + (first[row - 1] === second[column - 1] ? 0 : 1));
      previous = current;
    }
    return previous[second.length];
  }
  function ocrWordMatches(target, words) {
    var allowedErrors = target.length >= 8 ? 2 : target.length >= 5 ? 1 : 0;
    return words.some(function (word) { return Math.abs(word.length - target.length) <= allowedErrors && editDistance(target, word) <= allowedErrors; });
  }
  function detectGovernmentIdType(text) {
    var normalized = normalizeOcr(text), compactLetters = normalized.replace(/[^A-Z]/g, '');
    var scores = { philhealth: 0, national_id: 0, drivers_license: 0, passport: 0, umid: 0, postal_id: 0 };
    if (compactLetters.indexOf('PHILHEAL') !== -1) scores.philhealth += 8;
    if (/PHILIPPINE HEALTH INSURANCE|HEALTH INSURANCE CORPORATION/.test(normalized)) scores.philhealth += 6;
    if (/MEMBER DATA RECORD|MEMBER IDENTIFICATION|\bPHILHEALTH PIN\b/.test(normalized)) scores.philhealth += 5;
    if (/\b\d{2}\s+\d{9}\s+\d\b/.test(normalized)) scores.philhealth += 4;
    if (/\bMEMBER\b|\bPIN\b/.test(normalized)) scores.philhealth += 1;

    if (/PHILIPPINE IDENTIFICATION|PAMBANSANG PAGKAKAKILANLAN|PHILSYS/.test(normalized)) scores.national_id += 8;
    if (/PHILIPPINE IDENTIFICATION SYSTEM|PHILSYS CARD NUMBER|\bPCN\b|\bPSN\b/.test(normalized)) scores.national_id += 5;
    if (/\b\d{4}\s+\d{4}\s+\d{4}\s+\d{4}\b/.test(normalized)) scores.national_id += 4;

    if (/DRIVER.?S LICENSE/.test(normalized)) scores.drivers_license += 8;
    if (/LAND TRANSPORTATION OFFICE|DEPARTMENT OF TRANSPORTATION/.test(normalized)) scores.drivers_license += 6;
    if (/\bLTO\b|DL CODES|RESTRICTIONS/.test(normalized)) scores.drivers_license += 4;
    if (/\b[A-Z]\d{2}\s+\d{2}\s+\d{6}\b/.test(normalized)) scores.drivers_license += 4;

    if (/P\s*<\s*PH[L1I]/.test(normalized)) scores.passport += 10;
    if (/\bPASSPORT\b/.test(normalized) || compactLetters.indexOf('PASSPOR') !== -1) scores.passport += 7;
    if (/PILIPINAS|REPUBLIC OF THE PHILIPPINES|\bPHL\b/.test(normalized)) scores.passport += 2;
    if (/SURNAME|GIVEN NAMES|NATIONALITY/.test(normalized)) scores.passport += 1;

    if (/UNIFIED MULTI[ -]?PURPOSE ID/.test(normalized)) scores.umid += 10;
    if (/\bUMID\b/.test(normalized)) scores.umid += 8;
    if (/COMMON REFERENCE NUMBER|\bCRN\b/.test(normalized)) scores.umid += 5;
    if (/SOCIAL SECURITY SYSTEM|GOVERNMENT SERVICE INSURANCE SYSTEM/.test(normalized)) scores.umid += 2;

    if (/POSTAL IDENTITY CARD|POSTAL ID CARD|\bPOSTAL ID\b/.test(normalized)) scores.postal_id += 10;
    if (/PHILIPPINE POSTAL CORPORATION|\bPHLPOST\b/.test(normalized)) scores.postal_id += 7;
    if (/POSTAL REFERENCE NUMBER|\bPRN\b/.test(normalized)) scores.postal_id += 3;

    var ranked = Object.keys(scores).sort(function (first, second) { return scores[second] - scores[first]; });
    if (scores[ranked[0]] < 4 || (scores[ranked[1]] > 0 && scores[ranked[0]] - scores[ranked[1]] < 2)) return null;
    return ranked[0];
  }
  function applicantNameMatchCount(text) {
    var input = form.querySelector('[name="full_name"]'), parts = normalizeOcr(input ? input.value : '').split(/[^A-Z]+/).filter(function (part) { return part.length >= 2; });
    if (parts.length < 2) return 0;
    // Passport MRZ names use "<" separators (SURNAME<<GIVEN<NAMES), so split
    // on every non-letter instead of spaces only. The old behavior discarded
    // an otherwise readable MRZ name as one invalid token.
    var words = normalizeOcr(text).split(/[^A-Z]+/).filter(function (part) { return part.length >= 2; });
    return [parts[0], parts[parts.length - 1]].filter(function (part) { return ocrWordMatches(part, words); }).length;
  }
  function idTypeLabel(type) { return { philhealth: 'PhilHealth ID', national_id: 'Philippine National ID', drivers_license: 'Philippine Driver’s License', passport: 'Philippine Passport', umid: 'UMID', postal_id: 'Postal ID' }[type] || 'Valid Government-Issued ID'; }
  function cloneCanvas(source) {
    var copy = document.createElement('canvas');
    copy.width = source.width;
    copy.height = source.height;
    copy.getContext('2d').drawImage(source, 0, 0);
    return copy;
  }
  function startIdAutoCaptureCountdown(card, verifiedFrame) {
    var overlay = card.querySelector('.id-countdown-overlay'), number = card.querySelector('.id-countdown-number');
    var scanButton = card.querySelector('.scan-id'), captureButton = card.querySelector('.capture-photo');
    card._idVerifiedFrame = cloneCanvas(verifiedFrame);
    card._idAutoCapturePending = true;
    scanButton.disabled = true;
    captureButton.disabled = true;
    captureButton.textContent = 'Automatic capture in 3…';
    if (overlay) {
      overlay.classList.remove('hidden');
      overlay.classList.add('flex');
    }
    var seconds = 3;
    function tick() {
      if (!card._idAutoCapturePending || !card._stream || !card._idVerifiedFrame) return;
      if (number) number.textContent = String(seconds);
      captureButton.textContent = seconds > 0 ? 'Automatic capture in ' + seconds + '…' : 'Capturing verified ID…';
      idMessage(card, seconds > 0 ? 'Preliminary screening passed' : 'Capturing verified ID', seconds > 0 ? 'Keep the same ID steady. It will be captured automatically in ' + seconds + ' second' + (seconds === 1 ? '' : 's') + '.' : 'Using the exact camera frame that passed the ID type and applicant-name screening.', 'success');
      if (seconds === 0) {
        card._idCountdownTimer = null;
        card._idAutoCapturePending = false;
        if (overlay) {
          overlay.classList.add('hidden');
          overlay.classList.remove('flex');
        }
        capture(card, { sourceCanvas: card._idVerifiedFrame, automaticIdCapture: true });
        return;
      }
      seconds -= 1;
      card._idCountdownTimer = window.setTimeout(tick, 1000);
    }
    tick();
  }
  async function scanGovernmentId(card) {
    clearError();
    var select = card.querySelector('.id-type-select'), scanButton = card.querySelector('.scan-id'), captureButton = card.querySelector('.capture-photo'), video = card.querySelector('.camera-stream');
    if (!select.value) return error('Select a valid ID type before scanning.');
    if (!card._stream || !video.videoWidth || !video.videoHeight) return error('Open the rear camera and wait for the ID preview before scanning.');
    var fullName = form.querySelector('[name="full_name"]');
    if (!fullName || normalizeOcr(fullName.value).split(' ').filter(Boolean).length < 2) return error('Enter your complete Full Name in Personal Information before scanning the ID.');
    resetIdVerification(card);
    scanButton.disabled = true;
    captureButton.disabled = true;
    card.querySelector('.capture-state').textContent = 'Scanning ID';
    idMessage(card, 'Checking document', 'Checking the ID framing, lighting, and clarity on this device.', 'info');
    try {
      // Preserve enough native camera detail for small printed fields such as
      // PhilHealth numbers, birth dates, and addresses. The previous 1280 px
      // cap was sufficient for ID-type screening but could blur field text.
      var source = frameCanvas(video, 2560), analysis = analyzeIdFrame(source), scanningPassport = select.value === 'passport';
      var minimumBrightness = scanningPassport ? 45 : 55, maximumBrightness = scanningPassport ? 235 : 225;
      var maximumGlare = scanningPassport ? 0.35 : 0.25, minimumSharpness = scanningPassport ? 4.5 : 7, minimumContrast = scanningPassport ? 12 : 18;
      if (analysis.brightness < minimumBrightness) throw new Error('The ID is too dark. Move to a brighter area and scan again.');
      if (analysis.brightness > maximumBrightness || analysis.glareRatio > maximumGlare) throw new Error('There is too much glare on the ID. Tilt it slightly away from the light and scan again.');
      if (analysis.sharpness < minimumSharpness || analysis.contrast < minimumContrast) throw new Error(scanningPassport ? 'The passport text is not clear enough. Show the complete biodata page, including both P<PHL lines at the bottom, and hold the phone steady.' : 'The ID text looks blurry or too plain. Keep all four corners inside the guide, hold the phone steady, and move closer.');
      idMessage(card, 'Document detected', 'Reading the ID type and applicant name locally. The image is not sent to the OCR library provider.', 'info');
      var worker = await getTesseractWorker(), ocrCanvas = cropCanvas(source, analysis.document), result = await worker.recognize(ocrCanvas), text = result && result.data ? result.data.text : '', confidence = result && result.data ? Number(result.data.confidence || 0) : 0;
      var minimumOcrConfidence = scanningPassport ? 3 : 8;
      var detectedType = detectGovernmentIdType(text), nameMatchCount = applicantNameMatchCount(text);
      if (!detectedType || nameMatchCount < 1 || confidence < minimumOcrConfidence) {
        idMessage(card, 'Improving ID reading', 'Running a second high-contrast OCR pass for laminated or low-contrast IDs.', 'info');
        var enhancedResult = await worker.recognize(highContrastCanvas(ocrCanvas)), enhancedText = enhancedResult && enhancedResult.data ? enhancedResult.data.text : '', enhancedConfidence = enhancedResult && enhancedResult.data ? Number(enhancedResult.data.confidence || 0) : 0;
        text += '\n' + enhancedText; confidence = Math.max(confidence, enhancedConfidence);
        if (scanningPassport) {
          idMessage(card, 'Reading passport MRZ', 'Checking the two P<PHL machine-readable lines at the bottom of the biodata page.', 'info');
          var mrzResult = await worker.recognize(highContrastCanvas(passportMrzCanvas(source))), mrzText = mrzResult && mrzResult.data ? mrzResult.data.text : '', mrzConfidence = mrzResult && mrzResult.data ? Number(mrzResult.data.confidence || 0) : 0;
          text += '\n' + mrzText; confidence = Math.max(confidence, mrzConfidence);
        }
        detectedType = detectGovernmentIdType(text); nameMatchCount = applicantNameMatchCount(text);
      }
      if (!detectedType || nameMatchCount < 1 || confidence < minimumOcrConfidence) {
        idMessage(card, 'Checking phone orientation', 'The first reading looked sideways. Trying landscape orientation automatically.', 'info');
        var rotations = [rotateCanvas(ocrCanvas, true), rotateCanvas(ocrCanvas, false)];
        for (var rotationIndex = 0; rotationIndex < rotations.length; rotationIndex += 1) {
          var rotatedResult = await worker.recognize(rotations[rotationIndex]);
          var rotatedText = rotatedResult && rotatedResult.data ? rotatedResult.data.text : '';
          var rotatedConfidence = rotatedResult && rotatedResult.data ? Number(rotatedResult.data.confidence || 0) : 0;
          text += '\n' + rotatedText; confidence = Math.max(confidence, rotatedConfidence);
          detectedType = detectGovernmentIdType(text); nameMatchCount = applicantNameMatchCount(text);
          if (detectedType && nameMatchCount >= 1 && confidence >= minimumOcrConfidence) break;
        }
      }
      if (!detectedType) throw new Error(scanningPassport ? 'Passport not detected. Open the biodata page and keep the word PASSPORT, your name, photo, and both P<PHL lines fully visible.' : 'The scanner could not confirm the ID type. Move closer and keep the ID title, logo, and number fully visible.');
      if (detectedType !== select.value) throw new Error('The camera detected ' + idTypeLabel(detectedType) + ', but you selected ' + idTypeLabel(select.value) + '. Select the correct type and scan again.');
      if (nameMatchCount < 1) throw new Error(scanningPassport ? 'The passport was detected, but the applicant name was not readable. Move closer and keep SURNAME and GIVEN NAMES clear.' : 'The scanner could not read any matching name from the ID. Move closer and keep the name area clear.');
      var readableTokens = normalizeOcr(text).split(/[^A-Z0-9]+/).filter(function (part) { return part.length >= 2; }).length;
      if (readableTokens < 4 || confidence < minimumOcrConfidence) throw new Error(scanningPassport ? 'The passport markers were found, but too little text was readable. Keep the biodata page flat and the two MRZ lines fully inside the guide.' : 'Not enough readable ID text was found. Move closer, avoid glare, and scan again.');
      card._idVerified = true;
      card._idVerifiedAt = Date.now();
      card._idVerification = { verified: true, selected_type: select.value, detected_type: detectedType, type_basis: 'ocr_marker', name_match: true, name_match_level: nameMatchCount === 2 ? 'first_and_last' : 'partial', confidence: Math.round(confidence), verified_at: new Date(card._idVerifiedAt).toISOString(), provider: 'tesseract_js', version: '6.0.1', document_detected: true };
      card._idExtraction = extractIdInformation(text, detectedType, confidence, nameMatchCount);
      showIdExtraction(card, card._idExtraction);
      captureButton.disabled = true;
      captureButton.textContent = 'Automatic capture in 3…';
      card.querySelector('.capture-state').textContent = 'Screening passed';
      card.querySelector('.capture-state').className = 'capture-state rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700';
      idMessage(card, 'Preliminary screening passed', idTypeLabel(detectedType) + ' markers and the applicant name matched. Automatic capture is starting; final authenticity remains subject to staff review.', 'success');
      note(card, 'ID verified. Hold it steady during the 3-second automatic capture.', 'text-emerald-700');
      startIdAutoCaptureCountdown(card, source);
    } catch (reason) {
      resetIdVerification(card);
      card.querySelector('.capture-state').textContent = 'Scan required';
      card.querySelector('.capture-state').className = 'capture-state rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800';
      idMessage(card, 'ID scan not passed', reason.message || 'The ID could not be read. Reposition it and scan again.', 'warning');
      error(reason.message || 'The ID could not be read. Reposition it and scan again.');
    } finally {
      scanButton.disabled = card._idAutoCapturePending || !card._stream || !select.value;
    }
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
    if (isGovernmentId(card) && !card.querySelector('.id-type-select').value) {
      card.querySelector('.id-type-select').focus();
      return error('Select a valid ID type before opening the camera.');
    }
    if (active && active !== card) stop(active);
    stop(card);
    try {
      if (isSelfie(card)) card._facing = 'user';
      if (isGovernmentId(card)) { card._facing = 'environment'; resetIdVerification(card); }
      if (isAddressProof(card)) card._facing = 'environment';
      if (isHomeScene(card)) card._facing = 'environment';
      var stream = await streamFor(card._facing || 'environment');
      card._stream = stream;
      active = card;
      var video = card.querySelector('.camera-stream');
      setCameraOrientation(video, card._facing);
      video.srcObject = stream;
      video.classList.remove('hidden');
      if (isSelfie(card)) card.querySelector('.selfie-camera-frame').classList.remove('hidden');
      if (isGovernmentId(card)) card.querySelector('.id-camera-frame').classList.remove('hidden');
      await video.play();
      await ready(video);
      card.querySelector('.start-camera').classList.add('hidden');
      if (isSelfie(card)) {
        card.querySelector('.switch-camera').classList.add('hidden');
        await startFaceVerification(card);
      } else if (isGovernmentId(card)) {
        card.querySelector('.capture-photo').disabled = true;
        card.querySelector('.switch-camera').classList.add('hidden');
        card.querySelector('.scan-id').classList.remove('hidden');
        card.querySelector('.scan-id').disabled = false;
        var selectedIdType = card.querySelector('.id-type-select').value;
        idMessage(card, 'Camera ready', selectedIdType === 'passport' ? 'Open the biodata page and show the word PASSPORT, your photo and name, plus both P<PHL lines at the bottom.' : 'Place the complete ' + idTypeLabel(selectedIdType) + ' inside the guide, then tap Scan ID.', 'info');
        note(card, 'Rear camera ready. Scan the ID format and applicant name before capture.', 'text-blue-700');
        // Warm up the local OCR worker while the applicant positions the ID.
        // Scan ID will reuse this same promise instead of starting a new load.
        getTesseractWorker().catch(function (reason) {
          idMessage(card, 'OCR could not start', reason.message || 'Check your connection, then reopen the camera.', 'warning');
        });
      } else if (isAddressProof(card)) {
        card.querySelector('.capture-photo').disabled = true;
        card.querySelector('.switch-camera').classList.add('hidden');
        note(card, 'Checking the proof-of-address document and lighting…', 'text-gray-500');
        startCaptureQualityMonitor(card);
      } else if (isHomeScene(card)) {
        card.querySelector('.capture-photo').disabled = true;
        card.querySelector('.switch-camera').classList.add('hidden');
        note(card, card.dataset.key === 'home_access' ? 'Checking the window or outdoor-access view and lighting…' : 'Checking the indoor living area and lighting…', 'text-gray-500');
        startCaptureQualityMonitor(card);
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
  async function capture(card, options) {
    options = options || {};
    clearError();
    var video = card.querySelector('.camera-stream');
    if (!card._stream || !video.videoWidth || !video.videoHeight) return error('Camera is not ready. Open it again and wait for the preview.');
    if (isSelfie(card) && (!card._livenessVerified || Date.now() - card._livenessVerifiedAt > 30000 || Date.now() - card._faceLastDetectedAt > 1500)) {
      card.querySelector('.capture-photo').disabled = true;
      return error('Complete the live face challenge and keep your face visible before capturing the selfie.');
    }
    if (isGovernmentId(card) && (!card._idVerified || !card._idVerification || Date.now() - card._idVerifiedAt > 60000 || card._idVerification.selected_type !== card.querySelector('.id-type-select').value)) {
      resetIdVerification(card);
      return error('Scan and pass the valid government-issued ID type and applicant-name check before capturing.');
    }
    var homeSceneVerification = null;
    if (isAddressProof(card) && !analyzeCaptureQuality(card)) {
      return error(card._sceneMessage || 'Show a bright, clear view of the proof-of-address document before capturing.');
    }
    if (isHomeScene(card)) {
      if (!analyzeCaptureQuality(card)) return error(card._sceneMessage || 'Show a bright, clear view of the actual area before capturing.');
      var homeAnalysis = card._sceneAnalysis;
      homeSceneVerification = { verified: true, capture_type: homeSceneType(card), brightness: Math.round(homeAnalysis.brightness), contrast: Math.round(homeAnalysis.contrast), sharpness: Math.round(homeAnalysis.sharpness * 10) / 10, verified_at: new Date().toISOString(), provider: 'native_canvas_scene_quality', version: '1.0' };
    }
    var button = card.querySelector('.capture-photo'), canvas = card.querySelector('.capture-canvas');
    button.disabled = true;
    var liveness = isSelfie(card) ? { verified: true, challenge: card._livenessChallenge, verified_at: new Date(card._livenessVerifiedAt).toISOString(), provider: 'mediapipe_face_landmarker', version: '1.2.0', capture_mode: options.automaticSelfieCapture ? 'automatic_challenge_frame' : 'manual' } : null;
    var idVerification = isGovernmentId(card) ? Object.assign({}, card._idVerification) : null;
    var captureSource = options.sourceCanvas || video;
    var sourceWidth = captureSource.width || captureSource.videoWidth, sourceHeight = captureSource.height || captureSource.videoHeight;
    var longest = Math.max(sourceWidth, sourceHeight), scale = Math.min(1, 1280 / longest);
    canvas.width = Math.round(sourceWidth * scale);
    canvas.height = Math.round(sourceHeight * scale);
    var ctx = canvas.getContext('2d'), when = new Date();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    if (card._facing === 'user') { ctx.translate(canvas.width, 0); ctx.scale(-1, 1); }
    ctx.drawImage(captureSource, 0, 0, canvas.width, canvas.height);
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
        if (idVerification) {
          idVerification.capture_mode = options.automaticIdCapture ? 'automatic_verified_frame' : 'manual';
          captureMetadata.id_verification = idVerification;
        }
        if (homeSceneVerification) captureMetadata.home_scene = homeSceneVerification;
        captures[key] = captureMetadata;
        if (isSelfie(card)) card._faceAutoCapturePending = false;
        meta.value = JSON.stringify(captures);
        var preview = card.querySelector('.capture-preview');
        preview.src = canvas.toDataURL('image/jpeg', .82);
        card.querySelector('.capture-preview-wrap').classList.remove('hidden');
        video.classList.add('hidden');
        card.querySelector('.retake-photo').classList.remove('hidden');
        button.classList.add('hidden');
        card.querySelector('.switch-camera').classList.add('hidden');
        if (isGovernmentId(card)) { card.querySelector('.scan-id').classList.add('hidden'); card.querySelector('.id-type-select').disabled = true; }
        card.querySelector('.capture-state').textContent = isSelfie(card) ? 'Live selfie captured' : (isGovernmentId(card) ? 'Screened ID auto-captured' : 'Captured');
        card.querySelector('.capture-state').className = 'capture-state rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700';
        note(card, (isSelfie(card) ? 'Verified live selfie captured at ' : 'Captured at ') + place.location + ' (GPS ±' + Math.round(coords.accuracy) + ' m)', 'text-emerald-700');
        stop(card);
      }, 'image/jpeg', .82);
    } catch (reason) {
      button.disabled = false;
      if (isSelfie(card) && options.automaticSelfieCapture) {
        card._faceAutoCapturePending = false;
        resetFaceVerification(card);
        stop(card);
        card.querySelector('.start-camera').classList.remove('hidden');
      }
      if (isGovernmentId(card) && options.automaticIdCapture) {
        card._idVerifiedFrame = null;
        resetIdVerification(card);
        card.querySelector('.capture-state').textContent = 'Scan required';
        card.querySelector('.capture-state').className = 'capture-state rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800';
        card.querySelector('.scan-id').classList.remove('hidden');
        card.querySelector('.scan-id').disabled = false;
        idMessage(card, 'Automatic capture interrupted', 'Allow location access, keep the ID ready, then scan it again.', 'warning');
      }
      error(reason.message || 'Could not attach GPS information to this photo.');
    }
  }
  function zoom(card) { var source = card.querySelector('.capture-preview').src; if (!source) return; var modal = document.createElement('div'); modal.className = 'fixed inset-0 z-[200] flex items-center justify-center bg-black/80 p-4'; modal.innerHTML = '<button type="button" class="absolute right-4 top-4 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-800">Close</button><img class="max-h-full max-w-full rounded-lg object-contain" alt="Full-size verification capture">'; modal.querySelector('img').src = source; modal.querySelector('button').onclick = function () { modal.remove(); }; modal.onclick = function (event) { if (event.target === modal) modal.remove(); }; document.body.appendChild(modal); }
  document.querySelectorAll('.verification-capture').forEach(function (card) {
    card._facing = isSelfie(card) ? 'user' : 'environment';
    ensureFacePanel(card);
    ensureIdPanel(card);
    if (isSelfie(card)) card.querySelector('.capture-photo').textContent = 'Automatic capture after face check';
    if (isGovernmentId(card)) card.querySelector('.capture-photo').textContent = 'Automatic capture after scan';
    card.querySelector('.start-camera').onclick = function () { open(card); };
    card.querySelector('.switch-camera').onclick = function () {
      if (isSelfie(card) || isGovernmentId(card) || isAddressProof(card) || isHomeScene(card)) return;
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
      if (!isSelfie(card) && !isGovernmentId(card) && !isAddressProof(card) && !isHomeScene(card)) card.querySelector('.switch-camera').classList.remove('hidden');
      if (isGovernmentId(card)) { card.querySelector('.id-type-select').disabled = false; resetIdVerification(card); }
      card.querySelector('.capture-state').textContent = 'Not captured';
      card.querySelector('.capture-state').className = 'capture-state rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600';
      open(card);
    };
  });
  form.addEventListener('submit', function (event) { var cards = document.querySelectorAll('.verification-capture'), valid = Object.keys(captures).length === cards.length && Array.prototype.every.call(cards, function (card) { return card.querySelector('.capture-file').files.length === 1; }); if (!valid) { event.preventDefault(); error('Capture all five live verification photos before submitting.'); cards[0].scrollIntoView({ behavior: 'smooth', block: 'center' }); return; } if (submitting) { event.preventDefault(); return; } submitting = true; meta.value = JSON.stringify(captures); var button = form.querySelector('button[type="submit"]'); button.disabled = true; button.querySelector('span').textContent = 'Submitting application…'; });
  window.addEventListener('beforeunload', function () { document.querySelectorAll('.verification-capture').forEach(stop); if (tesseractWorkerPromise) tesseractWorkerPromise.then(function (worker) { worker.terminate(); }).catch(function () {}); });
});
