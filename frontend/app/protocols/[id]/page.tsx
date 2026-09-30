import { cache } from 'react';
import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { fetchProtocol, fetchProtocolThreads, fetchProtocolReviews } from '@/features/protocols/api';
import { Badge } from '@/components/ui/Badge';
import { RatingStars } from '@/components/ui/RatingStars';
import { ReviewList } from '@/features/reviews/components/ReviewList';
import { CreateReviewForm } from '@/features/reviews/components/CreateReviewForm';
import { ThreadSection } from '@/features/threads/components/ThreadSection';
import { VoteButtonWrapper } from '@/features/votes/components/VoteButtonWrapper';
import type { ApiError } from '@/lib/api-client';

interface PageProps {
  params: Promise<{ id: string }>;
}

const getCachedProtocol = cache(async (id: string) => {
  return await fetchProtocol(id);
});

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { id } = await params;
  try {
    const protocol = await getCachedProtocol(id);
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
    protocol = await getCachedProtocol(id);
  } catch (err) {
    const apiErr = err as ApiError;
    if (apiErr.status === 404) notFound();
    throw err;
  }

  let threads = protocol.threads ?? [];
  let reviews = protocol.reviews ?? [];

  if (!protocol.threads || !protocol.reviews) {
    const [threadsResult, reviewsResult] = await Promise.allSettled([
      protocol.threads ? Promise.resolve({ data: protocol.threads }) : fetchProtocolThreads(protocol.id),
      protocol.reviews ? Promise.resolve({ data: protocol.reviews }) : fetchProtocolReviews(protocol.id),
    ]);

    if (!protocol.threads && threadsResult.status === 'fulfilled') {
      threads = threadsResult.value.data;
    }
    if (!protocol.reviews && reviewsResult.status === 'fulfilled') {
      reviews = reviewsResult.value.data;
    }
  }


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
          <div className='flex items-center gap-1.5'>
            <VoteButtonWrapper
              votableType='protocol'
              votableId={protocol.id}
              count={protocol.votes_count}
              layout='horizontal'
            />
          </div>
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
      <ThreadSection protocolId={protocol.id} initialThreads={threads} />

      {/* ── Peer Reviews ─────────────────────────────────────────────── */}
      <section>
        <div className='flex items-center justify-between mb-4'>
          <h2 className='section-title mb-0'>
            Peer Reviews
            <span
              className='badge badge-neutral ml-2'
              style={{ borderRadius: '999px', verticalAlign: 'middle' }}
            >
              {protocol.reviews_count}
            </span>
          </h2>
        </div>
        <CreateReviewForm
          protocolId={protocol.id}
          authorId={protocol.author?.id}
          existingReviewerIds={reviews.map((r) => r.author?.id).filter((id): id is number => typeof id === 'number')}
        />
        <div className='card-flat p-5'>
          <ReviewList reviews={reviews} />
        </div>
      </section>
    </div>
  );
}
