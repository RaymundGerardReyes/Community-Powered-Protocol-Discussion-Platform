import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, act } from '@testing-library/react';
import { AuthProvider, useAuth } from './AuthContext';
import { apiClient } from '@/lib/api-client';

function TestConsumer() {
  const { user, token, isLoading, login, logout, isAuthModalOpen, openAuthModal, closeAuthModal } =
    useAuth();

  return (
    <div>
      <div data-testid="loading">{isLoading ? 'loading' : 'idle'}</div>
      <div data-testid="user">{user ? user.name : 'anonymous'}</div>
      <div data-testid="token">{token ?? 'none'}</div>
      <div data-testid="modal">{isAuthModalOpen ? 'open' : 'closed'}</div>
      <button onClick={() => login('vitalik@protocol.io', 'secret')}>Login</button>
      <button onClick={() => logout()}>Logout</button>
      <button onClick={openAuthModal}>Open Modal</button>
      <button onClick={closeAuthModal}>Close Modal</button>
    </div>
  );
}

describe('AuthContext Integration', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.restoreAllMocks();
  });

  afterEach(() => {
    localStorage.clear();
  });

  it('starts idle and unauthenticated when no token is in localStorage', () => {
    render(
      <AuthProvider>
        <TestConsumer />
      </AuthProvider>,
    );

    expect(screen.getByTestId('loading')).toHaveTextContent('idle');
    expect(screen.getByTestId('user')).toHaveTextContent('anonymous');
    expect(screen.getByTestId('token')).toHaveTextContent('none');
  });

  it('restores authenticated user session when valid token exists in localStorage', async () => {
    localStorage.setItem('auth_token', 'valid-stored-token');

    const mockUser = { id: 1, name: 'Vitalik B.', email: 'vitalik@protocol.io' };
    vi.spyOn(apiClient, 'get').mockResolvedValueOnce({
      data: { user: mockUser },
    });

    render(
      <AuthProvider>
        <TestConsumer />
      </AuthProvider>,
    );

    expect(screen.getByTestId('loading')).toHaveTextContent('loading');

    // Wait for the token check promise to resolve
    await act(async () => {
      await Promise.resolve();
    });

    expect(screen.getByTestId('loading')).toHaveTextContent('idle');
    expect(screen.getByTestId('user')).toHaveTextContent('Vitalik B.');
    expect(screen.getByTestId('token')).toHaveTextContent('valid-stored-token');
  });

  it('clears token from localStorage and resets state if me check fails', async () => {
    localStorage.setItem('auth_token', 'expired-token');

    vi.spyOn(apiClient, 'get').mockRejectedValueOnce(new Error('Unauthorized'));

    render(
      <AuthProvider>
        <TestConsumer />
      </AuthProvider>,
    );

    await act(async () => {
      await Promise.resolve();
    });

    expect(screen.getByTestId('loading')).toHaveTextContent('idle');
    expect(screen.getByTestId('user')).toHaveTextContent('anonymous');
    expect(localStorage.getItem('auth_token')).toBeNull();
  });

  it('login saves token, updates user state, and closes auth modal', async () => {
    const mockUser = { id: 1, name: 'Vitalik B.', email: 'vitalik@protocol.io' };
    vi.spyOn(apiClient, 'post').mockResolvedValueOnce({
      data: { user: mockUser, access_token: 'new-auth-token-123' },
    });

    render(
      <AuthProvider>
        <TestConsumer />
      </AuthProvider>,
    );

    // Open modal first
    act(() => {
      screen.getByText('Open Modal').click();
    });
    expect(screen.getByTestId('modal')).toHaveTextContent('open');

    // Trigger login
    await act(async () => {
      screen.getByText('Login').click();
    });

    expect(localStorage.getItem('auth_token')).toBe('new-auth-token-123');
    expect(screen.getByTestId('user')).toHaveTextContent('Vitalik B.');
    expect(screen.getByTestId('token')).toHaveTextContent('new-auth-token-123');
    expect(screen.getByTestId('modal')).toHaveTextContent('closed');
  });

  it('logout invalidates token, clears localStorage, and sets user to null', async () => {
    localStorage.setItem('auth_token', 'active-token');
    vi.spyOn(apiClient, 'post').mockResolvedValueOnce({ data: { message: 'Logged out' } });

    render(
      <AuthProvider>
        <TestConsumer />
      </AuthProvider>,
    );

    await act(async () => {
      screen.getByText('Logout').click();
    });

    expect(localStorage.getItem('auth_token')).toBeNull();
    expect(screen.getByTestId('user')).toHaveTextContent('anonymous');
    expect(screen.getByTestId('token')).toHaveTextContent('none');
  });

  it('throws error when useAuth is consumed outside AuthProvider', () => {
    // Suppress console.error during expected throw
    const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => render(<TestConsumer />)).toThrow(
      'useAuth must be used within an AuthProvider',
    );

    consoleError.mockRestore();
  });
});
