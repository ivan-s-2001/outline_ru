import { spawnSync } from "node:child_process";
import fs from "node:fs/promises";
import path from "node:path";

const root = path.resolve(import.meta.dirname, "..");
const corepack = process.platform === "win32" ? "corepack.cmd" : "corepack";
const env = {
  ...process.env,
  YARN_ENABLE_GLOBAL_CACHE: "true",
  YARN_NM_MODE: "hardlinks-global",
};

function run(args) {
  console.log(`\n> corepack ${args.join(" ")}`);
  const result = spawnSync(corepack, args, {
    cwd: root,
    env,
    stdio: "inherit",
  });

  if (result.error) {
    throw result.error;
  }
  if (result.status !== 0) {
    process.exit(result.status ?? 1);
  }
}

await fs.rm(path.join(root, "build"), { recursive: true, force: true });

run(["yarn", "vite:build"]);
run([
  "yarn",
  "i18next",
  "--silent",
  "{shared,app,server,plugins}/**/*.{ts,tsx}",
]);

const localeSource = path.join(root, "shared", "i18n", "locales");
const localeTarget = path.join(root, "build", "shared", "i18n", "locales");
await fs.mkdir(path.dirname(localeTarget), { recursive: true });
await fs.cp(localeSource, localeTarget, { recursive: true });

run(["yarn", "build:server"]);
run(["yarn", "workspaces", "focus", "--production"]);

console.log("\nСборка завершена. В node_modules оставлены только runtime-зависимости.");
