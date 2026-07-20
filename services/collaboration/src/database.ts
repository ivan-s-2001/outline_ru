import mysql from "mysql2/promise";
import { config } from "./config.js";

export const pool = mysql.createPool({
  uri: config.databaseUrl,
  connectionLimit: 10,
  enableKeepAlive: true,
  keepAliveInitialDelay: 0,
  timezone: "Z",
  charset: "utf8mb4",
});

export const parseDocumentId = (documentName: string): number => {
  const match = /^document\.(\d+)$/.exec(documentName);
  if (!match) throw new Error("Invalid document name");
  return Number(match[1]);
};
