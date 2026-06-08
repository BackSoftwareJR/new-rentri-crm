import { defineConfig, devices } from '@playwright/test';

const port = process.env.E2E_PORT ?? '8765';
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? `http://127.0.0.1:${port}`;

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: process.env.CI ? 'github' : 'list',
    timeout: 60_000,
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: process.env.PLAYWRIGHT_SKIP_WEBSERVER
        ? undefined
        : {
              command: `bash scripts/e2e-serve.sh`,
              url: baseURL,
              reuseExistingServer: !process.env.CI,
              timeout: 120_000,
              env: {
                  ...process.env,
                  E2E_PORT: port,
                  ALLOW_SESSION_DEMO: 'true',
              },
          },
});
