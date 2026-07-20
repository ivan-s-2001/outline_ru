export type CollaborationConfig = {
  host: string;
  port: number;
  jwtSecret: Uint8Array;
  databaseUrl: string;
  redisUrl?: string;
  debounceMs: number;
  maxDebounceMs: number;
};

const required = (name: string): string => {
  const value = process.env[name];
  if (!value) throw new Error(`${name} is required`);
  return value;
};

export const config: CollaborationConfig = {
  host: process.env.COLLABORATION_HOST ?? "127.0.0.1",
  port: Number(process.env.COLLABORATION_PORT ?? 3001),
  jwtSecret: new TextEncoder().encode(required("COLLABORATION_SECRET")),
  databaseUrl: required("DATABASE_URL"),
  redisUrl: process.env.REDIS_URL || undefined,
  debounceMs: Number(process.env.COLLABORATION_DEBOUNCE_MS ?? 3000),
  maxDebounceMs: Number(process.env.COLLABORATION_MAX_DEBOUNCE_MS ?? 10000),
};
