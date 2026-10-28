import { spawnSync } from 'node:child_process';

// The outer Git build calls a standalone inner Vercel build so we can patch
// the generated vercel-php launcher before the Build Output API is uploaded.
// The guard prevents the inner build from recursively invoking this command.
if (process.env.CATCARE_INNER_VERCEL_BUILD === '1') {
  console.log('Inner vercel-php build detected; continuing without recursion.');
  process.exit(0);
}

const environment = process.env.VERCEL_ENV === 'production' ? 'production' : 'preview';
const vercelArguments = [
  '--yes',
  'vercel@65.5.0',
  'build',
  '--yes',
  '--environment',
  environment,
  '--local-config',
  'vercel.builder.json',
];
const executable = process.platform === 'win32'
  ? (process.env.ComSpec || 'C:\\Windows\\System32\\cmd.exe')
  : 'npx';
const argumentsList = process.platform === 'win32'
  ? ['/d', '/s', '/c', 'npx', ...vercelArguments]
  : vercelArguments;
const result = spawnSync(
  executable,
  argumentsList,
  {
    cwd: process.cwd(),
    env: { ...process.env, CATCARE_INNER_VERCEL_BUILD: '1' },
    stdio: 'inherit',
  },
);

if (result.error) {
  throw result.error;
}
if (result.status !== 0) {
  throw new Error(`Inner Vercel build failed with exit code ${result.status}.`);
}

await import('./patch-vercel-output.mjs');
console.log('Git-triggered Vercel build output is patched and ready.');
