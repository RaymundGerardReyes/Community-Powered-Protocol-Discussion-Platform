import { Suspense } from 'react';
import type { Metadata } from 'next';
import { ProtocolFilterBar } from '@/features/protocols/components/ProtocolFilterBar';
import { SearchBar } from '@/features/protocols/components/SearchBar';
import { ProtocolListClient } from '@/features/protocols/components/ProtocolListClient';
import { Spinner } from '@/components/ui/Spinner';

export const metadata: Metadata = {
  title: 'Protocols',
  description: 'Browse and discover community-powered healing, wellness, and instructional protocols.',
};

interface PageProps {
  searchParams: Promise<Record<string, string>>;
}

export default async function ProtocolsPage({ searchParams }: PageProps) {
  const params = await searchParams;
  return (
    <div className='mx-auto max-w-6xl px-4 py-10'>
      {/* Page hero */}
      <div className='mb-8'>
        <h1
          className='text-3xl font-bold tracking-tight'
          style={{ color: 'var(--text-primary)' }}
        >
          Protocols
        </h1>
        <p className='mt-2 text-sm' style={{ color: 'var(--text-secondary)' }}>
          Discover, discuss, and vote on community-powered healing, wellness, and instructional protocols.
        </p>
      </div>

      {/* Filters row */}
      <div
        className='mb-6 flex flex-col gap-3 rounded-xl p-4 sm:flex-row sm:items-center'
        style={{ background: 'var(--surface-card)', border: '1px solid var(--border)', boxShadow: 'var(--shadow-sm)' }}
      >
        <Suspense>
          <SearchBar />
        </Suspense>
        <Suspense>
          <ProtocolFilterBar />
        </Suspense>
      </div>

      {/* List */}
      <Suspense fallback={<div className='flex justify-center py-20'><Spinner className='h-8 w-8' /></div>}>
        <ProtocolListClient initialParams={params} />
      </Suspense>
    </div>
  );
}
