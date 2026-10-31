import { execSync } from "node:child_process";

const targets = {
  fastify: "5.12.7",
  kysely: "0.29.7",
  pg: "8.23.1",
  "node-pg-migrate": "9.0.0",
  zod: "4.6.7",
  react: "19.3.1",
  vite: "8.3.5",
  typescript: "7.0.2",
  vitest: "5.0.5",
};

const now = Date.now();
for (const [name, version] of Object.entries(targets)) {
  const times = JSON.parse(execSync(`npm view ${name} time --json`, { encoding: "utf8" }));
  const published = times[version];
  const ageDays = Math.floor((now - new Date(published).getTime()) / 86_400_000);
  const verdict = ageDays < 14 ? "TOO NEW (wait or pick previous)" : "OK";
  console.log(`${name}@${version}  published ${published}  (${ageDays} days ago)  ${verdict}`);
}