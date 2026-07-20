function required(name: string): string {
  const value = process.env[name]?.trim();
  if (!value) {
    throw new Error(`${name} is required`);
  }
  return value;
}

function integer(name: string, fallback: number): number {
  const raw = process.env[name];
  if (!raw) {
    return fallback;
  }
  const value = Number.parseInt(raw, 10);
  if (!Number.isInteger(value) || value <= 0) {
    throw new Error(`${name} must be a positive integer`);
  }
  return value;
}

const jwtSecret = required("JWT_SECRET");
if (jwtSecret.length < 32) {
  throw new Error("JWT_SECRET must contain at least 32 characters");
}

export const config = {
  host: process.env.COLLABORATION_HOST?.trim() || "127.0.0.1",
  port: integer("COLLABORATION_PORT", 3010),
  jwtSecret: new TextEncoder().encode(jwtSecret),
  database: {
    host: process.env.DB_HOST?.trim() || "127.0.0.1",
    port: integer("DB_PORT", 3306),
    database: required("DB_NAME"),
    user: process.env.DB_USER?.trim() || "root",
    password: process.env.DB_PASSWORD || "",
    charset: process.env.DB_CHARSET?.trim() || "utf8mb4",
    connectionLimit: integer("COLLABORATION_DB_POOL", 8),
  },
  debounce: integer("COLLABORATION_DEBOUNCE_MS", 3000),
  maxDebounce: integer("COLLABORATION_MAX_DEBOUNCE_MS", 10000),
  timeout: integer("COLLABORATION_TIMEOUT_MS", 30000),
};
