/**
 * axe smoke stub — pagine pubbliche (Sprint 60)
 *
 * Usage: node scripts/axe-smoke.js
 * Env: BASE_URL=http://localhost:8000
 *
 * Scan autenticato: usare axe DevTools manuale (docs/A11Y-AUDIT-RUNBOOK.md)
 */

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const BASE_URL = process.env.BASE_URL || 'http://localhost:8000';
const __dirname = dirname(fileURLToPath(import.meta.url));
const pages = JSON.parse(readFileSync(join(__dirname, 'a11y-pages.json'), 'utf8'));

const publicPages = pages.filter((page) => ! page.auth);

let failed = 0;

for (const page of publicPages) {
    const url = `${BASE_URL}${page.path}`;

    try {
        const response = await fetch(url);
        if (! response.ok) {
            console.error(`FAIL ${page.name}: HTTP ${response.status}`);
            failed += 1;
            continue;
        }

        const html = await response.text();
        if (! html.includes('<html') || html.length < 100) {
            console.error(`FAIL ${page.name}: risposta HTML non valida`);
            failed += 1;
            continue;
        }

        console.log(`OK   ${page.name} (${url})`);
    } catch (error) {
        console.error(`FAIL ${page.name}: ${error.message}`);
        failed += 1;
    }
}

console.log(`\naxe-smoke stub: ${publicPages.length - failed}/${publicPages.length} pagine pubbliche raggiungibili`);
console.log('Per scan axe completo: docs/A11Y-AUDIT-RUNBOOK.md');

process.exit(failed > 0 ? 1 : 0);
