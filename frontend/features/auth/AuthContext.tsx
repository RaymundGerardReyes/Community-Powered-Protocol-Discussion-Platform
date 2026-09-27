'use client';

import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { apiClient, type ApiError } from '@/lib/api-client';
import type { User } from '@/types';

export interface DemoUser {
  name: string;
  email: string;
  role: string;
}

export const DEMO_USERS: DemoUser[] = [
  { name: 'Vitalik B.', email: 'vitalik@protocol.io', role: 'Core Contributor' },
  { name: 'Alice Cryptographer', email: 'alice@protocol.io', role: 'Cryptographer' },
  { name: 'Bob Auditor', email: 'bob@protocol.io', role: 'Security Auditor' },
  { name: 'Charlie DeFi', email: 'charlie@protocol.io', role: 'Protocol Researcher' },
  { name: 'Protocol Admin', email: 'admin@protocol.io', role: 'Platform Admin' },
];

interface AuthContextType {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  login: (email: string, password?: string) => Promise<void>;
  logout: () => Promise<void>;
  openAuthModal: () => void;
  closeAuthModal: () => void;
  isAuthModalOpen: boolean;
}

const AuthContext = createContext<AuthContextType | null>(null);

let inFlightMePromise: Promise<{ data: { user: User } }> | null = null;

function fetchMeDeduplicated(): Promise<{ data: { user: User } }> {
  if (!inFlightMePromise) {
    inFlightMePromise = apiClient.get<{ user: User }>('/api/v1/auth/me').finally(() => {
      inFlightMePromise = null;
    });
  }
  return inFlightMePromise;
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);

  // Initialize from localStorage with in-flight deduplication
  useEffect(() => {
    let isSubscribed = true;
    const savedToken = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;
    if (!savedToken) {
      setIsLoading(false);
      return;
    }

    setToken(savedToken);
    fetchMeDeduplicated()
      .then((res) => {
        if (isSubscribed) {
          setUser(res.data.user);
        }
      })
      .catch(() => {
        if (isSubscribed) {
          localStorage.removeItem('auth_token');
          setToken(null);
          setUser(null);
        }
      })
      .finally(() => {
        if (isSubscribed) {
          setIsLoading(false);
        }
      });

    return () => {
      isSubscribed = false;
    };
  }, []);

  const login = useCallback(async (email: string, password = 'password') => {
    setIsLoading(true);
    try {
      const res = await apiClient.post<{ user: User; access_token: string }>(
        '/api/v1/auth/login',
        { email, password },
      );
      const { user: authedUser, access_token } = res.data;
      localStorage.setItem('auth_token', access_token);
      setToken(access_token);
      setUser(authedUser);
      setIsAuthModalOpen(false);
    } finally {
      setIsLoading(false);
    }
  }, []);

  const logout = useCallback(async () => {
    try {
      await apiClient.post('/api/v1/auth/logout');
    } catch {
      // Ignore logout failure if token expired
    } finally {
      localStorage.removeItem('auth_token');
      setToken(null);
      setUser(null);
    }
  }, []);

  const openAuthModal = useCallback(() => setIsAuthModalOpen(true), []);
  const closeAuthModal = useCallback(() => setIsAuthModalOpen(false), []);

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isLoading,
        login,
        logout,
        openAuthModal,
        closeAuthModal,
        isAuthModalOpen,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return ctx;
}
