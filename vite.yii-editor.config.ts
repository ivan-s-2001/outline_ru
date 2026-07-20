import path from "node:path";
import { fileURLToPath } from "node:url";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

const dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      "~": path.resolve(dirname, "app"),
      "@shared": path.resolve(dirname, "shared"),
    },
  },
  define: {
    "process.env.NODE_ENV": JSON.stringify("production"),
  },
  build: {
    outDir: path.resolve(dirname, "php-backend/web/editor"),
    emptyOutDir: true,
    sourcemap: true,
    cssCodeSplit: false,
    lib: {
      entry: path.resolve(dirname, "app/yii-editor/index.tsx"),
      formats: ["es"],
      fileName: () => "outline-editor.js",
      name: "OutlineYiiEditor",
    },
    rollupOptions: {
      output: {
        assetFileNames: (assetInfo) =>
          assetInfo.name?.endsWith(".css")
            ? "outline-editor.css"
            : "assets/[name]-[hash][extname]",
      },
    },
  },
});
