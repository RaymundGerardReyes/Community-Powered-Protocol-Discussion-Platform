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
  draft: 'warning',
  deprecated: 'danger',
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
    <div className='mx-auto max-w-4xl px-4 py-8'>
      {/* Protocol Header */}
      <div className='mb-8'>
        <div className='flex flex-wrap items-start gap-3'>
          <Badge variant={STATUS_VARIANT[protocol.status] ?? 'default'}>
            {protocol.status}
          </Badge>
          <span className='rounded bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-600'>
            {protocol.category}
          </span>
          <span className='rounded bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-600'>
            v{protocol.version}
          </span>
        </div>
        <h1 className='mt-3 text-3xl font-bold text-slate-900'>{protocol.title}</h1>
        <div className='mt-2 flex items-center gap-4 text-sm text-slate-500'>
          <span>By <span className='font-medium text-slate-700'>{protocol.author.name}</span></span>
          <RatingStars rating={protocol.average_rating} />
          <span>{protocol.reviews_count} reviews</span>
          <span>▲ {protocol.votes_count} votes</span>
        </div>
        <p className='mt-4 text-slate-700 leading-relaxed'>{protocol.description}</p>
      </div>

      {/* Threads */}
      <section className='mb-10'>
        <h2 className='mb-4 text-lg font-semibold text-slate-900'>Discussion Threads</h2>
        <Suspense fallback={<Spinner />}>
          {threads.length > 0 ? (
            <ul className='space-y-3'>
              {threads.map((thread) => (
                <li key={thread.id}><ThreadCard thread={thread} /></li>
              ))}
            </ul>
          ) : (
            <p className='text-sm text-slate-500 italic'>No threads yet.</p>
          )}
        </Suspense>
      </section>

      {/* Reviews */}
      <section>
        <h2 className='mb-4 text-lg font-semibold text-slate-900'>
          Peer Reviews
          <span className='ml-2 text-sm font-normal text-slate-500'>
            ({protocol.reviews_count})
          </span>
        </h2>
        <ReviewList reviews={reviews} />
      </section>
    </div>
  );
}
