import type { Metadata } from 'next';
import { Geist } from 'next/font/google';
import './globals.css';
import { Providers } from '@/lib/query-client';
import { AuthProvider } from '@/features/auth/AuthContext';
import { NavAuth } from '@/features/auth/NavAuth';
import Link from 'next/link';

const geist = Geist({
  variable: '--font-geist-sans',
  subsets: ['latin'],
});

export const metadata: Metadata = {
  title: {
    template: '%s | Protocol Hub',
    default: 'Protocol Hub — Community-Powered Protocol Discussion',
  },
  description:
    'Discover, discuss, and vote on structured healing, wellness, and instructional protocols. A community-powered platform for evidence-based protocols.',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang='en' className={`${geist.variable} h-full antialiased`} data-scroll-behavior='smooth'>
      <body className='min-h-full flex flex-col' style={{ background: 'var(--background)' }}>
        <Providers>
          <AuthProvider>
            {/* ── Header ──────────────────────────────────────────────────── */}
            <header
              className='sticky top-0 z-30 backdrop-blur-md'
              style={{
                background: 'rgba(255,255,255,0.85)',
                borderBottom: '1px solid var(--border)',
                boxShadow: '0 1px 3px 0 rgb(0 0 0 / 0.06)',
              }}
            >
              <div className='mx-auto flex max-w-6xl items-center justify-between px-4 py-3 gap-4'>
                {/* Logo */}
                <Link
                  href='/protocols'
                  className='flex items-center gap-2 font-bold text-base tracking-tight transition-colors'
                  style={{ color: 'var(--text-primary)' }}
                >
                  <span
                    className='inline-flex h-7 w-7 items-center justify-center rounded-lg text-white text-xs font-bold'
                    style={{ background: 'var(--brand)' }}
                  >
                    P
                  </span>
                  Protocol Hub
                </Link>

                {/* Nav & Auth */}
                <div className='flex items-center gap-3'>
                  <nav className='flex items-center gap-1' aria-label='Primary navigation'>
                    <Link
                      href='/protocols'
                      className='rounded-md px-3 py-1.5 text-sm font-medium transition-colors hover:bg-indigo-50 hover:text-indigo-600'
                      style={{ color: 'var(--text-secondary)' }}
                    >
                      Protocols
                    </Link>
                  </nav>

                  <div className='h-4 w-px' style={{ background: 'var(--border)' }} />

                  <NavAuth />
                </div>
              </div>
            </header>

          {/* ── Main ────────────────────────────────────────────────────── */}
          <main className='flex-1'>{children}</main>

          {/* ── Footer ──────────────────────────────────────────────────── */}
          <footer
            className='py-8 text-center text-xs'
            style={{
              borderTop: '1px solid var(--border)',
              background: 'var(--surface-card)',
              color: 'var(--text-muted)',
            }}
          >
            <div className='mx-auto max-w-6xl px-4 flex flex-col sm:flex-row items-center justify-between gap-2'>
              <span className='font-medium' style={{ color: 'var(--text-secondary)' }}>
                Protocol Hub
              </span>
              <span>Community-Powered Protocol Discussion Platform</span>
            </div>
          </footer>
        </AuthProvider>
      </Providers>
    </body>
  </html>
);
}
