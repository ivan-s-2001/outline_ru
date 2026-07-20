import { existsSync, statSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { build } from "esbuild";

const directory = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(directory, "../..");
const outputDirectory = path.resolve(
  root,
  "php-backend/services/collaboration/dist"
);

const aliases = {
  "~": path.resolve(root, "app"),
  "@shared": path.resolve(root, "shared"),
  "@server": path.resolve(root, "server"),
};

const moduleExtensions = [
  "",
  ".ts",
  ".tsx",
  ".js",
  ".jsx",
  ".mjs",
  ".cjs",
  ".json",
];

function isFile(candidate) {
  return existsSync(candidate) && statSync(candidate).isFile();
}

function resolveAliasModule(basePath) {
  for (const extension of moduleExtensions) {
    const candidate = `${basePath}${extension}`;
    if (isFile(candidate)) {
      return candidate;
    }
  }

  for (const extension of moduleExtensions.slice(1)) {
    const candidate = path.join(basePath, `index${extension}`);
    if (isFile(candidate)) {
      return candidate;
    }
  }

  return undefined;
}

await build({
  entryPoints: [path.resolve(directory, "src/index.ts")],
  outfile: path.resolve(outputDirectory, "collaboration.mjs"),
  platform: "node",
  format: "esm",
  target: "node22",
  bundle: true,
  minify: false,
  sourcemap: true,
  legalComments: "none",
  loader: {
    ".css": "empty",
    ".woff": "empty",
    ".woff2": "empty",
    ".ttf": "empty",
  },
  define: {
    "process.env.NODE_ENV": JSON.stringify("production"),
  },
  plugins: [
    {
      name: "outline-aliases",
      setup(context) {
        for (const [prefix, replacement] of Object.entries(aliases)) {
          context.onResolve(
            {
              filter: new RegExp(
                `^${prefix.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")}(?:/|$)`
              ),
            },
            (args) => {
              const unresolved = path.join(
                replacement,
                args.path.slice(prefix.length)
              );
              const resolved = resolveAliasModule(unresolved);
              if (!resolved) {
                return {
                  errors: [
                    {
                      text: `Cannot resolve Outline alias ${args.path}`,
                    },
                  ],
                };
              }
              return { path: resolved };
            }
          );
        }
      },
    },
  ],
  external: ["bufferutil", "utf-8-validate"],
  logLevel: "info",
});
