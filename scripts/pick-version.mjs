import { execSync } from "node:child_process";

const [name, minAgeArg] = process.argv.slice(2);
const minAgeDays = Number(minAgeArg ?? 14);

if (!name || Number.isNaN(minAgeDays)) {
  console.error("usage: node scripts/pick-version.mjs <package> [minAgeDays=14]");
  process.exit(1);
}

const times = JSON.parse(
  execSync(`npm view ${name} time --json`, { encoding: "utf8", maxBuffer: 20 * 1024 * 1024 }),
);

const stable = /^\d+\.\d+\.\d+$/;
const cutoff = Date.now() - minAgeDays * 86_400_000;

function compareSemver(a, b) {
  const pa = a.split(".").map(Number);
  const pb = b.split(".").map(Number);
  for (let i = 0; i < 3; i++) {
    if (pa[i] !== pb[i]) return pa[i] - pb[i];
  }
  return 0;
}

const chosen = Object.entries(times)
  .filter(([version]) => stable.test(version))
  .filter(([, published]) => new Date(published).getTime() <= cutoff)
  .sort(([a], [b]) => compareSemver(a, b))
  .at(-1);

if (!chosen) {
  console.log(`${name}: no stable version older than ${minAgeDays} days`);
  process.exit(2);
}

const [version, published] = chosen;
const ageDays = Math.floor((Date.now() - new Date(published).getTime()) / 86_400_000);
console.log(`${name}@${version}  published ${published}  (${ageDays} days ago)`);