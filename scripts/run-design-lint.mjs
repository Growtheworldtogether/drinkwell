// Runs scripts/design-lint.sh with whichever bash is available (PATH, then Git for Windows).
import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';

const candidates = [
    'bash',
    'C:\\Program Files\\Git\\bin\\bash.exe',
    'C:\\Program Files (x86)\\Git\\bin\\bash.exe',
];

for (const bash of candidates) {
    if (bash.includes('\\') && !existsSync(bash)) continue;
    const result = spawnSync(bash, ['scripts/design-lint.sh'], { stdio: 'inherit' });
    if (result.error && result.error.code === 'ENOENT') continue;
    process.exit(result.status ?? 1);
}

console.error('design-lint: bash not found. Install Git for Windows or run scripts/design-lint.sh from a bash shell.');
process.exit(1);
