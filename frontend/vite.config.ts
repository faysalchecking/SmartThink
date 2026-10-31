import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

const LARAVEL_DEV_SERVER = "http://127.0.0.1:8000";

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    strictPort: true,
    // Development only. In production the reverse proxy does this job.
    proxy: {
      "/api": { target: LARAVEL_DEV_SERVER },
      "/sanctum": { target: LARAVEL_DEV_SERVER },
    },
  },
});
