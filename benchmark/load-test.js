import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const baseUrl = __ENV.BASE_URL || 'http://127.0.0.1:8765';
const errorRate = new Rate('failed_requests');

export const options = {
  stages: [
    { duration: '20s', target: 10 },
    { duration: '30s', target: 40 },
    { duration: '20s', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<500'],
  },
};

export default function () {
  const pages = [
    '/p_loginStu.php',
    '/p_terms.php',
    '/p_privacy.php',
    '/style.css',
    '/assets/css/layout.css',
  ];
  for (const path of pages) {
    const res = http.get(baseUrl + path);
    check(res, { 'status is 200': (r) => r.status === 200 });
    errorRate.add(res.status !== 200);
    sleep(0.1);
  }
}