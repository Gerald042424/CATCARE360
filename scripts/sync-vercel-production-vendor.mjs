import { readdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const root = process.cwd();
const configPath = path.join(
  root,
  '.vercel',
  'output',
  'functions',
  'api',
  'index.func',
  '.vc-config.json',
);
const vendorRoot = path.join(root, '.vercel', 'production-root', 'vendor');
const config = JSON.parse(await readFile(configPath, 'utf8'));

async function listFiles(directory) {
  const entries = await readdir(directory, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const absolutePath = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      files.push(...await listFiles(absolutePath));
    } else if (entry.isFile()) {
      files.push(absolutePath);
    }
  }

  return files;
}

for (const bundledPath of Object.keys(config.filePathMap)) {
  if (bundledPath.startsWith('user/vendor/')) {
    delete config.filePathMap[bundledPath];
  }
}

const productionFiles = await listFiles(vendorRoot);
for (const absolutePath of productionFiles) {
  const relativePath = path.relative(vendorRoot, absolutePath).split(path.sep).join('/');
  config.filePathMap[`user/vendor/${relativePath}`] =
    path.relative(root, absolutePath).split(path.sep).join('/');
}

for (const requiredPath of [
  'user/vendor/autoload.php',
  'user/vendor/composer/autoload_real.php',
  'user/vendor/composer/platform_check.php',
]) {
  if (! config.filePathMap[requiredPath]) {
    throw new Error(`Production Composer file is missing: ${requiredPath}`);
  }
}

await writeFile(configPath, `${JSON.stringify(config, null, 2)}\n`);
console.log(`Mapped ${productionFiles.length} production-only Composer files.`);
