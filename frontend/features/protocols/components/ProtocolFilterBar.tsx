'use client';
import { useRouter, useSearchParams, usePathname } from 'next/navigation';
import { useCallback } from 'react';
import { cn } from '@/lib/cn';

const SORT_OPTIONS = [
  { label: 'Latest', value: 'latest' },
  { label: 'Top Voted', value: 'top' },
  { label: 'Highest Rated', value: 'rating' },
];

const CATEGORY_OPTIONS = [
  { label: 'All', value: '' },
  { label: 'DeFi', value: 'defi' },
  { label: 'Layer 2', value: 'layer2' },
  { label: 'NFT', value: 'nft' },
  { label: 'DAO', value: 'dao' },
  { label: 'Privacy', value: 'privacy' },
  { label: 'Infrastructure', value: 'infrastructure' },
];

const STATUS_OPTIONS = [
  { label: 'All', value: '' },
  { label: 'Published', value: 'published' },
  { label: 'Draft', value: 'draft' },
  { label: 'Deprecated', value: 'deprecated' },
];

export function ProtocolFilterBar() {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  const updateParam = useCallback(
    (key: string, value: string) => {
      const params = new URLSearchParams(searchParams.toString());
      if (value) {
        params.set(key, value);
      } else {
        params.delete(key);
      }
      params.delete('page'); // reset pagination on filter change
      router.push(`${pathname}?${params.toString()}`);
    },
    [router, pathname, searchParams],
  );

  const current = {
    sort: searchParams.get('sort') ?? 'latest',
    category: searchParams.get('category') ?? '',
    status: searchParams.get('status') ?? '',
  };

  return (
    <div className='flex flex-wrap gap-3 items-center'>
      {/* Sort */}
      <div className='flex items-center gap-1 rounded-lg border border-slate-200 p-1'>
        {SORT_OPTIONS.map((opt) => (
          <button
            key={opt.value}
            onClick={() => updateParam('sort', opt.value)}
            className={cn(
              'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
              current.sort === opt.value
                ? 'bg-indigo-600 text-white'
                : 'text-slate-600 hover:bg-slate-100',
            )}
          >
            {opt.label}
          </button>
        ))}
      </div>

      {/* Category */}
      <select
        value={current.category}
        onChange={(e) => updateParam('category', e.target.value)}
        className='rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500'
        aria-label='Filter by category'
      >
        {CATEGORY_OPTIONS.map((opt) => (
          <option key={opt.value} value={opt.value}>
            {opt.label}
          </option>
        ))}
      </select>

      {/* Status */}
      <select
        value={current.status}
        onChange={(e) => updateParam('status', e.target.value)}
        className='rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500'
        aria-label='Filter by status'
      >
        {STATUS_OPTIONS.map((opt) => (
          <option key={opt.value} value={opt.value}>
            {opt.label}
          </option>
        ))}
      </select>
    </div>
  );
}
