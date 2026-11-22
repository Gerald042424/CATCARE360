'use strict';

// Starting values for local validation, not production blocking thresholds.
const rules = {
    windowSize: 4, minVotes: 3, edgeContrast: 24, edgeCoverage: 0.58,
    minPeriodCorrelation: 0.55, minPeriodContrast: 0.20,
    minTextureRatio: 1.4, minTemporalDifference: 2
};
const frames = [];

function luminance(image) {
    const values = new Uint8Array(image.width * image.height);
    for (let i = 0; i < values.length; i++) {
        const offset = i * 4;
        values[i] = (image.rgba[offset] + 2 * image.rgba[offset + 1] + image.rgba[offset + 2]) / 4;
    }
    return values;
}

function rectangleEvidence(values, width, height, box) {
    // Compare opposing edge coverage over the same span around the cat.
    function edge(horizontal, start, end, low, high) {
        const stride = horizontal ? width : 1;
        let best = { position: start, coverage: 0 };
        for (let position = start; position <= end; position++) {
            let count = 0, hits = 0;
            for (let cross = low; cross <= high; cross += 2) {
                const i = horizontal ? position * width + cross : cross * width + position;
                if (Math.abs(values[i + stride] - values[i - stride]) >= rules.edgeContrast) hits++;
                count++;
            }
            if (count && hits / count > best.coverage) best = { position, coverage: hits / count };
        }
        return best;
    }

    const left = edge(false, 2, box.left - 3, box.top, box.bottom - 1);
    const right = edge(false, box.right + 2, width - 3, box.top, box.bottom - 1);
    const top = edge(true, 2, box.top - 3, box.left, box.right - 1);
    const bottom = edge(true, box.bottom + 2, height - 3, box.left, box.right - 1);
    if ([left, right, top, bottom].some(side => side.coverage < rules.edgeCoverage)) return null;

    const bounds = { left: left.position, right: right.position, top: top.position, bottom: bottom.position };
    const w = bounds.right - bounds.left, h = bounds.bottom - bounds.top;
    if (Math.max(w, h) / Math.min(w, h) > 2.6 || Math.min(w, h) < 24) return null;
    const corners = [
        [bounds.left, bounds.top, 1, 1], [bounds.right, bounds.top, -1, 1],
        [bounds.left, bounds.bottom, 1, -1], [bounds.right, bounds.bottom, -1, -1]
    ];
    const confirmed = corners.filter(([x, y, dx, dy]) => {
        const i = (y + dy * 4) * width + x;
        const j = y * width + x + dx * 4;
        return Math.abs(values[i + 1] - values[i - 1]) >= rules.edgeContrast
            && Math.abs(values[j + width] - values[j - width]) >= rules.edgeContrast;
    }).length;
    return confirmed >= 3 ? bounds : null;
}

function texture(values, width, height, region) {
    let sum = 0, sumSquares = 0, edgeEnergy = 0, count = 0;
    const lags = [1, 2, 3, 4, 5, 6];
    const xCorrelation = lags.map(() => [0, 0, 0]);
    const yCorrelation = lags.map(() => [0, 0, 0]);
    function accumulate(stats, first, second) {
        stats[0] += first * second;
        stats[1] += first * first;
        stats[2] += second * second;
    }
    for (let y = Math.max(1, region.top); y < Math.min(height - 7, region.bottom - 7); y += 3) {
        for (let x = Math.max(1, region.left); x < Math.min(width - 7, region.right - 7); x += 3) {
            const i = y * width + x, v = values[i];
            const gx = 2 * v - values[i - 1] - values[i + 1];
            const gy = 2 * v - values[i - width] - values[i + width];
            sum += v;
            sumSquares += v * v;
            edgeEnergy += (Math.abs(gx) + Math.abs(gy)) / 2;
            count++;
            for (let index = 0; index < lags.length; index++) {
                const j = i + lags[index], k = i + lags[index] * width;
                accumulate(xCorrelation[index], gx, 2 * values[j] - values[j - 1] - values[j + 1]);
                accumulate(yCorrelation[index], gy, 2 * values[k] - values[k - width] - values[k + width]);
            }
        }
    }
    function periodicity(correlation) {
        const scores = correlation.map(([dot, a, b]) => a && b ? dot / Math.sqrt(a * b) : 0);
        // Favor a return to positive correlation after a trough, rather
        // than neighboring pixels that are simply similar everywhere.
        return Math.max(0, ...scores.slice(1).map((score, i) => scores.slice(0, i + 1).some(v => v < -0.20) ? score : 0));
    }
    const mean = count ? sum / count : 0;
    return {
        count, mean,
        variance: count ? Math.max(0, sumSquares / count - mean * mean) : 0,
        laplacian: count ? edgeEnergy / count : 0,
        periodicity: Math.max(periodicity(xCorrelation), periodicity(yCorrelation))
    };
}

function difference(current, previous, width, region, exclude) {
    let total = 0, count = 0;
    for (let y = region.top; y < region.bottom; y += 3) {
        for (let x = region.left; x < region.right; x += 3) {
            if (exclude && x >= exclude.left && x < exclude.right && y >= exclude.top && y < exclude.bottom) continue;
            const i = y * width + x;
            total += Math.abs(current[i] - previous[i]);
            count++;
        }
    }
    return count ? total / count : 0;
}

function analyze(sample, previous) {
    const { scene, subject, catBox } = sample;
    const { width, height } = scene;
    const values = luminance(scene);
    const full = { left: 0, top: 0, right: width, bottom: height };
    const rectangle = rectangleEvidence(values, width, height, catBox);
    const around = {
        left: Math.max(0, catBox.left - 20), top: Math.max(0, catBox.top - 20),
        right: Math.min(width, catBox.right + 20), bottom: Math.min(height, catBox.bottom + 20)
    };
    const cat = texture(luminance(subject), subject.width, subject.height, {
        left: 0, top: 0, right: subject.width, bottom: subject.height
    });
    const near = texture(values, width, height, rectangle ? {
        left: Math.max(around.left, rectangle.left + 2), top: Math.max(around.top, rectangle.top + 2),
        right: Math.min(around.right, rectangle.right - 2), bottom: Math.min(around.bottom, rectangle.bottom - 2)
    } : around);
    const wider = texture(values, width, height, full);
    const outside = rectangle ? [
        { ...full, right: rectangle.left - 3 },
        { ...full, left: rectangle.right + 3 },
        { left: rectangle.left, right: rectangle.right, top: 0, bottom: rectangle.top - 3 },
        { left: rectangle.left, right: rectangle.right, top: rectangle.bottom + 3, bottom: height }
    ].map(region => texture(values, width, height, region)).filter(region => region.count >= 16) : [];
    const outsidePeriod = Math.max(0, ...outside.map(region => region.periodicity));
    const outsideEnergy = Math.max(2, ...outside.map(region => region.laplacian));
    const localizedPattern = outside.length > 0 && near.laplacian >= 4
        && near.periodicity >= rules.minPeriodCorrelation && near.periodicity - outsidePeriod >= rules.minPeriodContrast;
    const textureContrast = outside.length > 0 && near.laplacian / outsideEnergy >= rules.minTextureRatio;
    const comparable = previous && previous.width === width && previous.height === height;
    const sceneDifference = comparable ? difference(values, previous.values, width, full) : null;
    const insideDifference = rectangle && comparable ? difference(values, previous.values, width, rectangle) : null;
    const outsideDifference = rectangle && comparable ? difference(values, previous.values, width, full, rectangle) : null;
    const localizedChange = insideDifference !== null && insideDifference >= rules.minTemporalDifference
        && insideDifference >= 2 * outsideDifference + 1;

    return {
        values, width, height, videoTime: sample.videoTime,
        evidence: { rectangle, cat, near, wider, outsidePeriod, outsideEnergy, localizedPattern, textureContrast,
            sceneDifference, insideDifference, outsideDifference, localizedChange }
    };
}

function summarizeWindow(windowFrames) {
    const evidence = windowFrames.map(frame => frame.evidence);
    const votes = {
        rectangle: evidence.filter(frame => frame.rectangle).length,
        localizedPattern: evidence.filter(frame => frame.localizedPattern).length,
        textureContrast: evidence.filter(frame => frame.textureContrast).length,
        localizedChange: evidence.filter(frame => frame.localizedChange).length,
        combined: evidence.filter(frame => frame.rectangle && frame.localizedPattern
            && (frame.textureContrast || frame.localizedChange)).length
    };
    const decision = votes.combined >= rules.minVotes ? 'REPLAY_SUSPECTED'
        : votes.rectangle || votes.localizedPattern || evidence.some(frame => frame.near.periodicity >= rules.minPeriodCorrelation)
            ? 'INCONCLUSIVE' : 'NORMAL';
    return { decision, votes, evidence };
}

let previous = null;
let processingMs = 0;
self.onmessage = ({ data }) => {
    if (previous && data.videoTime <= previous.videoTime) {
        self.postMessage({ type: 'sampled' });
        return;
    }
    if (previous && data.videoTime - previous.videoTime > 2) {
        previous = null;
        frames.length = 0;
        processingMs = 0;
    }
    const started = performance.now();
    previous = analyze(data, previous);
    frames.push({ evidence: previous.evidence, videoTime: data.videoTime });
    processingMs += performance.now() - started;
    if (frames.length < rules.windowSize) {
        self.postMessage({ type: 'sampled' });
        return;
    }
    const { decision, votes, evidence } = summarizeWindow(frames);
    self.postMessage({
        type: 'result', replayScreening: decision, votes, evidence,
        frames: frames.length, windowSeconds: frames.at(-1).videoTime - frames[0].videoTime,
        averageProcessingMs: processingMs / frames.length
    });
    frames.length = 0;
    processingMs = 0;
};

self.postMessage({ type: 'ready' });
