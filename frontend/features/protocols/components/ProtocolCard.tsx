import Link from 'next/link';
import { Badge } from '@/components/ui/Badge';
import { RatingStars } from '@/components/ui/RatingStars';
import { VoteButtonWrapper } from '@/features/votes/components/VoteButtonWrapper';
import type { Protocol } from '@/types';

const STATUS_VARIANT: Record<Protocol['status'], 'success' | 'warning' | 'danger'> = {
  published: 'success',
  draft: 'warning',
  deprecated: 'danger',
};

export function ProtocolCard({ protocol }: { protocol: Protocol }) {
  return (
    <article
      className='card-flat group relative flex flex-col h-full p-5 transition-all hover:-translate-y-0.5'
      style={{ cursor: 'pointer' }}
    >
      {/* Header row */}
      <div className='flex items-start justify-between gap-3 mb-3 relative z-10 pointer-events-none'>
        <div className='flex flex-wrap items-center gap-1.5 min-w-0'>
          <Badge variant={STATUS_VARIANT[protocol.status]}>{protocol.status}</Badge>
          <span
            className='rounded px-2 py-0.5 text-[0.68rem] font-mono font-medium'
            style={{ background: 'var(--brand-light)', color: 'var(--brand)' }}
          >
            {protocol.category}
          </span>
          <span
            className='rounded px-2 py-0.5 text-[0.68rem] font-mono'
            style={{ background: 'var(--surface-muted)', color: 'var(--text-muted)' }}
          >
            v{protocol.version}
          </span>
        </div>
      </div>

      {/* Title with full card hit area overlay */}
      <Link
        href={`/protocols/${protocol.slug}`}
        className='flex-1 min-w-0 block focus:outline-none after:absolute after:inset-0 after:z-0'
      >
        <h3
          className='text-sm font-semibold leading-snug line-clamp-2 transition-colors group-hover:underline'
          style={{ color: 'var(--text-primary)' }}
        >
          {protocol.title}
        </h3>
      </Link>

      {/* Description */}
      <p
        className='mt-2 text-xs leading-relaxed line-clamp-2 relative z-10 pointer-events-none'
        style={{ color: 'var(--text-secondary)' }}
      >
        {protocol.description}
      </p>

      {/* Footer */}
      <div
        className='mt-4 pt-3 flex items-center justify-between text-xs relative z-10 pointer-events-none'
        style={{ borderTop: '1px solid var(--border)', color: 'var(--text-muted)' }}
      >
        <span>
          By{' '}
          <span className='font-medium' style={{ color: 'var(--text-secondary)' }}>
            {protocol.author.name}
          </span>
        </span>
        <div className='flex items-center gap-3'>
          <RatingStars rating={protocol.average_rating} size='sm' />
          <span>{protocol.reviews_count} reviews</span>
          <div className='pointer-events-auto'>
            <VoteButtonWrapper
              votableType='protocol'
              votableId={protocol.id}
              count={protocol.votes_count}
              layout='horizontal'
            />
          </div>
        </div>
      </div>
    </article>
  );
}
