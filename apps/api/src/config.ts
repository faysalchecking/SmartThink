import { z } from "zod";

export const MIN_SESSION_SECRET_LENGTH = 32;

export const ConfigSchema = z.object({
  NODE_ENV: z.enum(["development", "test", "production"]),
  APP_PORT: z.coerce.number().int().min(1).max(65535),
  APP_ORIGIN: z.url(),
  DATABASE_URL: z.string().regex(/^postgres(ql)?:\/\//, "must be a postgres:// URL"),
  SESSION_SECRET: z
    .string()
    .min(MIN_SESSION_SECRET_LENGTH, `must be at least ${MIN_SESSION_SECRET_LENGTH} characters`),
  FILE_STORAGE_PATH: z.string().min(1),
});

export type AppConfig = z.infer<typeof ConfigSchema>;

export function loadConfig(env: Record<string, string | undefined> = process.env): AppConfig {
  const result = ConfigSchema.safeParse(env);
  if (!result.success) {
    // Only field names and rule messages are shown. Values such as secrets and URLs are never printed.
    const details = result.error.issues
      .map((issue) => `- ${issue.path.join(".")}: ${issue.message}`)
      .join("\n");
    throw new Error(`Invalid configuration:\n${details}`);
  }
  return result.data;
}
