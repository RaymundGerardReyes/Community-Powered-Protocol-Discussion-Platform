'use client';
import { useEffect, useState } from 'react';
import { searchProtocols } from '@/lib/typesense-client';
import { fetchProtocols } from '../api';
import type { Protocol } from '@/types';

/** Debounced search: tries Typesense first, falls back to Laravel. */
export function useProtocolSearch(query: string, debounceMs = 300) {
  const [results, setResults] = useState<Protocol[]>([]);
  const [isPending, setIsPending] = useState(false);

  useEffect(() => {
    if (!query.trim()) {
      setResults([]);
      return;
    }
    setIsPending(true);
    const timer = setTimeout(async () => {
      try {
        const ts = await searchProtocols(query);
        if (ts) {
          // Typesense hit — map minimal shape
          setResults(
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (ts.hits ?? []).map((h: any) => h.document as Protocol),
          );
        } else {
          // Fallback: Laravel
          const res = await fetchProtocols({ search: query });
          setResults(res.data);
        }
      } catch {
        setResults([]);
      } finally {
        setIsPending(false);
      }
    }, debounceMs);
    return () => clearTimeout(timer);
  }, [query, debounceMs]);

  return { results, isPending };
}
