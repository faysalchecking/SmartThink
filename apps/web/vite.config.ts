import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    // Development only: API calls go to the local API server.
    proxy: { "/api": { target: "http://127.0.0.1:3000" } },
  },
});
