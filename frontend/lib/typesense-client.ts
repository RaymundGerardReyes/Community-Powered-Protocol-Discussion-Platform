// Typesense search-only client — bypasses Laravel for low-latency search.
// Falls back to Laravel /protocols?search= if NEXT_PUBLIC_TYPESENSE_SEARCH_KEY is absent.
import Typesense from 'typesense';

export const typesenseClient =
  typeof process.env.NEXT_PUBLIC_TYPESENSE_SEARCH_KEY === 'string' &&
  process.env.NEXT_PUBLIC_TYPESENSE_SEARCH_KEY.length > 0
    ? new Typesense.Client({
        nodes: [
          {
            host: process.env.NEXT_PUBLIC_TYPESENSE_HOST ?? 'localhost',
            port: Number(process.env.NEXT_PUBLIC_TYPESENSE_PORT ?? 8108),
            protocol: 'http',
          },
        ],
        apiKey: process.env.NEXT_PUBLIC_TYPESENSE_SEARCH_KEY,
        connectionTimeoutSeconds: 2,
      })
    : null;

export async function searchProtocols(query: string) {
  if (!typesenseClient) return null; // caller falls back to Laravel
  return typesenseClient
    .collections('protocols')
    .documents()
    .search({ q: query, query_by: 'title,description', per_page: 20 });
}
