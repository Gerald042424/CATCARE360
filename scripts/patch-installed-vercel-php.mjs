import { readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const builderRoot = path.join(
  process.cwd(),
  '.vercel',
  'builders',
  'node_modules',
  'vercel-php',
  'dist',
);
const builderPath = path.join(builderRoot, 'index.js');
const launcherPath = path.join(builderRoot, 'launchers', 'builtin.js');

async function updateFile(filePath, transform) {
  const source = await readFile(filePath, 'utf8');
  const updated = transform(source);
  if (updated !== source) {
    await writeFile(filePath, updated, 'utf8');
  }
}

await updateFile(builderPath, (source) => {
  let updated = source;

  if (!/new\s+(?:[\w$]+\.)*NodejsLambda\s*\(/.test(updated)) {
    updated = updated.replace(
      /new\s+((?:[\w$]+\.)*)Lambda\s*\(/,
      'new $1NodejsLambda(',
    );
  }
  if (!updated.includes("awsLambdaHandler: 'launcher.launcher'")) {
    const legacyHandler = /handler\s*:\s*(['"])launcher\.launcher\1\s*,/;
    const fileHandler = /handler\s*:\s*(['"])launcher\.js\1\s*,/;
    updated = legacyHandler.test(updated)
      ? updated.replace(
          legacyHandler,
          "handler: 'launcher.js',\n        awsLambdaHandler: 'launcher.launcher',",
        )
      : updated.replace(
          fileHandler,
          "handler: 'launcher.js',\n        awsLambdaHandler: 'launcher.launcher',",
        );
  }

  if (
    !/new\s+(?:[\w$]+\.)*NodejsLambda\s*\(/.test(updated) ||
    !/handler\s*:\s*['"]launcher\.js['"]/.test(updated) ||
    !/awsLambdaHandler\s*:\s*['"]launcher\.launcher['"]/.test(updated)
  ) {
    throw new Error('Unsupported vercel-php builder shape; refusing to emit an unsafe function.');
  }
  return updated;
});

await updateFile(launcherPath, (source) => {
  let updated = source;

  if (!updated.includes('process.env.LAMBDA_TASK_ROOT')) {
    updated = updated.replace(
      /(const\s+path_1\s*=\s*require\(["']path["']\);?)/,
      "$1\nprocess.env.LAMBDA_TASK_ROOT = process.env.LAMBDA_TASK_ROOT || '/var/task';",
    );
  }
  if (!updated.includes('const normalizedEvent = { ...event };')) {
    updated = updated.replace(
      /(async\s+function\s+launcher\s*\(event\)\s*\{)\s*const\s+awsRequest\s*=\s*\(0,\s*helpers_1\.normalizeEvent\)\(event\);/,
      `$1
    const normalizedEvent = { ...event };
    if (Array.isArray(normalizedEvent.body)) {
        normalizedEvent.body = Buffer.from(normalizedEvent.body);
    }
    else if (typeof normalizedEvent.body === 'string') {
        normalizedEvent.body = Buffer.from(normalizedEvent.body, normalizedEvent.isBase64Encoded ? 'base64' : 'utf8');
    }
    else {
        normalizedEvent.body = undefined;
    }
    const headers = normalizedEvent.headers || {};
    normalizedEvent.host = normalizedEvent.host || headers['x-forwarded-host'] || headers.host;
    const awsRequest = (0, helpers_1.normalizeEvent)(normalizedEvent);`,
    );
  }

  if (
    !updated.includes('process.env.LAMBDA_TASK_ROOT') ||
    !updated.includes('const normalizedEvent = { ...event };')
  ) {
    throw new Error('Unsupported vercel-php launcher shape; refusing to deploy an unpatched runtime.');
  }
  return updated;
});

console.log('Applied CatCare 360 compatibility patch to the installed vercel-php runtime.');
