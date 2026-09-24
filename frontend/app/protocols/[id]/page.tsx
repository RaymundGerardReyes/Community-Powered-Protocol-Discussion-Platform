import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { Suspense } from 'react';
import {
  fetchProtocol,
  fetchProtocolThreads,
  fetchProtocolReviews,
} from '@/features/protocols/api';
import { ThreadCard } from '@/features/threads/components/ThreadCard';
import { ReviewList } from '@/features/reviews/components/ReviewList';
import type { ApiError } from '@/lib/api-client';

interface PageProps {
  params: Promise<{ id: string }>;
}

/* ── SEO Metadata ──────────────────────────────────────────────────────── */
export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { id } = await params;
  try {
    const p = await fetchProtocol(id);
    return { title: p.title, description: p.description.slice(0, 160) };
  } catch {
    return { title: 'Protocol' };
  }
}

/* ── Star rating display ───────────────────────────────────────────────── */
function BigStarRating({ rating, count }: { rating: number; count: number }) {
  return (
    <div className="flex items-center gap-3">
      <span className="text-3xl font-bold tabular-nums" style={{ color: 'var(--text-primary)' }}>
        {rating.toFixed(1)}
      </span>
      <div>
        <div className="flex items-center gap-0.5">
          {Array.from({ length: 5 }, (_, i) => (
            <svg
              key={i}
              className={`h-4 w-4 ${i < Math.round(rating) ? 'star-filled' : 'star-empty'}`}
              fill="currentColor" viewBox="0 0 20 20" aria-hidden
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
          ))}
        </div>
        <p className="text-xs mt-0.5" style={{ color: 'var(--text-muted)' }}>{count} reviews</p>
      </div>
    </div>
  );
}

/* ── Status badge ─────────────────────────────────────────────────────── */
const STATUS_CLS: Record<string, string> = {
  published:  'badge-published',
  draft:      'badge-draft',
  deprecated: 'badge-deprecated',
};

/* ── Page ─────────────────────────────────────────────────────────────── */
export default async function ProtocolDetailPage({ params }: PageProps) {
  const { id } = await params;

  let protocol;
  try {
    protocol = await fetchProtocol(id);
  } catch (err) {
    if ((err as ApiError).status === 404) notFound();
    throw err;
  }

  const [threadsResult, reviewsResult] = await Promise.allSettled([
    fetchProtocolThreads(protocol.id),
    fetchProtocolReviews(protocol.id),
  ]);

  const threads = threadsResult.status === 'fulfilled' ? threadsResult.value.data : [];
  const reviews = reviewsResult.status === 'fulfilled' ? reviewsResult.value.data : [];

  return (
    <div className="mx-auto max-w-5xl px-4 py-8">
      {/* ── Hero ────────────────────────────────────────────────────────── */}
      <header className="mb-8">
        {/* Breadcrumb */}
        <nav className="mb-4 flex items-center gap-1.5 text-xs" style={{ color: 'var(--text-muted)' }} aria-label="Breadcrumb">
          <a href="/protocols" className="hover:text-[var(--brand)] transition-colors">Protocols</a>
          <span>/</span>
          <span style={{ color: 'var(--text-secondary)' }}>{protocol.category}</span>
        </nav>

        {/* Badges row */}
        <div className="flex flex-wrap items-center gap-2 mb-3">
          <span className={`badge ${STATUS_CLS[protocol.status] ?? 'badge-neutral'}`}>
            {protocol.status}
          </span>
          <span className="chip">{protocol.category}</span>
          <span className="chip">v{protocol.version}</span>
        </div>

        {/* Title */}
        <h1
          className="text-3xl font-bold tracking-tight leading-tight"
          style={{ color: 'var(--text-primary)' }}
        >
          {protocol.title}
        </h1>

        {/* Meta row */}
        <div className="mt-3 flex flex-wrap items-center gap-4 text-sm" style={{ color: 'var(--text-muted)' }}>
          <span>
            By{' '}
            <span className="font-medium" style={{ color: 'var(--text-secondary)' }}>
              {protocol.author.name}
            </span>
          </span>
          <span
            className="flex items-center gap-1 font-medium tabular-nums"
            style={{ color: 'var(--brand)' }}
          >
            <svg className="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden>
              <path d="M10 17l-1.45-1.32C4.4 11.67 2 9.43 2 6.7 2 4.57 3.57 3 5.7 3c1.14 0 2.23.53 2.93 1.36L10 5.73l1.37-1.37A3.93 3.93 0 0114.3 3C16.43 3 18 4.57 18 6.7c0 2.73-2.4 4.97-6.55 9l-1.45 1.3z"/>
            </svg>
            {protocol.votes_count} votes
          </span>
        </div>

        {/* Description */}
        <p
          className="mt-4 text-base leading-relaxed"
          style={{ color: 'var(--text-secondary)' }}
        >
          {protocol.description}
        </p>
      </header>

      {/* ── Two-column layout ────────────────────────────────────────────── */}
      <div className="grid gap-8 lg:grid-cols-[1fr_320px]">
        {/* Left: Threads */}
        <section>
          <h2 className="section-title mb-5">
            Discussion Threads
            <span
              className="badge badge-info ml-2"
              style={{ borderRadius: '999px' }}
            >
              {threads.length}
            </span>
          </h2>

          <Suspense>
            {threads.length > 0 ? (
              <ul className="space-y-3" role="list">
                {threads.map((thread) => (
                  <li key={thread.id}>
                    <ThreadCard thread={thread} />
                  </li>
                ))}
              </ul>
            ) : (
              <div
                className="rounded-xl p-6 text-center"
                style={{ background: 'var(--surface-card)', border: '1px solid var(--surface-overlay)' }}
              >
                <p className="text-sm" style={{ color: 'var(--text-muted)' }}>
                  No threads yet. Start the discussion!
                </p>
              </div>
            )}
          </Suspense>
        </section>

        {/* Right: Reviews */}
        <aside>
          <h2 className="section-title mb-5">Peer Reviews</h2>
          <div
            className="rounded-xl p-5 sticky top-20"
            style={{ background: 'var(--surface-card)', border: '1px solid var(--surface-overlay)' }}
          >
            <BigStarRating rating={protocol.average_rating} count={protocol.reviews_count} />
            <div className="divider" />
            <ReviewList reviews={reviews} />
          </div>
        </aside>
      </div>
    </div>
  );
}
