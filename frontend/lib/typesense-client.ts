// Typesense search-only client — bypasses Laravel for low-latency search.
// Falls back to Laravel /protocols?search= if NEXT_PUBLIC_TYPESENSE_SEARCH_KEY is absent.
import Typesense from 'typesense';

const rawHost = process.env.NEXT_PUBLIC_TYPESENSE_HOST ?? 'localhost';
const cleanHost = rawHost.replace(/^https?:\/\//i, '').replace(/\/+$/, '');
const port = Number(
  process.env.NEXT_PUBLIC_TYPESENSE_PORT ?? (cleanHost.includes('typesense.net') ? 443 : 8108),
);
const protocol =
  process.env.NEXT_PUBLIC_TYPESENSE_PROTOCOL ??
  (port === 443 || cleanHost.includes('typesense.net') ? 'https' : 'http');

export const typesenseClient =
  typeof process.env.NEXT_PUBLIC_TYPESENSE_SEARCH_KEY === 'string' &&
  process.env.NEXT_PUBLIC_TYPESENSE_SEARCH_KEY.length > 0
    ? new Typesense.Client({
        nodes: [
          {
            host: cleanHost,
            port,
            protocol,
          },
        ],
        apiKey: process.env.NEXT_PUBLIC_TYPESENSE_SEARCH_KEY,
        connectionTimeoutSeconds: 2,
      })
    : null;

export async function searchProtocols(query: string) {
  if (!typesenseClient) return null; // caller falls back to Laravel
  try {
    return await typesenseClient
      .collections('protocols')
      .documents()
      .search({ q: query, query_by: 'title,description', per_page: 20 });
  } catch {
    // If Typesense is unreachable or times out, gracefully fall back to Laravel search
    return null;
  }
}

