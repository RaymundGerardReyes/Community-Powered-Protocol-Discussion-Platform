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
  { name: 'Dr. Andrew H.', email: 'andrew.h@wellness.io', role: 'Neurobiology & Sleep' },
  { name: 'Dr. Rhonda P.', email: 'rhonda.p@wellness.io', role: 'Biomedical Science' },
  { name: 'Elena Rostova, PT', email: 'elena.r@wellness.io', role: 'Physical Therapy & Rehab' },
  { name: 'Coach Marcus Vance', email: 'marcus.v@wellness.io', role: 'Physiology & Recovery' },
  { name: 'Vitalik B.', email: 'vitalik@protocol.io', role: 'Research Fellow' },
  { name: 'Alice Cryptographer', email: 'alice@protocol.io', role: 'Clinical Data Reviewer' },
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

  // Initialize from localStorage with in-flight deduplication and sessionStorage caching
  useEffect(() => {
    let isSubscribed = true;
    const savedToken = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;
    const savedUserStr = typeof window !== 'undefined' ? sessionStorage.getItem('auth_user') : null;

    if (!savedToken) {
      setIsLoading(false);
      return;
    }

    setToken(savedToken);

    if (savedUserStr) {
      try {
        const cachedUser = JSON.parse(savedUserStr);
        if (cachedUser && cachedUser.id) {
          setUser(cachedUser);
          setIsLoading(false);
        }
      } catch {
        sessionStorage.removeItem('auth_user');
      }
    }

    fetchMeDeduplicated()
      .then((res) => {
        if (isSubscribed) {
          setUser(res.data.user);
          try {
            sessionStorage.setItem('auth_user', JSON.stringify(res.data.user));
          } catch {
            // Ignore storage errors
          }
        }
      })
      .catch(() => {
        if (isSubscribed) {
          localStorage.removeItem('auth_token');
          sessionStorage.removeItem('auth_user');
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
      try {
        sessionStorage.setItem('auth_user', JSON.stringify(authedUser));
      } catch {
        // Ignore storage errors
      }
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
      sessionStorage.removeItem('auth_user');
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
