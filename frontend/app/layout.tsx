import type { Metadata } from 'next';
import { Geist } from 'next/font/google';
import './globals.css';
import { Providers } from '@/lib/query-client';
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
    'Discover, discuss, and vote on Web3 protocol standards. A community-powered platform for protocol governance.',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang='en' className={`${geist.variable} h-full antialiased`}>
      <body className='min-h-full flex flex-col bg-slate-50'>
        <Providers>
          <header className='sticky top-0 z-10 border-b border-slate-200 bg-white/80 backdrop-blur-sm'>
            <div className='mx-auto flex max-w-6xl items-center justify-between px-4 py-3'>
              <Link
                href='/'
                className='font-semibold text-slate-900 hover:text-indigo-600 transition-colors'
              >
                Protocol Hub
              </Link>
              <nav className='flex items-center gap-6 text-sm text-slate-600'>
                <Link href='/protocols' className='hover:text-indigo-600 transition-colors'>
                  Protocols
                </Link>
              </nav>
            </div>
          </header>
          <main className='flex-1'>{children}</main>
          <footer className='border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-400'>
            Community-Powered Protocol Discussion Platform
          </footer>
        </Providers>
      </body>
    </html>
  );
}
