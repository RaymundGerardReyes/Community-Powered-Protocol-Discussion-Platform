import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { NavAuth } from './NavAuth';
import * as AuthContextModule from './AuthContext';

describe('NavAuth Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders Sign In button when unauthenticated', () => {
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: null,
      token: null,
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    render(<NavAuth />);

    expect(screen.getByRole('button', { name: /Sign In/i })).toBeInTheDocument();
  });

  it('opens demo logins menu when Sign In is clicked', () => {
    const login = vi.fn();
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: null,
      token: null,
      isLoading: false,
      login,
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    render(<NavAuth />);

    const signInBtn = screen.getByRole('button', { name: /Sign In/i });
    fireEvent.click(signInBtn);

    expect(screen.getByText(/1-Click Demo Accounts/i)).toBeInTheDocument();
    expect(screen.getByText('Vitalik B.')).toBeInTheDocument();
    expect(screen.getByText('Alice Cryptographer')).toBeInTheDocument();

    const vitalikBtn = screen.getByRole('button', { name: /Vitalik B\./i });
    fireEvent.click(vitalikBtn);

    expect(login).toHaveBeenCalledWith('vitalik@protocol.io', 'password');
  });

  it('renders user details and Sign Out button when authenticated', () => {
    const logout = vi.fn();
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: {
        id: 2,
        name: 'Vitalik B.',
        email: 'vitalik@protocol.io',
        created_at: '2026-09-24T00:00:00Z',
      },
      token: 'token-abc',
      isLoading: false,
      login: vi.fn(),
      logout,
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    render(<NavAuth />);

    expect(screen.getByText('Vitalik B.')).toBeInTheDocument();
    expect(screen.getByText('VB')).toBeInTheDocument();

    const signOutBtn = screen.getByRole('button', { name: /Sign Out/i });
    fireEvent.click(signOutBtn);

    expect(logout).toHaveBeenCalled();
  });
});
