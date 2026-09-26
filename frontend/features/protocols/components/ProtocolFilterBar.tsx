'use client';
import { useRouter, useSearchParams, usePathname } from 'next/navigation';
import { useCallback } from 'react';
import { cn } from '@/lib/cn';

const SORT_OPTIONS = [
  { label: 'Latest',         value: 'latest' },
  { label: 'Top Voted',      value: 'top' },
  { label: 'Highest Rated',  value: 'rating' },
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
  { label: 'All Status',  value: '' },
  { label: 'Published',   value: 'published' },
  { label: 'Draft',       value: 'draft' },
  { label: 'Deprecated',  value: 'deprecated' },
];

export function ProtocolFilterBar() {
  const router      = useRouter();
  const pathname    = usePathname();
  const searchParams = useSearchParams();

  const updateParam = useCallback(
    (key: string, value: string) => {
      const params = new URLSearchParams(searchParams.toString());
      if (value) {
        params.set(key, value);
      } else {
        params.delete(key);
      }
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
    <div className='flex flex-wrap gap-2 items-center'>
      {/* Sort pill group */}
      <div
        className='flex items-center gap-0.5 rounded-lg p-0.5'
        style={{ background: 'var(--surface-muted)', border: '1px solid var(--border)' }}
      >
        {SORT_OPTIONS.map((opt) => (
          <button
            key={opt.value}
            onClick={() => updateParam('sort', opt.value)}
            className={cn(
              'rounded-md px-3 py-1.5 text-xs font-medium transition-all cursor-pointer',
              current.sort === opt.value
                ? 'shadow-sm text-white'
                : 'hover:bg-white',
            )}
            style={
              current.sort === opt.value
                ? { background: 'var(--brand)', color: '#ffffff' }
                : { color: 'var(--text-secondary)' }
            }
          >
            {opt.label}
          </button>
        ))}
      </div>

      {/* Category select */}
      <select
        value={current.category}
        onChange={(e) => updateParam('category', e.target.value)}
        className='rounded-lg border px-3 py-2 text-xs font-medium focus:outline-none transition-colors'
        style={{
          background: 'var(--surface-card)',
          borderColor: 'var(--border)',
          color: 'var(--text-secondary)',
        }}
        aria-label='Filter by category'
      >
        {CATEGORY_OPTIONS.map((opt) => (
          <option key={opt.value} value={opt.value}>{opt.label}</option>
        ))}
      </select>

      {/* Status select */}
      <select
        value={current.status}
        onChange={(e) => updateParam('status', e.target.value)}
        className='rounded-lg border px-3 py-2 text-xs font-medium focus:outline-none transition-colors'
        style={{
          background: 'var(--surface-card)',
          borderColor: 'var(--border)',
          color: 'var(--text-secondary)',
        }}
        aria-label='Filter by status'
      >
        {STATUS_OPTIONS.map((opt) => (
          <option key={opt.value} value={opt.value}>{opt.label}</option>
        ))}
      </select>
    </div>
  );
}
