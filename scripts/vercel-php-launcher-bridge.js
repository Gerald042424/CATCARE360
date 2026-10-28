'use strict';

// Vercel's current bootstrap no longer supplies the Lambda task root expected
// by vercel-php. Normalize it before loading the original runtime launcher.
process.env.LAMBDA_TASK_ROOT = process.env.LAMBDA_TASK_ROOT || '/var/task';

const originalLauncher = require('./launcher-orig.js');

exports.launcher = async (event, context) => {
  const normalizedEvent = { ...event };

  if (Array.isArray(normalizedEvent.body)) {
    normalizedEvent.body = Buffer.from(normalizedEvent.body);
  } else if (typeof normalizedEvent.body === 'string') {
    normalizedEvent.body = Buffer.from(
      normalizedEvent.body,
      normalizedEvent.isBase64Encoded ? 'base64' : 'utf8',
    );
  } else {
    normalizedEvent.body = undefined;
  }

  const headers = normalizedEvent.headers || {};
  normalizedEvent.host =
    normalizedEvent.host || headers['x-forwarded-host'] || headers.host;

  return originalLauncher.launcher(normalizedEvent, context);
};
