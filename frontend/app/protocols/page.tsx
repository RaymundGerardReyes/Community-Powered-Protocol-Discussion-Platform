import { Suspense } from 'react';
import type { Metadata } from 'next';
import { ProtocolFilterBar } from '@/features/protocols/components/ProtocolFilterBar';
import { SearchBar } from '@/features/protocols/components/SearchBar';
import { ProtocolListClient } from '@/features/protocols/components/ProtocolListClient';
import { Spinner } from '@/components/ui/Spinner';

export const metadata: Metadata = {
  title: 'Protocols',
  description: 'Browse and discover community-powered Web3 protocol standards.',
};

interface PageProps {
  searchParams: Promise<Record<string, string>>;
}

export default async function ProtocolsPage({ searchParams }: PageProps) {
  const params = await searchParams;
  return (
    <div className='mx-auto max-w-6xl px-4 py-8'>
      <div className='mb-6'>
        <h1 className='text-2xl font-bold text-slate-900'>Protocols</h1>
        <p className='mt-1 text-sm text-slate-500'>
          Discover and discuss community-powered protocol standards.
        </p>
      </div>

      <div className='mb-5 flex flex-col gap-3 sm:flex-row sm:items-center'>
        <Suspense>
          <SearchBar />
        </Suspense>
        <Suspense>
          <ProtocolFilterBar />
        </Suspense>
      </div>

      <Suspense fallback={<div className='flex justify-center py-20'><Spinner className='h-8 w-8' /></div>}>
        <ProtocolListClient initialParams={params} />
      </Suspense>
    </div>
  );
}
