'use client';
import { useRouter, useSearchParams, usePathname } from 'next/navigation';
import { useCallback } from 'react';
import { cn } from '@/lib/cn';

const SORT_OPTIONS = [
  { label: 'Latest',  value: 'latest' },
  { label: 'Top',     value: 'top'    },
  { label: 'Rating',  value: 'rating' },
];

const CATEGORY_OPTIONS = [
  { label: 'All',            value: '' },
  { label: 'DeFi',           value: 'defi' },
  { label: 'Layer 2',        value: 'layer2' },
  { label: 'NFT',            value: 'nft' },
  { label: 'DAO',            value: 'dao' },
  { label: 'Privacy',        value: 'privacy' },
  { label: 'Infrastructure', value: 'infrastructure' },
];

const STATUS_OPTIONS = [
  { label: 'All',        value: '' },
  { label: 'Published',  value: 'published' },
  { label: 'Draft',      value: 'draft' },
  { label: 'Deprecated', value: 'deprecated' },
];

export function ProtocolFilterBar() {
  const router     = useRouter();
  const pathname   = usePathname();
  const searchParams = useSearchParams();

  const updateParam = useCallback(
    (key: string, value: string) => {
      const params = new URLSearchParams(searchParams.toString());
      value ? params.set(key, value) : params.delete(key);
      params.delete('page');
      router.push(`${pathname}?${params.toString()}`);
    },
    [router, pathname, searchParams],
  );

  const current = {
    sort:     searchParams.get('sort')     ?? 'latest',
    category: searchParams.get('category') ?? '',
    status:   searchParams.get('status')   ?? '',
  };

  return (
    <div className="flex flex-wrap gap-2 items-center">
      {/* Sort pills */}
      <div
        className="flex items-center rounded-lg p-0.5 gap-0.5"
        style={{ background: 'var(--surface-card)', border: '1px solid var(--surface-overlay)' }}
        role="group"
        aria-label="Sort by"
      >
        {SORT_OPTIONS.map((opt) => {
          const active = current.sort === opt.value;
          return (
            <button
              key={opt.value}
              onClick={() => updateParam('sort', opt.value)}
              aria-pressed={active}
              className={cn(
                'rounded-md px-3 py-1.5 text-xs font-medium transition-all',
                active
                  ? 'text-white'
                  : 'hover:text-[var(--text-primary)]',
              )}
              style={active
                ? { background: 'var(--brand)', boxShadow: '0 2px 6px rgba(99,102,241,0.4)' }
                : { color: 'var(--text-secondary)' }
              }
            >
              {opt.label}
            </button>
          );
        })}
      </div>

      {/* Category dropdown */}
      <div className="relative">
        <select
          value={current.category}
          onChange={(e) => updateParam('category', e.target.value)}
          aria-label="Filter by category"
          className="input h-9 pr-8 text-xs appearance-none cursor-pointer"
          style={{ paddingRight: '2rem' }}
        >
          {CATEGORY_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>{opt.label}</option>
          ))}
        </select>
        <svg
          className="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 h-3 w-3"
          style={{ color: 'var(--text-muted)' }}
          viewBox="0 0 20 20" fill="currentColor" aria-hidden
        >
          <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd"/>
        </svg>
      </div>

      {/* Status dropdown */}
      <div className="relative">
        <select
          value={current.status}
          onChange={(e) => updateParam('status', e.target.value)}
          aria-label="Filter by status"
          className="input h-9 pr-8 text-xs appearance-none cursor-pointer"
          style={{ paddingRight: '2rem' }}
        >
          {STATUS_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>{opt.label}</option>
          ))}
        </select>
        <svg
          className="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 h-3 w-3"
          style={{ color: 'var(--text-muted)' }}
          viewBox="0 0 20 20" fill="currentColor" aria-hidden
        >
          <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd"/>
        </svg>
      </div>
    </div>
  );
}
