import { readFile, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';

const root = process.cwd();
const functionDirectory = path.join(
  root,
  '.vercel',
  'output',
  'functions',
  'api',
  'index.func',
);
const configPath = path.join(functionDirectory, '.vc-config.json');
const bridgeSource = 'scripts/vercel-php-launcher-bridge.js';
const config = JSON.parse(await readFile(configPath, 'utf8'));

if (config.handler !== 'launcher.launcher') {
  throw new Error(`Unexpected generated handler: ${config.handler}`);
}

const originalLauncher = config.filePathMap?.['launcher.js'];
if (!originalLauncher) {
  throw new Error('Generated vercel-php launcher was not found');
}

const blockedPrefixes = [
  'user/.agents/',
  'user/.aws/',
  'user/.codex/',
  'user/.env',
  'user/.git/',
  'user/.vercel/',
  'user/backups/',
  'user/public/uploads/',
  'user/writable/',
];

for (const bundledPath of Object.keys(config.filePathMap)) {
  if (blockedPrefixes.some((prefix) => bundledPath.startsWith(prefix))) {
    delete config.filePathMap[bundledPath];
  }
}

config.handler = 'launcher.js';
config.launcherType = 'Nodejs';
config.awsLambdaHandler = 'launcher.launcher';
delete config.filePathMap['launcher.js'];
config.filePathMap['launcher-orig.js'] = originalLauncher;
config.filePathMap['launcher.js'] = bridgeSource;

await writeFile(configPath, `${JSON.stringify(config, null, 2)}\n`);

// Local uploads can contain IDs, address proofs, and other personal records.
// They must never become public static deployment artifacts.
await rm(path.join(root, '.vercel', 'output', 'static', 'uploads'), {
  recursive: true,
  force: true,
});

const remainingSensitiveEntries = Object.keys(config.filePathMap).filter(
  (bundledPath) =>
    blockedPrefixes.some((prefix) => bundledPath.startsWith(prefix)),
);

if (remainingSensitiveEntries.length > 0) {
  throw new Error(
    `Sensitive entries remain in the bundle: ${remainingSensitiveEntries.join(', ')}`,
  );
}

console.log(
  `Patched ${configPath}; ${Object.keys(config.filePathMap).length} safe file mappings remain.`,
);
