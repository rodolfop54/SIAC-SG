import { randomBytes } from 'node:crypto';
import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'node:fs';
if (existsSync('.env')) {
  console.log('.env existente conservado.');
  process.exit(0);
}
mkdirSync('.runtime', { recursive: true });
const demo = 'Demo!' + randomBytes(9).toString('hex');
let env = readFileSync('.env.example', 'utf8')
  .replace('DB_PASSWORD=', 'DB_PASSWORD=' + randomBytes(24).toString('hex'))
  .replace('DEMO_PASSWORD=', 'DEMO_PASSWORD=' + demo);
writeFileSync('.env', env);
writeFileSync(
  '.runtime/demo-access.txt',
  `Solo laboratorio local.\nUsuarios: DEMO-ALBA, DEMO-LEO, DEMO-SOL\nAdministrador: DEMO-ADMIN\nContraseña sintética: ${demo}\n`,
);
console.log(
  '.env generado. Accesos ficticios guardados en .runtime/demo-access.txt (excluido de Git).',
);
