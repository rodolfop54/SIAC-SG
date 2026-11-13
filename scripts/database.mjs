import EmbeddedPostgres from 'embedded-postgres';
import { readFileSync, existsSync } from 'node:fs';
const env = Object.fromEntries(
  readFileSync('.env', 'utf8')
    .split(/\r?\n/)
    .filter((l) => l && !l.startsWith('#'))
    .map((l) => {
      const i = l.indexOf('=');
      return [l.slice(0, i), l.slice(i + 1).replace(/^"|"$/g, '')];
    }),
);
const pg = new EmbeddedPostgres({
  databaseDir: '.runtime/postgres',
  user: env.DB_USERNAME,
  password: env.DB_PASSWORD,
  port: Number(env.DB_PORT),
  persistent: true,
  onLog: () => {},
  onError: console.error,
});
if (process.argv[2] === 'stop') {
  await pg.stop();
  console.log('PostgreSQL detenido.');
} else {
  if (!existsSync('.runtime/postgres/PG_VERSION')) await pg.initialise();
  await pg.start();
  try {
    await pg.createDatabase(env.DB_DATABASE);
  } catch (e) {
    if (!String(e).includes('already exists')) throw e;
  }
  try {
    await pg.createDatabase('siac_sg_test');
  } catch (e) {
    if (!String(e).includes('already exists')) throw e;
  }
  console.log(
    `PostgreSQL local disponible en 127.0.0.1:${env.DB_PORT}. Datos persistentes en .runtime/postgres.`,
  );
}
