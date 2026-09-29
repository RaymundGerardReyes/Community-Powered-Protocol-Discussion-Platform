// Typesense search-only client — bypasses Laravel for low-latency search.
// Falls back to Laravel /protocols?search= if NEXT_PUBLIC_TYPESENSE_SEARCH_KEY is absent.
import Typesense from 'typesense';

export interface TypesenseResolvedConfig {
  host: string;
  port: number;
  protocol: string;
  apiKey: string | undefined;
  isValid: boolean;
}

export function resolveTypesenseConfig(env: {
  host?: string;
  port?: string | number;
  protocol?: string;
  apiKey?: string;
}): TypesenseResolvedConfig {
  const rawHost = env.host ?? 'localhost';
  const cleanHost = rawHost.replace(/^https?:\/\//i, '').replace(/\/+$/, '');
  const port = Number(
    env.port ?? (cleanHost.includes('typesense.net') ? 443 : 8108),
  );
  const protocol =
    env.protocol ??
    (port === 443 || cleanHost.includes('typesense.net') ? 'https' : 'http');
  const apiKey = env.apiKey;

  return {
    host: cleanHost,
    port,
    protocol,
    apiKey,
    isValid: typeof apiKey === 'string' && apiKey.length > 0,
  };
}

const activeConfig = resolveTypesenseConfig({
  host: process.env.NEXT_PUBLIC_TYPESENSE_HOST,
  port: process.env.NEXT_PUBLIC_TYPESENSE_PORT,
  protocol: process.env.NEXT_PUBLIC_TYPESENSE_PROTOCOL,
  apiKey: process.env.NEXT_PUBLIC_TYPESENSE_SEARCH_KEY,
});

export const typesenseClient = activeConfig.isValid
  ? new Typesense.Client({
      nodes: [
        {
          host: activeConfig.host,
          port: activeConfig.port,
          protocol: activeConfig.protocol,
        },
      ],
      apiKey: activeConfig.apiKey!,
      connectionTimeoutSeconds: 2,
    })
  : null;

export interface ProtocolSearchParams {
  query: string;
  sortBy?: 'recent' | 'reviewed' | 'rating' | 'upvoted' | 'newest' | 'reviews' | 'top' | string;
  perPage?: number;
}

export async function searchProtocols(paramsOrQuery: string | ProtocolSearchParams) {
  if (!typesenseClient) return null; // caller falls back to Laravel

  const query = typeof paramsOrQuery === 'string' ? paramsOrQuery : paramsOrQuery.query;
  const sortByParam = typeof paramsOrQuery === 'string' ? undefined : paramsOrQuery.sortBy;
  const perPage = (typeof paramsOrQuery !== 'string' && paramsOrQuery.perPage) ? paramsOrQuery.perPage : 20;

  let sortBy: string | undefined;
  if (sortByParam === 'recent' || sortByParam === 'newest') {
    sortBy = 'created_at:desc';
  } else if (sortByParam === 'reviewed' || sortByParam === 'reviews') {
    sortBy = 'reviews_count:desc';
  } else if (sortByParam === 'rating') {
    sortBy = 'average_rating:desc';
  } else if (sortByParam === 'upvoted' || sortByParam === 'top') {
    sortBy = 'votes:desc';
  } else if (sortByParam) {
    sortBy = sortByParam;
  }

  try {
    const searchOptions: Record<string, unknown> = {
      q: query,
      query_by: 'title,description,tags',
      per_page: perPage,
    };
    if (sortBy) {
      searchOptions.sort_by = sortBy;
    }

    return await typesenseClient
      .collections('protocols')
      .documents()
      .search(searchOptions);
  } catch {
    // If Typesense is unreachable or times out, gracefully fall back to Laravel search
    return null;
  }
}

export async function searchThreads(query: string) {
  if (!typesenseClient) return null; // caller falls back to Laravel
  try {
    return await typesenseClient
      .collections('threads')
      .documents()
      .search({ q: query, query_by: 'title,body,content,tags', per_page: 20 });
  } catch {
    // If Typesense is unreachable or times out, gracefully fall back
    return null;
  }
}

