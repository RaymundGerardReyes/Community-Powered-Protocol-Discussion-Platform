'use client';
import { useRouter, useSearchParams, usePathname } from 'next/navigation';
import { useCallback } from 'react';
import { cn } from '@/lib/cn';

import { useCategories } from '../hooks/useCategories';

const SORT_OPTIONS = [
  { label: 'Latest',         value: 'latest' },
  { label: 'Top Voted',      value: 'top' },
  { label: 'Highest Rated',  value: 'rating' },
];

export const WELLNESS_CATEGORY_FALLBACKS = [
  { label: 'All Categories', value: '' },
  { label: 'Biohacking', value: 'biohacking' },
  { label: 'Breathwork', value: 'breathwork' },
  { label: 'Cardiovascular', value: 'cardiovascular' },
  { label: 'Fitness', value: 'fitness' },
  { label: 'Gut Health', value: 'gut-health' },
  { label: 'Immunology', value: 'immunology' },
  { label: 'Instructional', value: 'instructional' },
  { label: 'Nutrition', value: 'nutrition' },
  { label: 'Physical Therapy', value: 'physical-therapy' },
  { label: 'Recovery', value: 'recovery' },
  { label: 'Sleep', value: 'sleep' },
  { label: 'Supplements', value: 'supplements' },
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

  const { data: dynamicCategories } = useCategories();

  const categoryOptions = dynamicCategories && dynamicCategories.length > 0
    ? [
        { label: 'All Categories', value: '' },
        ...dynamicCategories.map((c) => ({
          label: c.count !== undefined && c.count > 0 ? `${c.name} (${c.count})` : c.name,
          value: c.slug,
        })),
      ]
    : WELLNESS_CATEGORY_FALLBACKS;

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
        {categoryOptions.map((opt) => (
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
