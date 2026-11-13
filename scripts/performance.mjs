import { request } from '@playwright/test';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import os from 'node:os';
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';
const env = Object.fromEntries(
  readFileSync('.env', 'utf8')
    .split(/\r?\n/)
    .map((l) => {
      const i = l.indexOf('=');
      return [l.slice(0, i), l.slice(i + 1)];
    }),
);
const contexts = [];
const times = [];
const failures = [];
const duration = Number(process.env.SIAC_LOAD_SECONDS ?? 300);
const thinkMs = Number(process.env.SIAC_THINK_MS ?? 1000);
const baseURL = 'http://127.0.0.1:8000';
for (let i = 0; i < 10; i++) {
  const ctx = await request.newContext({ baseURL });
  await ctx.get('/acceso');
  const token = (await ctx.storageState()).cookies.find((c) => c.name === 'XSRF-TOKEN')?.value;
  const login = await ctx.post('/acceso', {
    form: { alias: i % 2 ? 'DEMO-ALBA' : 'DEMO-LEO', password: env.DEMO_PASSWORD },
    headers: { 'X-XSRF-TOKEN': decodeURIComponent(token) },
    maxRedirects: 0,
  });
  if (login.status() !== 302) throw new Error('Login failed: ' + login.status());
  contexts.push(ctx);
}
const php =
  process.env.SIAC_PHP ?? (process.platform === 'win32' ? resolve('.runtime/php/php.exe') : 'php');
const version = execFileSync(
  php,
  ['-r', "echo hash_file('xxh128','public/build/manifest.json');"],
  { encoding: 'utf8', windowsHide: true },
).trim();
await Promise.all(
  contexts.map((ctx) =>
    ctx.get('/disponibilidad', {
      headers: { 'X-Inertia': 'true', 'X-Inertia-Version': version, Accept: 'text/html' },
    }),
  ),
);
const start = Date.now();
console.log(`Medición iniciada: ${duration}s, 10 sesiones independientes.`);
await Promise.all(
  contexts.map(async (ctx, i) => {
    while (Date.now() - start < duration * 1000) {
      const t = performance.now();
      const res = await ctx.get(i % 2 ? '/mis-citas' : '/disponibilidad', {
        headers: { 'X-Inertia': 'true', 'X-Inertia-Version': version, Accept: 'text/html' },
      });
      times.push(performance.now() - t);
      if (res.status() !== 200) failures.push(res.status());
      await new Promise((r) => setTimeout(r, thinkMs));
    }
  }),
);
times.sort((a, b) => a - b);
const result = {
  date: new Date().toISOString(),
  durationSeconds: (Date.now() - start) / 1000,
  sessions: 10,
  requests: times.length,
  errors: failures.length,
  statuses: [...new Set(failures)],
  p95Ms: times[Math.ceil(times.length * 0.95) - 1],
  maxMs: times.at(-1),
  host: {
    os: os.type(),
    release: os.release(),
    cpu: os.cpus()[0].model,
    memoryGB: Math.round(os.totalmem() / 1024 ** 3),
    node: process.version,
  },
  thinkMs,
  scope:
    'Lecturas autenticadas de disponibilidad y mis citas; servidor PHP de desarrollo local con OPcache. Cada sesión espera thinkMs entre peticiones. Se mide respuesta HTTP completa.',
};
mkdirSync('output/verification', { recursive: true });
writeFileSync('output/verification/performance.json', JSON.stringify(result, null, 2));
console.log(JSON.stringify(result));
await Promise.all(contexts.map((c) => c.dispose()));
if (result.errors || result.p95Ms > 2000) process.exitCode = 1;
