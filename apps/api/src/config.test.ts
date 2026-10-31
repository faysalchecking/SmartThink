import { describe, expect, it } from "vitest";
import { loadConfig, MIN_SESSION_SECRET_LENGTH } from "./config.js";

const validEnv = {
  NODE_ENV: "development",
  APP_PORT: "3000",
  APP_ORIGIN: "http://localhost:5173",
  DATABASE_URL: "postgres://smartthink_app:pw@localhost:5432/smartthink_dev",
  SESSION_SECRET: "a".repeat(MIN_SESSION_SECRET_LENGTH),
  FILE_STORAGE_PATH: "./storage",
};

describe("loadConfig", () => {
  it("accepts a valid environment and converts the port to a number", () => {
    const config = loadConfig(validEnv);
    expect(config.APP_PORT).toBe(3000);
    expect(config.NODE_ENV).toBe("development");
  });

  it("rejects a session secret shorter than the minimum", () => {
    expect(() => loadConfig({ ...validEnv, SESSION_SECRET: "short-secret-value" })).toThrow(
      /SESSION_SECRET/,
    );
  });

  it("never echoes secret values in the error message", () => {
    const secretValue = "short-secret-value";
    try {
      loadConfig({ ...validEnv, SESSION_SECRET: secretValue });
      expect.fail("expected loadConfig to throw");
    } catch (error) {
      expect((error as Error).message).not.toContain(secretValue);
    }
  });

  it("rejects a database URL that is not PostgreSQL", () => {
    expect(() => loadConfig({ ...validEnv, DATABASE_URL: "mysql://x" })).toThrow(/DATABASE_URL/);
  });

  it("rejects a missing required variable", () => {
    expect(() => loadConfig({ ...validEnv, FILE_STORAGE_PATH: undefined })).toThrow(
      /FILE_STORAGE_PATH/,
    );
  });

  it("rejects an invalid port", () => {
    expect(() => loadConfig({ ...validEnv, APP_PORT: "70000" })).toThrow(/APP_PORT/);
  });
});
