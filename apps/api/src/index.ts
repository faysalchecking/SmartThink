import { sql } from "kysely";
import { loadConfig } from "./config.js";
import { createDatabase } from "./db/database.js";
import { buildServer } from "./server.js";

async function main(): Promise<void> {
  let config;
  try {
    config = loadConfig();
  } catch (error) {
    console.error((error as Error).message);
    process.exit(1);
  }

  const db = createDatabase(config.DATABASE_URL);
  const app = buildServer({
    checkDatabase: async () => {
      await sql`select 1`.execute(db);
      return true;
    },
  });

  const shutdown = async (): Promise<void> => {
    await app.close();
    await db.destroy();
    process.exit(0);
  };
  process.on("SIGINT", () => void shutdown());
  process.on("SIGTERM", () => void shutdown());

  // Listen on loopback only. Public exposure is decided in Phase 7 behind HTTPS.
  await app.listen({ port: config.APP_PORT, host: "127.0.0.1" });
}

main().catch((error: unknown) => {
  console.error("Startup failed:", (error as Error).message);
  process.exit(1);
});
