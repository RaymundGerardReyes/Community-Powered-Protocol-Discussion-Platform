import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { apiClient, type ApiError } from './api-client';
import axios from 'axios';

describe('apiClient', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  afterEach(() => {
    localStorage.clear();
    vi.restoreAllMocks();
  });

  it('has correct baseURL and headers configuration', () => {
    expect(apiClient.defaults.headers.Accept).toBe('application/json');
    expect(apiClient.defaults.headers['Content-Type']).toBe('application/json');
    expect(apiClient.defaults.withCredentials).toBe(true);
  });

  it('injects Bearer token into Authorization header when present in localStorage', async () => {
    localStorage.setItem('auth_token', 'test-token-xyz');

    // Run interceptor handler directly
    const interceptor = (apiClient.interceptors.request as any).handlers[0];
    const config = await interceptor.fulfilled({ headers: {} });

    expect(config.headers.Authorization).toBe('Bearer test-token-xyz');
  });

  it('does not inject Authorization header when localStorage is empty', async () => {
    const interceptor = (apiClient.interceptors.request as any).handlers[0];
    const config = await interceptor.fulfilled({ headers: {} });

    expect(config.headers.Authorization).toBeUndefined();
  });

  it('normalizes axios error into structured ApiError', async () => {
    const responseInterceptor = (apiClient.interceptors.response as any).handlers[0];

    const mockAxiosError = {
      message: 'Request failed with status code 422',
      response: {
        status: 422,
        data: {
          message: 'The email field is required.',
          errors: {
            email: ['The email field is required.'],
          },
        },
      },
    };

    await expect(responseInterceptor.rejected(mockAxiosError)).rejects.toEqual({
      message: 'The email field is required.',
      errors: {
        email: ['The email field is required.'],
      },
      status: 422,
    });
  });

  it('handles network error with fallback message and status 0', async () => {
    const responseInterceptor = (apiClient.interceptors.response as any).handlers[0];

    const mockNetworkError = {
      message: 'Network Error',
      response: undefined,
    };

    await expect(responseInterceptor.rejected(mockNetworkError)).rejects.toEqual({
      message: 'Network Error',
      errors: undefined,
      status: 0,
    });
  });
});
