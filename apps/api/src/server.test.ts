import { afterEach, describe, expect, it } from "vitest";
import type { FastifyInstance } from "fastify";
import { buildServer } from "./server.js";

let app: FastifyInstance | undefined;

afterEach(async () => {
  await app?.close();
  app = undefined;
});

describe("GET /api/v1/health", () => {
  it("returns 200 and no internal details when the database responds", async () => {
    app = buildServer({ checkDatabase: async () => true });
    const res = await app.inject({ method: "GET", url: "/api/v1/health" });
    expect(res.statusCode).toBe(200);
    expect(res.json()).toEqual({ status: "ok" });
  });

  it("returns 503 without leaking the internal error when the database fails", async () => {
    app = buildServer({
      checkDatabase: async () => {
        throw new Error("connect ECONNREFUSED 10.0.0.5:5432");
      },
    });
    const res = await app.inject({ method: "GET", url: "/api/v1/health" });
    expect(res.statusCode).toBe(503);
    expect(res.json()).toEqual({ status: "unavailable" });
    expect(res.body).not.toContain("ECONNREFUSED");
    expect(res.body).not.toContain("10.0.0.5");
  });
});

describe("error responses", () => {
  it("returns the fixed error shape for an unknown route", async () => {
    app = buildServer({ checkDatabase: async () => true });
    const res = await app.inject({ method: "GET", url: "/does-not-exist" });
    expect(res.statusCode).toBe(404);
    expect(res.json()).toEqual({ error: { code: "NOT_FOUND", message: "Resource not found" } });
  });
});
