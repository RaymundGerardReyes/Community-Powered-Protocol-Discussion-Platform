'use client';
import { useSearchParams } from 'next/navigation';
import { useProtocols } from '../hooks/useProtocols';
import { ProtocolCard } from './ProtocolCard';
import { Spinner } from '@/components/ui/Spinner';
import type { ProtocolFilters } from '../api';

export function ProtocolListClient({ initialParams }: { initialParams: Record<string, string> }) {
  const searchParams = useSearchParams();

  const filters: ProtocolFilters = {
    sort: (searchParams.get('sort') ?? initialParams.sort ?? 'latest') as ProtocolFilters['sort'],
    category: searchParams.get('category') ?? initialParams.category ?? '',
    status: searchParams.get('status') ?? initialParams.status ?? '',
    search: searchParams.get('search') ?? initialParams.search ?? '',
    page: searchParams.get('page') ?? initialParams.page ?? '1',
  };

  // Remove empty strings so API doesn't receive ?status=
  const cleanFilters = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== '' && v !== undefined)
  ) as ProtocolFilters;

  const { data, isLoading, isError, error } = useProtocols(cleanFilters);

  if (isLoading) {
    return <div className='flex justify-center py-20'><Spinner className='h-8 w-8' /></div>;
  }

  if (isError) {
    return (
      <div className='rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700'>
        {(error as { message?: string })?.message ?? 'Failed to load protocols.'}
      </div>
    );
  }

  const protocols = data?.data ?? [];
  const meta = data?.meta;

  if (!protocols.length) {
    return <p className='py-12 text-center text-slate-500'>No protocols found matching your filters.</p>;
  }

  return (
    <div>
      <div className='mb-3 text-sm text-slate-500'>
        {meta?.total ? `${meta.total} protocols` : ''}
      </div>
      <ul className='grid gap-4 sm:grid-cols-2 lg:grid-cols-3'>
        {protocols.map((protocol) => (
          <li key={protocol.id}>
            <ProtocolCard protocol={protocol} />
          </li>
        ))}
      </ul>
      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className='mt-8 flex justify-center gap-2 text-sm'>
          {Array.from({ length: meta.last_page }, (_, i) => i + 1).map((page) => (
            <a
              key={page}
              href={`?${new URLSearchParams({ ...cleanFilters, page: String(page) }).toString()}`}
              className={`inline-flex h-9 w-9 items-center justify-center rounded-lg border text-sm font-medium transition-colors ${
                page === meta.current_page
                  ? 'border-indigo-600 bg-indigo-600 text-white'
                  : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
              }`}
            >
              {page}
            </a>
          ))}
        </div>
      )}
    </div>
  );
}
