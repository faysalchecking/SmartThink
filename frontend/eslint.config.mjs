import js from "@eslint/js";
import globals from "globals";
import tseslint from "typescript-eslint";

export default tseslint.config(
  {
    ignores: ["dist/**", "node_modules/**", "coverage/**"],
  },
  {
    // Type-aware rules only for source files covered by tsconfig.json.
    files: ["src/**/*.{ts,tsx}"],
    extends: [js.configs.recommended, ...tseslint.configs.strictTypeChecked],
    languageOptions: {
      globals: globals.browser,
      parserOptions: {
        projectService: true,
        tsconfigRootDir: import.meta.dirname,
      },
    },
  },
  {
    // Config files: no type-aware rules.
    files: ["vite.config.ts", "eslint.config.mjs"],
    extends: [js.configs.recommended, tseslint.configs.disableTypeChecked],
    languageOptions: {
      globals: globals.node,
    },
  },
);
