'use client';
import { useState } from 'react';
import { useRouter, usePathname, useSearchParams } from 'next/navigation';
import { useProtocolSearch } from '../hooks/useProtocolSearch';
import { cn } from '@/lib/cn';

export function SearchBar() {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [inputValue, setInputValue] = useState(searchParams.get('search') ?? '');

  const { isPending } = useProtocolSearch(inputValue);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const params = new URLSearchParams(searchParams.toString());
    if (inputValue.trim()) {
      params.set('search', inputValue.trim());
    } else {
      params.delete('search');
    }
    params.delete('page');
    router.push(`${pathname}?${params.toString()}`);
  }

  return (
    <form onSubmit={handleSubmit} role='search' className='relative flex-1 min-w-0'>
      <label htmlFor='protocol-search' className='sr-only'>
        Search protocols
      </label>
      <input
        id='protocol-search'
        type='search'
        value={inputValue}
        onChange={(e) => setInputValue(e.target.value)}
        placeholder='Search protocols…'
        className={cn(
          'w-full rounded-lg border border-slate-300 bg-white px-4 py-2 pr-10 text-sm text-slate-900 placeholder-slate-400',
          'focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500',
        )}
      />
      {isPending && (
        <span className='absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs'>
          …
        </span>
      )}
    </form>
  );
}
