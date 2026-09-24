import type { Metadata } from 'next';
import { Geist, Geist_Mono } from 'next/font/google';
import './globals.css';
import { Providers } from '@/lib/query-client';
import Link from 'next/link';
import { NavSearch } from '@/features/protocols/components/NavSearch';

const geistSans = Geist({
  variable: '--font-geist-sans',
  subsets: ['latin'],
  display: 'swap',
});

const geistMono = Geist_Mono({
  variable: '--font-geist-mono',
  subsets: ['latin'],
  display: 'swap',
});

export const metadata: Metadata = {
  title: {
    template: '%s | Protocol Hub',
    default: 'Protocol Hub — Community-Powered Protocol Discussion',
  },
  description:
    'Discover, discuss, and vote on Web3 protocol standards. A community-powered platform for protocol governance.',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html
      lang="en"
      className={`${geistSans.variable} ${geistMono.variable} h-full`}
    >
      <body className="min-h-full flex flex-col" style={{ background: 'var(--surface-base)', color: 'var(--text-primary)' }}>
        <Providers>
          {/* ── Sticky glassmorphism header ─────────────────────────────── */}
          <header className="glass sticky top-0 z-50">
            <div className="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
              {/* Logo */}
              <Link
                href="/protocols"
                className="flex items-center gap-2 shrink-0 group"
                aria-label="Protocol Hub home"
              >
                <span
                  className="flex h-7 w-7 items-center justify-center rounded-lg text-white text-xs font-bold"
                  style={{ background: 'var(--brand)', boxShadow: '0 2px 8px rgba(99,102,241,0.5)' }}
                  aria-hidden
                >
                  PH
                </span>
                <span className="font-semibold text-sm hidden sm:block" style={{ color: 'var(--text-primary)' }}>
                  Protocol Hub
                </span>
              </Link>

              {/* Inline search — grows to fill space */}
              <div className="flex-1 max-w-md">
                <NavSearch />
              </div>

              {/* Right nav */}
              <nav className="flex items-center gap-1 shrink-0">
                <Link
                  href="/protocols"
                  className="btn btn-ghost btn-sm rounded-lg px-3 text-sm"
                  style={{ color: 'var(--text-secondary)' }}
                >
                  Protocols
                </Link>
                <button
                  className="btn btn-primary btn-sm"
                  aria-label="Sign in"
                >
                  Sign In
                </button>
              </nav>
            </div>
          </header>

          {/* ── Page content ────────────────────────────────────────────── */}
          <main className="flex-1 animate-fade-up">{children}</main>

          {/* ── Footer ──────────────────────────────────────────────────── */}
          <footer
            className="text-center py-6 text-xs"
            style={{ borderTop: '1px solid var(--surface-overlay)', color: 'var(--text-muted)' }}
          >
            Community-Powered Protocol Discussion Platform
          </footer>
        </Providers>
      </body>
    </html>
  );
}
