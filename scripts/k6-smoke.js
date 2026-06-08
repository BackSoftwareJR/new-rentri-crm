/**
 * k6 smoke load test — CRM RENTRI (stub Sprint 59)
 *
 * Usage:
 *   k6 run scripts/k6-smoke.js
 *   BASE_URL=https://staging.example.com k6 run scripts/k6-smoke.js
 *
 * Requires: k6 (https://k6.io/docs/get-started/installation/)
 */

import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export const options = {
    vus: 5,
    duration: '30s',
    thresholds: {
        http_req_duration: ['p(95)<2000'],
        checks: ['rate>0.9'],
    },
};

export default function () {
    const health = http.get(`${BASE_URL}/up`);
    check(health, {
        'health returns 200': (r) => r.status === 200,
    });

    const loginPage = http.get(`${BASE_URL}/login`);
    check(loginPage, {
        'login page returns 200': (r) => r.status === 200,
        'login page has csrf': (r) => r.body.includes('csrf') || r.body.includes('_token'),
    });

    sleep(1);
}
