import { runner } from "node-pg-migrate";

const direction = process.argv[2] === "down" ? "down" : "up";
const databaseUrl = process.env.MIGRATION_DATABASE_URL;

if (!databaseUrl) {
  console.error("MIGRATION_DATABASE_URL is not set. Add it to .env (never commit .env).");
  process.exit(1);
}

await runner({
  databaseUrl,
  dir: "migrations",
  direction,
  // "down" rolls back one migration. "up" applies all pending migrations.
  count: direction === "down" ? 1 : Infinity,
  migrationsTable: "pgmigrations",
  log: (message) => console.log(message),
});