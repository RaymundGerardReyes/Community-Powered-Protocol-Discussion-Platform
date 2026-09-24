'use client';
import { useState, useRef, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useProtocolSearch } from '../hooks/useProtocolSearch';
import Link from 'next/link';

export function NavSearch() {
  const [query, setQuery] = useState('');
  const [open, setOpen] = useState(false);
  const router = useRouter();
  const ref = useRef<HTMLDivElement>(null);
  const { results, isPending } = useProtocolSearch(query);

  // Close on outside click
  useEffect(() => {
    function handle(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    }
    document.addEventListener('mousedown', handle);
    return () => document.removeEventListener('mousedown', handle);
  }, []);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (query.trim()) {
      router.push(`/protocols?search=${encodeURIComponent(query.trim())}`);
      setOpen(false);
    }
  }

  return (
    <div ref={ref} className="relative w-full">
      <form onSubmit={handleSubmit} role="search">
        <label htmlFor="nav-search" className="sr-only">Search protocols</label>
        <div className="relative">
          {/* Search icon */}
          <svg
            className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5"
            style={{ color: 'var(--text-muted)' }}
            viewBox="0 0 20 20" fill="currentColor" aria-hidden
          >
            <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd"/>
          </svg>
          <input
            id="nav-search"
            type="search"
            value={query}
            onChange={(e) => { setQuery(e.target.value); setOpen(true); }}
            onFocus={() => setOpen(true)}
            placeholder="Search protocols…"
            className="input pl-9 pr-4 h-9 text-sm"
            autoComplete="off"
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

      {/* Dropdown results */}
      {open && query.trim().length > 0 && (
        <div
          className="absolute top-full left-0 right-0 mt-1 rounded-xl shadow-2xl z-50 overflow-hidden"
          style={{
            background: 'var(--surface-card)',
            border: '1px solid var(--surface-overlay)',
            boxShadow: '0 20px 40px rgba(0,0,0,0.6)',
          }}
        >
          {results.length === 0 && !isPending ? (
            <p className="px-4 py-3 text-sm" style={{ color: 'var(--text-muted)' }}>
              No protocols found for &ldquo;{query}&rdquo;
            </p>
          ) : (
            <ul>
              {results.slice(0, 6).map((p) => (
                <li key={p.id}>
                  <Link
                    href={`/protocols/${p.slug}`}
                    onClick={() => { setOpen(false); setQuery(''); }}
                    className="flex items-start gap-3 px-4 py-3 hover:bg-[rgba(99,102,241,0.08)] transition-colors"
                  >
                    <span
                      className="mt-0.5 shrink-0 w-1.5 h-1.5 rounded-full"
                      style={{ background: 'var(--brand)', marginTop: '6px' }}
                      aria-hidden
                    />
                    <div className="min-w-0">
                      <p className="text-sm font-medium truncate" style={{ color: 'var(--text-primary)' }}>
                        {p.title}
                      </p>
                      <p className="text-xs truncate" style={{ color: 'var(--text-muted)' }}>
                        {p.category} · v{p.version}
                      </p>
                    </div>
                  </Link>
                </li>
              ))}
              <li>
                <button
                  onClick={() => { router.push(`/protocols?search=${encodeURIComponent(query)}`); setOpen(false); }}
                  className="w-full text-left px-4 py-2.5 text-xs font-medium transition-colors hover:bg-[rgba(99,102,241,0.08)]"
                  style={{ color: 'var(--brand)', borderTop: '1px solid var(--surface-overlay)' }}
                >
                  View all results for &ldquo;{query}&rdquo; →
                </button>
              </li>
            </ul>
          )}
        </div>
      )}
    </div>
  );
}
