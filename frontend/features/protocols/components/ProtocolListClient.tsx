'use client';
import { useMemo } from 'react';
import { useSearchParams } from 'next/navigation';
import { useProtocols } from '../hooks/useProtocols';
import { ProtocolCard } from './ProtocolCard';
import { Spinner } from '@/components/ui/Spinner';
import type { ProtocolFilters } from '../api';

export function ProtocolListClient({ initialParams }: { initialParams: Record<string, string> }) {
  const searchParams = useSearchParams();

  const cleanFilters = useMemo(() => {
    const filters: ProtocolFilters = {
      sort:     (searchParams.get('sort')     ?? initialParams.sort     ?? 'latest') as ProtocolFilters['sort'],
      category:  searchParams.get('category') ?? initialParams.category ?? '',
      status:    searchParams.get('status')   ?? initialParams.status   ?? '',
      search:    searchParams.get('search')   ?? initialParams.search   ?? '',
      page:      searchParams.get('page')     ?? initialParams.page     ?? '1',
    };
    return Object.fromEntries(
      Object.entries(filters).filter(([, v]) => v !== '' && v !== undefined)
    ) as ProtocolFilters;
  }, [searchParams, initialParams]);

  const { data, isLoading, isError, error } = useProtocols(cleanFilters);

  if (isLoading) {
    return <div className='flex justify-center py-20'><Spinner className='h-8 w-8' /></div>;
  }

  if (isError) {
    return (
      <div
        className='rounded-xl p-5 text-sm'
        style={{
          background: 'var(--danger-bg)',
          border: '1px solid #fecaca',
          color: 'var(--danger-text)',
        }}
      >
        {(error as { message?: string })?.message ?? 'Failed to load protocols.'}
      </div>
    );
  }

  const protocols = data?.data ?? [];
  const meta      = data?.meta;

  if (!protocols.length) {
    return (
      <div
        className='rounded-xl p-12 text-center text-sm italic'
        style={{
          background: 'var(--surface-card)',
          border: '1px solid var(--border)',
          color: 'var(--text-muted)',
        }}
      >
        No protocols found matching your filters.
      </div>
    );
  }

  return (
    <div>
      {/* Result count */}
      {meta?.total ? (
        <p className='mb-4 text-xs' style={{ color: 'var(--text-muted)' }}>
          {meta.total} protocol{meta.total !== 1 ? 's' : ''}
        </p>
      ) : null}

      {/* Grid */}
      <ul className='grid gap-4 sm:grid-cols-2 lg:grid-cols-3'>
        {protocols.map((protocol) => (
          <li key={protocol.id}>
            <ProtocolCard protocol={protocol} />
          </li>
        ))}
      </ul>

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <nav
          className='mt-8 flex justify-center gap-1.5 text-sm'
          aria-label='Pagination'
        >
          {Array.from({ length: meta.last_page }, (_, i) => i + 1).map((page) => {
            const isActive = page === meta.current_page;
            return (
              <a
                key={page}
                href={`?${new URLSearchParams({ ...cleanFilters, page: String(page) }).toString()}`}
                className='inline-flex h-9 w-9 items-center justify-center rounded-lg text-sm font-medium transition-all'
                style={
                  isActive
                    ? { background: 'var(--brand)', color: '#fff', border: '1px solid var(--brand)' }
                    : {
                        background: 'var(--surface-card)',
                        color: 'var(--text-secondary)',
                        border: '1px solid var(--border)',
                      }
                }
                aria-current={isActive ? 'page' : undefined}
              >
                {page}
              </a>
            );
          })}
        </nav>
      )}
    </div>
  );
}
