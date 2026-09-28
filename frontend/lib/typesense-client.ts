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

