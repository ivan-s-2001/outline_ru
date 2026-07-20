import { existsSync, readFileSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

function parseValue(raw: string): string {
  const value = raw.trim();
  if (
    value.length >= 2 &&
    ((value.startsWith('"') && value.endsWith('"')) ||
      (value.startsWith("'") && value.endsWith("'")))
  ) {
    return value.slice(1, -1);
  }
  return value;
}

function candidateFiles(): string[] {
  const explicit = process.env.OUTLINE_ENV_FILE?.trim();
  const runtimeDirectory = path.dirname(fileURLToPath(import.meta.url));
  return [
    explicit || "",
    path.resolve(process.cwd(), ".env"),
    path.resolve(runtimeDirectory, "../../../.env"),
  ].filter(Boolean);
}

for (const filename of candidateFiles()) {
  if (!existsSync(filename)) {
    continue;
  }

  const lines = readFileSync(filename, "utf8").split(/\r?\n/);
  for (const source of lines) {
    const line = source.trim();
    if (!line || line.startsWith("#")) {
      continue;
    }
    const separator = line.indexOf("=");
    if (separator <= 0) {
      continue;
    }
    const name = line.slice(0, separator).trim();
    if (!name || process.env[name] !== undefined) {
      continue;
    }
    process.env[name] = parseValue(line.slice(separator + 1));
  }
  break;
}
