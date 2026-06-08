/**
 * k6 authenticated load test — CRM RENTRI (Sprint 74)
 *
 * Usage:
 *   k6 run scripts/k6-authenticated.js
 *   BASE_URL=https://staging.example.com k6 run scripts/k6-authenticated.js
 *   K6_EMAIL=segreteria@example.com K6_PASSWORD=password k6 run scripts/k6-authenticated.js
 *
 * Scenarios: login session cookie → segreteria dashboard + operatore bonifica.
 * Requires: k6 (https://k6.io/docs/get-started/installation/)
 */

import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const SEGRETERIA_EMAIL = __ENV.K6_EMAIL || 'segreteria@example.com';
const SEGRETERIA_PASSWORD = __ENV.K6_PASSWORD || 'password';
const OPERATORE_EMAIL = __ENV.K6_OPERATORE_EMAIL || 'operatore@example.com';
const OPERATORE_PASSWORD = __ENV.K6_OPERATORE_PASSWORD || 'password';

export const options = {
    scenarios: {
        segreteria_smoke: {
            executor: 'constant-vus',
            vus: 3,
            duration: '20s',
            exec: 'segreteriaFlow',
        },
        operatore_smoke: {
            executor: 'constant-vus',
            vus: 2,
            duration: '20s',
            exec: 'operatoreFlow',
            startTime: '5s',
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<3000'],
        checks: ['rate>0.85'],
    },
};

function extractCsrf(html) {
    const match = html.match(/name="_token"\s+value="([^"]+)"/);
    return match ? match[1] : null;
}

function login(email, password) {
    const jar = http.cookieJar();
    const loginPage = http.get(`${BASE_URL}/login`);
    const csrf = extractCsrf(loginPage.body);

    check(loginPage, {
        'login page 200': (r) => r.status === 200,
        'csrf token found': () => csrf !== null,
    });

    if (!csrf) {
        return jar;
    }

    const loginRes = http.post(
        `${BASE_URL}/login`,
        {
            _token: csrf,
            email: email,
            password: password,
        },
        {
            jar,
            redirects: 0,
        },
    );

    check(loginRes, {
        'login redirect or ok': (r) => r.status === 302 || r.status === 200,
    });

    return jar;
}

export function segreteriaFlow() {
    const jar = login(SEGRETERIA_EMAIL, SEGRETERIA_PASSWORD);

    const dashboard = http.get(`${BASE_URL}/segreteria`, { jar });
    check(dashboard, {
        'segreteria dashboard 200': (r) => r.status === 200,
        'dashboard content': (r) => r.body.includes('Dashboard') || r.body.includes('VFU'),
    });

    const vfu = http.get(`${BASE_URL}/segreteria/vfu`, { jar });
    check(vfu, {
        'segreteria vfu 200': (r) => r.status === 200,
    });

    sleep(1);
}

export function operatoreFlow() {
    const jar = login(OPERATORE_EMAIL, OPERATORE_PASSWORD);

    const dashboard = http.get(`${BASE_URL}/operatore`, { jar });
    check(dashboard, {
        'operatore dashboard 200': (r) => r.status === 200,
    });

    const bonifica = http.get(`${BASE_URL}/operatore/bonifica`, { jar });
    check(bonifica, {
        'operatore bonifica 200': (r) => r.status === 200,
        'bonifica list': (r) => r.body.includes('bonific') || r.body.includes('Bonifica'),
    });

    sleep(1);
}

export default function () {
    segreteriaFlow();
}
