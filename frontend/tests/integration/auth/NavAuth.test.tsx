import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { NavAuth } from '@/features/auth/NavAuth';
import * as AuthContextModule from '@/features/auth/AuthContext';

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
    expect(screen.getByRole('button', { name: 'Sign In' })).toBeInTheDocument();
  });

  it('opens demo logins menu when Sign In is clicked', () => {
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
    const signInBtn = screen.getByRole('button', { name: 'Sign In' });
    fireEvent.click(signInBtn);

    expect(screen.getByText('1-Click Demo Accounts')).toBeInTheDocument();
    expect(screen.getByText('Vitalik B.')).toBeInTheDocument();
    expect(screen.getByText('Alice Cryptographer')).toBeInTheDocument();
  });

  it('renders user name and Sign Out button when authenticated', () => {
    const mockLogout = vi.fn();
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: {
        id: 1,
        name: 'Vitalik B.',
        email: 'vitalik@protocol.io',
        created_at: '2026-09-01T00:00:00Z',
      },
      token: 'fake-token',
      isLoading: false,
      login: vi.fn(),
      logout: mockLogout,
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    render(<NavAuth />);
    expect(screen.getByText('Vitalik B.')).toBeInTheDocument();

    const signOutBtn = screen.getByRole('button', { name: 'Sign Out' });
    expect(signOutBtn).toBeInTheDocument();

    fireEvent.click(signOutBtn);
    expect(mockLogout).toHaveBeenCalledTimes(1);
  });
});
