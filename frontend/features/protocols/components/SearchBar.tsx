'use client';
import { useState } from 'react';
import { useRouter, usePathname, useSearchParams } from 'next/navigation';
import { useProtocolSearch } from '../hooks/useProtocolSearch';

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
    <form onSubmit={handleSubmit} role="search" className="relative flex-1 min-w-0">
      <label htmlFor="protocol-search" className="sr-only">
        Search protocols
      </label>
      <div className="relative">
        <svg
          className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4"
          style={{ color: 'var(--text-muted)' }}
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden
        >
          <path
            fillRule="evenodd"
            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
            clipRule="evenodd"
          />
        </svg>
        <input
          id="protocol-search"
          type="search"
          value={inputValue}
          onChange={(e) => setInputValue(e.target.value)}
          placeholder="Search protocols by name or description…"
          className="input pl-9 pr-10 h-10 text-sm"
        />
        {isPending && (
          <span
            className="absolute right-3 top-1/2 -translate-y-1/2 text-xs"
            style={{ color: 'var(--text-muted)' }}
            aria-live="polite"
          >
            …
          </span>
        )}
      </div>
    </form>
  );
}
