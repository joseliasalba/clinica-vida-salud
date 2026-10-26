import { cp, mkdir } from 'node:fs/promises';

await mkdir('public', { recursive: true });
await cp('sistema/backend/public/build', 'public/build', { recursive: true });

for (const asset of ['favicon.ico', 'robots.txt']) {
    await cp(`sistema/backend/public/${asset}`, `public/${asset}`);
}
