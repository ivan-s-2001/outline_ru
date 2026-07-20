import { Redis } from "@hocuspocus/extension-redis";
import { Server } from "@hocuspocus/server";
import IORedis from "ioredis";
import { AuthenticationExtension } from "./authentication.js";
import { config } from "./config.js";
import { pool } from "./database.js";
import { PersistenceExtension } from "./persistence.js";

const extensions = [
  ...(config.redisUrl
    ? [
        new Redis({
          redis: new IORedis(config.redisUrl, {
            maxRetriesPerRequest: null,
            enableReadyCheck: true,
          }),
        }),
      ]
    : []),
  new AuthenticationExtension(),
  new PersistenceExtension(),
];

const server = Server.configure({
  address: config.host,
  port: config.port,
  debounce: config.debounceMs,
  maxDebounce: config.maxDebounceMs,
  timeout: 30000,
  extensions,
  onConnect: ({ documentName }) => {
    console.info(`[collaboration] connect ${documentName}`);
  },
  onDisconnect: ({ documentName }) => {
    console.info(`[collaboration] disconnect ${documentName}`);
  },
});

await pool.query("SELECT 1");
await server.listen();
console.info(`[collaboration] listening on ${config.host}:${config.port}`);

const shutdown = async () => {
  await server.destroy();
  await pool.end();
  process.exit(0);
};
process.on("SIGINT", shutdown);
process.on("SIGTERM", shutdown);
