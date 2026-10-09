import { defineConfig } from "@playwright/test";
export default defineConfig({
    testDir: "./tests",
    testMatch: "**/*.spec.mjs",
    use: { baseURL: "http://127.0.0.1:4173" },
    webServer: {
        command: "node server.mjs",
        url: "http://127.0.0.1:4173",
        reuseExistingServer: !process.env.CI,
    },
    projects: [
        { name: "desktop", use: { viewport: { width: 1280, height: 900 } } },
        { name: "mobile", use: { viewport: { width: 390, height: 844 } } },
    ],
});
