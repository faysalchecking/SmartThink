import { execSync } from "node:child_process";

const MIN_AGE_DAYS = 14;
const DAY_MS = 86_400_000;

// Exact versions we intend to pin. Change here only after the output shows them as OK.
const pins = [
  ["fastify", "5.12.7"],
  ["kysely", "0.29.6"],
  ["pg", "8.23.1"],
  ["node-pg-migrate", "9.0.0"],
  ["zod", "4.6.7"],
  ["react", "19.3.1"],
  ["react-dom", "19.3.1"],
  ["@types/react", "19.3.0"],
  ["@types/react-dom", "19.3.0"],
  ["@types/pg", "8.23.1"],
  ["vite", "8.3.4"],
  ["@vitejs/plugin-react", "6.1.2"],
  ["vitest", "5.0.4"],
  ["eslint", "10.12.0"],
  ["@eslint/js", "10.0.1"],
  ["typescript-eslint", "8.71.2"],
  ["prettier", "3.9.10"],
  ["globals", "17.13.0"],
  ["typescript", "6.0.3"],
];

// Packages whose peer dependencies must be checked against the pins above.
const peerCheck = ["typescript-eslint", "vitest", "@vitejs/plugin-react", "node-pg-migrate"];

// @types/node must match the Node 24 runtime. We pick the newest 24.x that is old enough.
const nodeTypesMajor = "24";

const ageDays = (iso) => Math.floor((Date.now() - new Date(iso).getTime()) / DAY_MS);

function readJson(command) {
  return JSON.parse(execSync(command, { encoding: "utf8", maxBuffer: 50 * 1024 * 1024 }));
}

function publishTimes(name) {
  return readJson(`npm view ${name} time --json`);
}

function report(name, version) {
  const times = publishTimes(name);
  const published = times[version];
  if (!published) {
    console.log(`${name}@${version}  NOT FOUND in registry`);
    return;
  }
  const age = ageDays(published);
  const status = age < MIN_AGE_DAYS ? "TOO NEW" : "OK";
  console.log(`${name}@${version}  published ${published.slice(0, 10)}  ${age} days  ${status}`);
}

console.log("== Pinned versions ==");
for (const [name, version] of pins) report(name, version);

console.log("\n== @types/node (newest 24.x older than", MIN_AGE_DAYS, "days) ==");
{
  const times = publishTimes("@types/node");
  const candidates = Object.entries(times)
    .filter(([v]) => v.startsWith(`${nodeTypesMajor}.`) && /^\d+\.\d+\.\d+$/.test(v))
    .filter(([, t]) => ageDays(t) >= MIN_AGE_DAYS)
    .sort(([a], [b]) => {
      const pa = a.split(".").map(Number);
      const pb = b.split(".").map(Number);
      for (let i = 0; i < 3; i++) if (pa[i] !== pb[i]) return pa[i] - pb[i];
      return 0;
    });
  const [v, t] = candidates.at(-1) ?? [];
  console.log(v ? `@types/node@${v}  published ${t.slice(0, 10)}  ${ageDays(t)} days  CANDIDATE` : "none found");
}

console.log("\n== Peer dependencies of pinned packages ==");
for (const name of peerCheck) {
  const pin = pins.find(([n]) => n === name);
  const peers = readJson(`npm view ${name}@${pin[1]} peerDependencies --json`);
  console.log(`${name}@${pin[1]} peers:`, JSON.stringify(peers));
}