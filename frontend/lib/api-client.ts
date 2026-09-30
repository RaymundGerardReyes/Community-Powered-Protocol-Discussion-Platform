// Single source of truth for talking to the Laravel 13 REST API.
// No component calls fetch()/axios directly — import this instead.
import axios, { type AxiosError } from 'axios';
import http from 'node:http';
import https from 'node:https';

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status: number;
}

const isServer = typeof window === 'undefined';

export const apiClient = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_BASE_URL ?? 'http://127.0.0.1:8000',
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
  withCredentials: true,
  httpAgent: isServer ? new http.Agent({ keepAlive: false }) : undefined,
  httpsAgent: isServer ? new https.Agent({ keepAlive: false }) : undefined,
});


// Request interceptor: add auth token from localStorage (client-side only)
apiClient.interceptors.request.use((config) => {
  if (typeof window !== 'undefined') {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

// Response interceptor: normalize errors to ApiError shape
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>) => {
    const apiError: ApiError = {
      message: error.response?.data?.message ?? error.message ?? 'Unknown error',
      errors: error.response?.data?.errors,
      status: error.response?.status ?? 0,
    };
    if (process.env.NODE_ENV === 'development') {
      if (apiError.status >= 500) {
        console.error('[api-client]', apiError);
      } else {
        console.warn('[api-client]', `${apiError.status}: ${apiError.message}`);
      }
    }
    return Promise.reject(apiError);
  },
);
