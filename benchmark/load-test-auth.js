import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = __ENV.BASE_URL || 'http://127.0.0.1:8765';
const sessionCookie = __ENV.SESSION_COOKIE || '';

export const options = {
  stages: [
    { duration: '10s', target: 5 },
    { duration: '20s', target: 20 },
    { duration: '10s', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<800'],
  },
};

export function setup() {
  if (sessionCookie === '') {
    throw new Error('SESSION_COOKIE is required. Export cookies from a logged-in browser session first.');
  }
  return sessionCookie;
}

export default function (cookie) {
  const params = {
    headers: {
      Cookie: cookie,
    },
  };
  const endpoints = [
    '/api.php?action=unread',
    '/p_dynamics.php?page=1',
    '/p_notifications.php?page=1',
    '/p_messages.php',
  ];
  for (const path of endpoints) {
    const res = http.get(baseUrl + path, params);
    check(res, { 'authenticated endpoint returns 200': (r) => r.status === 200 });
    if (res.status !== 200) {
      console.error('non-200 response', path, res.status);
    }
    sleep(0.2);
  }
}