'use client';
import { useSearchParams } from 'next/navigation';
import { useProtocols } from '../hooks/useProtocols';
import { ProtocolCard } from './ProtocolCard';
import type { ProtocolFilters } from '../api';

function SkeletonCard() {
  return (
    <div className="card-flat p-5 space-y-3" aria-hidden>
      <div className="flex justify-between">
        <div className="skeleton h-5 w-20 rounded" />
        <div className="skeleton h-5 w-16 rounded-full" />
      </div>
      <div className="skeleton h-4 w-3/4 rounded" />
      <div className="skeleton h-4 w-full rounded" />
      <div className="skeleton h-4 w-1/2 rounded" />
      <div style={{ height: '1px', background: 'var(--surface-overlay)' }} className="my-2" />
      <div className="flex justify-between items-center">
        <div className="skeleton h-4 w-24 rounded" />
        <div className="skeleton h-4 w-16 rounded" />
      </div>
    </div>
  );
}

function PaginationBar({
  currentPage, lastPage, buildHref,
}: {
  currentPage: number;
  lastPage: number;
  buildHref: (page: number) => string;
}) {
  if (lastPage <= 1) return null;

  const pages: (number | '…')[] = [];
  if (lastPage <= 7) {
    for (let i = 1; i <= lastPage; i++) pages.push(i);
  } else {
    pages.push(1);
    if (currentPage > 3) pages.push('…');
    for (let i = Math.max(2, currentPage - 1); i <= Math.min(lastPage - 1, currentPage + 1); i++) pages.push(i);
    if (currentPage < lastPage - 2) pages.push('…');
    pages.push(lastPage);
  }

  return (
    <nav className="flex justify-center gap-1.5 mt-10" aria-label="Pagination">
      {pages.map((p, i) =>
        p === '…' ? (
          <span key={`ellipsis-${i}`} className="flex h-9 w-9 items-center justify-center text-sm" style={{ color: 'var(--text-muted)' }}>…</span>
        ) : (
          <a
            key={p}
            href={buildHref(p)}
            aria-current={p === currentPage ? 'page' : undefined}
            className="flex h-9 w-9 items-center justify-center rounded-lg text-sm font-medium transition-all"
            style={
              p === currentPage
                ? { background: 'var(--brand)', color: '#fff', boxShadow: '0 2px 8px rgba(99,102,241,0.4)' }
                : { background: 'var(--surface-card)', border: '1px solid var(--surface-overlay)', color: 'var(--text-secondary)' }
            }
          >
            {p}
          </a>
        ),
      )}
    </nav>
  );
}

export function ProtocolListClient({ initialParams }: { initialParams: Record<string, string> }) {
  const searchParams = useSearchParams();

  const get = (key: string) => searchParams.get(key) ?? initialParams[key] ?? '';

  const filters: ProtocolFilters = {
    sort:     (get('sort') || 'latest') as ProtocolFilters['sort'],
    category: get('category') || undefined,
    status:   get('status')   || undefined,
    search:   get('search')   || undefined,
    page:     get('page')     || undefined,
  };

  const { data, isLoading, isError, isFetching } = useProtocols(filters);

  // Build href for pagination keeping existing params
  function buildHref(page: number) {
    const params = new URLSearchParams(searchParams.toString());
    params.set('page', String(page));
    return `?${params.toString()}`;
  }

  /* ── Loading skeleton ─────────────────────────────────────────────────── */
  if (isLoading) {
    return (
      <div aria-busy="true" aria-label="Loading protocols">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, i) => <SkeletonCard key={i} />)}
        </div>
      </div>
    );
  }

  /* ── Error ───────────────────────────────────────────────────────────── */
  if (isError) {
    return (
      <div
        className="rounded-xl p-5 text-sm"
        style={{ background: 'rgba(239,68,68,0.08)', border: '1px solid rgba(239,68,68,0.2)', color: '#f87171' }}
        role="alert"
      >
        Failed to load protocols. Please try again.
      </div>
    );
  }

  const protocols = data?.data ?? [];
  const meta      = data?.meta;

  /* ── Empty ────────────────────────────────────────────────────────────── */
  if (!protocols.length) {
    return (
      <div className="flex flex-col items-center gap-3 py-16" style={{ color: 'var(--text-muted)' }}>
        <svg className="h-12 w-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p className="text-sm">No protocols found matching your filters.</p>
      </div>
    );
  }

  return (
    <div>
      {/* Result count + stale indicator */}
      {meta && (
        <p className="mb-4 text-xs" style={{ color: 'var(--text-muted)' }}>
          {meta.total.toLocaleString()} protocol{meta.total !== 1 ? 's' : ''}
          {isFetching && !isLoading && (
            <span className="ml-2 inline-block animate-pulse" style={{ color: 'var(--brand)' }}>
              Updating…
            </span>
          )}
        </p>
      )}

      {/* Grid */}
      <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
        {protocols.map((protocol, i) => (
          <li
            key={protocol.id}
            className="animate-fade-up"
            style={{ animationDelay: `${i * 40}ms` }}
          >
            <ProtocolCard protocol={protocol} />
          </li>
        ))}
      </ul>

      {/* Pagination */}
      {meta && (
        <PaginationBar
          currentPage={meta.current_page}
          lastPage={meta.last_page}
          buildHref={buildHref}
        />
      )}
    </div>
  );
}
