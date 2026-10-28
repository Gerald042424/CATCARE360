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

async function patchFile(filePath, patches) {
  let source = await readFile(filePath, 'utf8');
  let changed = false;

  for (const { original, replacement, alreadyPatched } of patches) {
    if (source.includes(alreadyPatched)) {
      continue;
    }
    if (!source.includes(original)) {
      throw new Error(`Expected vercel-php source pattern was not found in ${filePath}`);
    }
    source = source.replace(original, replacement);
    changed = true;
  }

  if (changed) {
    await writeFile(filePath, source, 'utf8');
  }
}

await patchFile(builderPath, [
  {
    original: 'const lambda = new build_utils_1.Lambda({',
    replacement: 'const lambda = new build_utils_1.NodejsLambda({',
    alreadyPatched: 'const lambda = new build_utils_1.NodejsLambda({',
  },
  {
    original: "        handler: 'launcher.launcher',\n        runtime: nodeVersion.runtime,",
    replacement: "        handler: 'launcher.js',\n        awsLambdaHandler: 'launcher.launcher',\n        runtime: nodeVersion.runtime,",
    alreadyPatched: "        awsLambdaHandler: 'launcher.launcher',",
  },
]);

await patchFile(launcherPath, [
  {
    original: 'const path_1 = require("path");\nlet server;',
    replacement: 'const path_1 = require("path");\nprocess.env.LAMBDA_TASK_ROOT = process.env.LAMBDA_TASK_ROOT || \'/var/task\';\nlet server;',
    alreadyPatched: "process.env.LAMBDA_TASK_ROOT = process.env.LAMBDA_TASK_ROOT || '/var/task';",
  },
  {
    original: 'async function launcher(event) {\n    const awsRequest = (0, helpers_1.normalizeEvent)(event);',
    replacement: `async function launcher(event) {
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
    alreadyPatched: 'const normalizedEvent = { ...event };',
  },
]);

console.log('Applied CatCare 360 compatibility patch to the installed vercel-php runtime.');
