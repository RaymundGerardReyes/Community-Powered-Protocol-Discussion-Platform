'use client';
import { useState, useEffect, useCallback } from 'react';
import { useRouter, usePathname, useSearchParams } from 'next/navigation';

export function SearchBar() {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const currentSearch = searchParams.get('search') ?? '';
  const [inputValue, setInputValue] = useState(currentSearch);

  // Keep input synchronized if URL search param changes
  useEffect(() => {
    setInputValue(currentSearch);
  }, [currentSearch]);

  const applySearch = useCallback(
    (term: string) => {
      const params = new URLSearchParams(searchParams.toString());
      if (term.trim()) {
        params.set('search', term.trim());
      } else {
        params.delete('search');
      }
      params.delete('page');
      router.push(`${pathname}?${params.toString()}`);
    },
    [router, pathname, searchParams],
  );

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    applySearch(inputValue);
  }

  function handleClear() {
    setInputValue('');
    applySearch('');
  }

  return (
    <form onSubmit={handleSubmit} role="search" className="relative flex-1 min-w-0">
      <label htmlFor="protocol-search" className="sr-only">
        Search protocols
      </label>
      <div className="relative flex items-center">
        {/* Magnifying Glass Icon (Isolated Left Alignment & Non-Interfering Pointer Events) */}
        <div className="pointer-events-none absolute left-3.5 flex items-center justify-center text-slate-400">
          <svg
            className="h-4 w-4"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
          >
            <path
              fillRule="evenodd"
              d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
              clipRule="evenodd"
            />
          </svg>
        </div>

        {/* Search Input with Guaranteed 40px Icon Clearance */}
        <input
          id="protocol-search"
          type="search"
          value={inputValue}
          onChange={(e) => setInputValue(e.target.value)}
          placeholder="Search protocols by name, category, or description…"
          className="input h-10 text-sm transition-all focus:ring-2 focus:ring-indigo-500/20"
          style={{ paddingLeft: '2.5rem', paddingRight: '2.5rem' }}
          autoComplete="off"
          spellCheck="false"
        />

        {/* Clear Action Button (✕) */}
        {inputValue && (
          <button
            type="button"
            onClick={handleClear}
            aria-label="Clear search"
            className="absolute right-3 flex h-5 w-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
          >
            <svg className="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
              <path
                fillRule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clipRule="evenodd"
              />
            </svg>
          </button>
        )}
      </div>
    </form>
  );
}
