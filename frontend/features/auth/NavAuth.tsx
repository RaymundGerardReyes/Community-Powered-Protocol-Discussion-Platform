'use client';

import React, { useState, useRef, useEffect } from 'react';
import { useAuth, DEMO_USERS, type DemoUser } from './AuthContext';
import { Button } from '@/components/ui/Button';

function getInitials(name: string): string {
  return name
    .split(' ')
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function getAvatarHue(name: string): number {
  return [...name].reduce((acc, c) => acc + c.charCodeAt(0), 0) % 360;
}

export function NavAuth() {
  const { user, isLoading, login, logout, isAuthModalOpen, openAuthModal, closeAuthModal } = useAuth();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [customEmail, setCustomEmail] = useState('');
  const [customPassword, setCustomPassword] = useState('');
  const [loginError, setLoginError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setIsMenuOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  async function handleDemoSelect(demo: DemoUser) {
    setSubmitting(true);
    setLoginError(null);
    try {
      await login(demo.email, 'password');
      setIsMenuOpen(false);
      closeAuthModal();
    } catch (err: unknown) {
      const apiErr = err as { errors?: Record<string, string[]>; message?: string };
      const fieldError = apiErr?.errors ? Object.values(apiErr.errors).flat()[0] : null;
      setLoginError(fieldError ?? apiErr?.message ?? 'Failed to log in');
    } finally {
      setSubmitting(false);
    }
  }

  async function handleCustomSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!customEmail) return;
    setSubmitting(true);
    setLoginError(null);
    try {
      await login(customEmail, customPassword || 'password');
      setIsMenuOpen(false);
      closeAuthModal();
    } catch (err: unknown) {
      const apiErr = err as { errors?: Record<string, string[]>; message?: string };
      const fieldError = apiErr?.errors ? Object.values(apiErr.errors).flat()[0] : null;
      setLoginError(fieldError ?? apiErr?.message ?? 'Invalid credentials');
    } finally {
      setSubmitting(false);
    }
  }

  if (isLoading) {
    return (
      <div className="h-8 w-20 animate-pulse rounded-md" style={{ background: 'var(--surface-muted)' }} />
    );
  }

  if (user) {
    const hue = getAvatarHue(user.name);
    return (
      <div className="flex items-center gap-3">
        <div className="flex items-center gap-2">
          <span
            className="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold text-white shadow-sm"
            style={{ background: `hsl(${hue}, 65%, 45%)` }}
            aria-hidden
          >
            {getInitials(user.name)}
          </span>
          <span className="hidden sm:inline-block text-xs font-semibold" style={{ color: 'var(--text-primary)' }}>
            {user.name}
          </span>
        </div>
        <button
          type="button"
          onClick={() => logout()}
          className="btn btn-ghost btn-sm text-xs"
          style={{ color: 'var(--text-muted)' }}
        >
          Sign Out
        </button>
      </div>
    );
  }

  return (
    <div className="relative" ref={menuRef}>
      <button
        type="button"
        onClick={() => setIsMenuOpen((prev) => !prev)}
        className="btn btn-primary btn-sm text-xs font-medium shadow-sm flex items-center gap-1.5"
      >
        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <span>Sign In</span>
      </button>

      {/* Popover Dropdown */}
      {(isMenuOpen || isAuthModalOpen) && (
        <div
          className="absolute right-0 mt-2 w-80 rounded-xl p-4 shadow-xl z-50 animate-in fade-in zoom-in-95 duration-100"
          style={{
            background: 'var(--surface-card)',
            border: '1px solid var(--border)',
            boxShadow: 'var(--shadow-lg)',
          }}
        >
          <div className="flex items-center justify-between pb-3" style={{ borderBottom: '1px solid var(--border)' }}>
            <div>
              <h4 className="text-xs font-bold" style={{ color: 'var(--text-primary)' }}>
                Sign In to Protocol Hub
              </h4>
              <p className="text-[0.7rem]" style={{ color: 'var(--text-muted)' }}>
                Select a demo contributor or enter credentials
              </p>
            </div>
            {isAuthModalOpen && (
              <button
                type="button"
                onClick={closeAuthModal}
                className="text-xs font-bold p-1 rounded hover:bg-slate-100"
                style={{ color: 'var(--text-muted)' }}
              >
                ✕
              </button>
            )}
          </div>

          {loginError && (
            <div
              className="mt-3 rounded-md p-2 text-[0.75rem]"
              style={{
                background: 'var(--danger-bg)',
                border: '1px solid #fecaca',
                color: 'var(--danger-text)',
              }}
            >
              {loginError}
            </div>
          )}

          {/* Quick Demo Switcher */}
          <div className="mt-3">
            <span className="block text-[0.68rem] font-semibold uppercase tracking-wider mb-2" style={{ color: 'var(--text-muted)' }}>
              1-Click Demo Accounts
            </span>
            <div className="space-y-1.5">
              {DEMO_USERS.map((demo) => {
                const hue = getAvatarHue(demo.name);
                return (
                  <button
                    key={demo.email}
                    type="button"
                    disabled={submitting}
                    onClick={() => handleDemoSelect(demo)}
                    className="w-full flex items-center justify-between p-2 rounded-lg text-left text-xs transition-colors hover:bg-slate-50 disabled:opacity-50"
                    style={{ border: '1px solid var(--border)' }}
                  >
                    <div className="flex items-center gap-2 min-w-0">
                      <span
                        className="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[0.65rem] font-bold text-white"
                        style={{ background: `hsl(${hue}, 65%, 45%)` }}
                      >
                        {getInitials(demo.name)}
                      </span>
                      <div className="truncate">
                        <div className="font-semibold" style={{ color: 'var(--text-primary)' }}>
                          {demo.name}
                        </div>
                        <div className="text-[0.65rem]" style={{ color: 'var(--text-muted)' }}>
                          {demo.role}
                        </div>
                      </div>
                    </div>
                    <span className="text-[0.7rem] font-medium" style={{ color: 'var(--brand)' }}>
                      Log In →
                    </span>
                  </button>
                );
              })}
            </div>
          </div>

          <div className="divider my-3" />

          {/* Custom Credentials Form */}
          <form onSubmit={handleCustomSubmit} className="space-y-2">
            <div>
              <input
                type="email"
                placeholder="Email address"
                value={customEmail}
                onChange={(e) => setCustomEmail(e.target.value)}
                className="input text-xs py-1.5"
                required
              />
            </div>
            <div>
              <input
                type="password"
                placeholder="Password (default: password)"
                value={customPassword}
                onChange={(e) => setCustomPassword(e.target.value)}
                className="input text-xs py-1.5"
              />
            </div>
            <Button
              type="submit"
              variant="secondary"
              size="sm"
              className="w-full text-xs font-semibold"
              disabled={submitting || !customEmail}
            >
              {submitting ? 'Authenticating…' : 'Sign In with Email'}
            </Button>
          </form>
        </div>
      )}
    </div>
  );
}
