import { spawn } from 'node:child_process';
import { writeFileSync, mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
const php =
  process.env.SIAC_PHP ?? (process.platform === 'win32' ? resolve('.runtime/php/php.exe') : 'php');
const env = { ...process.env, APP_ENV: 'testing', DB_DATABASE: 'siac_sg_test' };
function run(args) {
  return new Promise((res, rej) => {
    const p = spawn(php, ['scripts/concurrency-worker.php', ...args], { env, windowsHide: true });
    let out = '',
      err = '';
    p.stdout.on('data', (d) => (out += d));
    p.stderr.on('data', (d) => (err += d));
    p.on('error', rej);
    p.on('exit', (code) => {
      if (code !== 0) return rej(new Error(err + out));
      try {
        res(JSON.parse(out));
      } catch {
        rej(new Error(out + err));
      }
    });
  });
}
mkdirSync('.runtime/concurrency', { recursive: true });
mkdirSync('output/verification', { recursive: true });
const results = [];
for (let i = 0; i < 5; i++) {
  const f = await run(['prepare']);
  const path = resolve(`.runtime/concurrency/fixture-${Date.now()}-${i}.json`);
  const barrier = path + '.go';
  writeFileSync(path, JSON.stringify(f));
  const calls = f.users.map((u, j) =>
    run(['book', String(u), String(f.slot), barrier, j ? 'bot' : 'web']),
  );
  setTimeout(() => writeFileSync(barrier, 'go'), 200);
  const outcomes = await Promise.all(calls);
  const db = await run(['verify', path]);
  const passed =
    outcomes.filter((o) => o.result === 'confirmed').length === 1 &&
    outcomes.filter((o) => o.result === 'conflict').length === 1 &&
    db.active === 1 &&
    db.audits === 1;
  results.push({ trial: i + 1, outcomes: outcomes.map((o) => o.result), database: db, passed });
}
writeFileSync(
  'output/verification/concurrency.json',
  JSON.stringify(
    {
      date: new Date().toISOString(),
      database: 'siac_sg_test',
      scope:
        '5 carreras con dos procesos PHP independientes, canales web y bot, mismo servicio de negocio y franja.',
      results,
    },
    null,
    2,
  ),
);
console.log(JSON.stringify(results));
if (results.some((r) => !r.passed)) process.exitCode = 1;
