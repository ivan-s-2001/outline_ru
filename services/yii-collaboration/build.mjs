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
  define: {
    "process.env.NODE_ENV": JSON.stringify("production"),
  },
  plugins: [
    {
      name: "outline-aliases",
      setup(context) {
        for (const [prefix, replacement] of Object.entries(aliases)) {
          context.onResolve(
            { filter: new RegExp(`^${prefix.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")}(?:/|$)`) },
            (args) => ({
              path: path.join(replacement, args.path.slice(prefix.length)),
            })
          );
        }
      },
    },
  ],
  external: ["bufferutil", "utf-8-validate"],
  logLevel: "info",
});
