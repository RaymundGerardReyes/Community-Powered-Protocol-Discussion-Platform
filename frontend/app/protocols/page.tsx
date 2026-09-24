import { Suspense } from 'react';
import type { Metadata } from 'next';
import { ProtocolFilterBar } from '@/features/protocols/components/ProtocolFilterBar';
import { ProtocolListClient } from '@/features/protocols/components/ProtocolListClient';

export const metadata: Metadata = {
  title: 'Protocols',
  description: 'Browse and discover community-powered Web3 protocol standards.',
};

interface PageProps {
  searchParams: Promise<Record<string, string>>;
}

export default async function ProtocolsPage({ searchParams }: PageProps) {
  const params = await searchParams;

  return (
    <div className="mx-auto max-w-6xl px-4 py-8">
      {/* ── Page header ─────────────────────────────────────────────────── */}
      <div className="mb-8">
        <h1
          className="text-2xl font-bold tracking-tight"
          style={{ color: 'var(--text-primary)' }}
        >
          Protocols
        </h1>
        <p className="mt-1 text-sm" style={{ color: 'var(--text-secondary)' }}>
          Discover and discuss community-powered Web3 protocol standards.
        </p>
      </div>

      {/* ── Filters ─────────────────────────────────────────────────────── */}
      <div className="mb-6">
        <Suspense>
          <ProtocolFilterBar />
        </Suspense>
      </div>

      {/* ── List ────────────────────────────────────────────────────────── */}
      <Suspense>
        <ProtocolListClient initialParams={params} />
      </Suspense>
    </div>
  );
}
