import Fastify, { type FastifyInstance } from "fastify";

const BODY_LIMIT_BYTES = 1024 * 1024;

export interface ServerDependencies {
  checkDatabase: () => Promise<boolean>;
}

export function buildServer(deps: ServerDependencies): FastifyInstance {
  const app = Fastify({
    bodyLimit: BODY_LIMIT_BYTES,
    logger: {
      level: "info",
      redact: ["req.headers.cookie", "req.headers.authorization", 'res.headers["set-cookie"]'],
    },
  });

  app.get("/api/v1/health", async (_request, reply) => {
    let healthy = false;
    try {
      healthy = await deps.checkDatabase();
    } catch {
      healthy = false;
    }
    // Internal error details are logged by the server only, never returned.
    return reply.code(healthy ? 200 : 503).send({ status: healthy ? "ok" : "unavailable" });
  });

  app.setNotFoundHandler((_request, reply) => {
    reply.code(404).send({ error: { code: "NOT_FOUND", message: "Resource not found" } });
  });

  app.setErrorHandler((error, request, reply) => {
    request.log.error({ err: error }, "request failed");
    const status =
      error.statusCode !== undefined && error.statusCode < 500 ? error.statusCode : 500;
    const body =
      status >= 500
        ? { error: { code: "INTERNAL", message: "Internal server error" } }
        : { error: { code: "BAD_REQUEST", message: "Request could not be processed" } };
    reply.code(status).send(body);
  });

  return app;
}
