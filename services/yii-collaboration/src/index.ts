import http from "node:http";
import { URL } from "node:url";
import { Server } from "@hocuspocus/server";
import WebSocket from "ws";
import { AuthenticationExtension } from "./AuthenticationExtension.js";
import { config } from "./config.js";
import { pool } from "./database.js";
import { PersistenceExtension } from "./PersistenceExtension.js";

const hocuspocus = Server.configure({
  debounce: config.debounce,
  maxDebounce: config.maxDebounce,
  timeout: config.timeout,
  extensions: [new AuthenticationExtension(), new PersistenceExtension()],
});

const server = http.createServer(async (request, response) => {
  const url = new URL(request.url ?? "/", "http://localhost");
  if (url.pathname === "/health") {
    try {
      await pool.query("SELECT 1");
      response.writeHead(200, { "Content-Type": "application/json" });
      response.end(
        JSON.stringify({
          ok: true,
          service: "outline-yii-collaboration",
          database: "mariadb",
          time: new Date().toISOString(),
        })
      );
    } catch (error) {
      response.writeHead(503, { "Content-Type": "application/json" });
      response.end(
        JSON.stringify({
          ok: false,
          error: error instanceof Error ? error.message : "Database unavailable",
        })
      );
    }
    return;
  }

  response.writeHead(404, { "Content-Type": "application/json" });
  response.end(JSON.stringify({ error: "Not found" }));
});

const websocket = new WebSocket.Server({
  noServer: true,
  maxPayload: 20 * 1024 * 1024,
});

websocket.on("error", (error) => {
  console.error("WebSocket server error", error);
});

server.on("upgrade", (request, socket, head) => {
  try {
    const url = new URL(request.url ?? "/", "http://localhost");
    if (!url.pathname.startsWith("/collaboration")) {
      socket.end("HTTP/1.1 404 Not Found\r\n\r\n");
      return;
    }

    const documentName = decodeURIComponent(
      url.pathname.split("/").filter(Boolean).at(-1) ?? ""
    );
    if (!/^document\.[0-9a-fA-F-]{36}$/.test(documentName)) {
      socket.end("HTTP/1.1 400 Bad Request\r\n\r\n");
      return;
    }

    socket.on("error", (error) => {
      const code = "code" in error ? error.code : undefined;
      if (code !== "ECONNRESET") {
        console.error("Collaboration socket error", error);
      }
    });

    websocket.handleUpgrade(request, socket, head, (client) => {
      client.on("error", (error) => {
        console.error("Collaboration client error", error);
      });
      hocuspocus.handleConnection(client, request, documentName);
    });
  } catch (error) {
    console.error("Unable to upgrade collaboration connection", error);
    socket.end("HTTP/1.1 400 Bad Request\r\n\r\n");
  }
});

server.listen(config.port, config.host, () => {
  console.log(
    `Outline Yii collaboration is listening on http://${config.host}:${config.port}`
  );
});

let shuttingDown = false;
async function shutdown(signal: string) {
  if (shuttingDown) {
    return;
  }
  shuttingDown = true;
  console.log(`Received ${signal}, shutting down collaboration service`);

  const force = setTimeout(() => process.exit(1), 10_000);
  force.unref();

  websocket.clients.forEach((client) => client.close(1001, "Server shutdown"));
  await hocuspocus.destroy();
  await new Promise<void>((resolve) => server.close(() => resolve()));
  await pool.end();
  clearTimeout(force);
  process.exit(0);
}

process.on("SIGINT", () => void shutdown("SIGINT"));
process.on("SIGTERM", () => void shutdown("SIGTERM"));
process.on("uncaughtException", (error) => {
  console.error("Uncaught collaboration error", error);
  void shutdown("uncaughtException");
});
process.on("unhandledRejection", (error) => {
  console.error("Unhandled collaboration rejection", error);
});
