import { Suspense } from 'react';
import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { fetchProtocol, fetchProtocolThreads, fetchProtocolReviews } from '@/features/protocols/api';
import { Badge } from '@/components/ui/Badge';
import { RatingStars } from '@/components/ui/RatingStars';
import { ReviewList } from '@/features/reviews/components/ReviewList';
import { ThreadCard } from '@/features/threads/components/ThreadCard';
import { Spinner } from '@/components/ui/Spinner';
import type { ApiError } from '@/lib/api-client';

interface PageProps {
  params: Promise<{ id: string }>;
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { id } = await params;
  try {
    const protocol = await fetchProtocol(id);
    return { title: protocol.title, description: protocol.description.slice(0, 160) };
  } catch {
    return { title: 'Protocol' };
  }
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger'> = {
  published: 'success',
  draft:     'warning',
  deprecated:'danger',
};

export default async function ProtocolDetailPage({ params }: PageProps) {
  const { id } = await params;

  let protocol;
  try {
    protocol = await fetchProtocol(id);
  } catch (err) {
    const apiErr = err as ApiError;
    if (apiErr.status === 404) notFound();
    throw err;
  }

  const [threadsResult, reviewsResult] = await Promise.allSettled([
    fetchProtocolThreads(protocol.id),
    fetchProtocolReviews(protocol.id),
  ]);

  const threads = threadsResult.status === 'fulfilled' ? threadsResult.value.data : [];
  const reviews = reviewsResult.status === 'fulfilled' ? reviewsResult.value.data : [];

  return (
    <div className='mx-auto max-w-4xl px-4 py-10'>
      {/* ── Protocol Header Card ─────────────────────────────────────── */}
      <div
        className='card-flat p-6 mb-8'
        style={{ background: 'var(--surface-card)' }}
      >
        {/* Chips row */}
        <div className='flex flex-wrap items-center gap-2 mb-4'>
          <Badge variant={STATUS_VARIANT[protocol.status] ?? 'default'}>
            {protocol.status}
          </Badge>
          <span
            className='rounded px-2 py-0.5 text-xs font-mono font-medium'
            style={{ background: 'var(--brand-light)', color: 'var(--brand)' }}
          >
            {protocol.category}
          </span>
          <span
            className='rounded px-2 py-0.5 text-xs font-mono'
            style={{ background: 'var(--surface-muted)', color: 'var(--text-muted)' }}
          >
            v{protocol.version}
          </span>
        </div>

        {/* Title */}
        <h1
          className='text-2xl font-bold tracking-tight leading-snug'
          style={{ color: 'var(--text-primary)' }}
        >
          {protocol.title}
        </h1>

        {/* Author + stats row */}
        <div
          className='mt-3 flex flex-wrap items-center gap-4 text-sm'
          style={{ color: 'var(--text-muted)' }}
        >
          <span>
            By{' '}
            <span className='font-medium' style={{ color: 'var(--text-secondary)' }}>
              {protocol.author.name}
            </span>
          </span>
          <RatingStars rating={protocol.average_rating} />
          <span>{protocol.reviews_count} reviews</span>
          <span
            className='font-semibold flex items-center gap-1'
            style={{ color: 'var(--brand)' }}
          >
            ▲ {protocol.votes_count} votes
          </span>
        </div>

        {/* Divider */}
        <div className='divider my-5' />

        {/* Description */}
        <p
          className='text-sm leading-relaxed'
          style={{ color: 'var(--text-secondary)' }}
        >
          {protocol.description}
        </p>
      </div>

      {/* ── Discussion Threads ───────────────────────────────────────── */}
      <section className='mb-10'>
        <h2 className='section-title mb-4'>
          Discussion Threads
          <span
            className='badge badge-info ml-2'
            style={{ borderRadius: '999px', verticalAlign: 'middle' }}
          >
            {threads.length}
          </span>
        </h2>
        <Suspense fallback={<Spinner />}>
          {threads.length > 0 ? (
            <ul className='space-y-3'>
              {threads.map((thread) => (
                <li key={thread.id}><ThreadCard thread={thread} /></li>
              ))}
            </ul>
          ) : (
            <div
              className='rounded-xl p-8 text-center text-sm italic'
              style={{
                background: 'var(--surface-card)',
                border: '1px solid var(--border)',
                color: 'var(--text-muted)',
              }}
            >
              No discussion threads yet.
            </div>
          )}
        </Suspense>
      </section>

      {/* ── Peer Reviews ─────────────────────────────────────────────── */}
      <section>
        <h2 className='section-title mb-4'>
          Peer Reviews
          <span
            className='badge badge-neutral ml-2'
            style={{ borderRadius: '999px', verticalAlign: 'middle' }}
          >
            {protocol.reviews_count}
          </span>
        </h2>
        <div className='card-flat p-5'>
          <ReviewList reviews={reviews} />
        </div>
      </section>
    </div>
  );
}
