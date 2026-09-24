import Link from 'next/link';
import { Badge } from '@/components/ui/Badge';
import { RatingStars } from '@/components/ui/RatingStars';
import { cn } from '@/lib/cn';
import type { Protocol } from '@/types';

const STATUS_VARIANT: Record<Protocol['status'], 'success' | 'warning' | 'danger'> = {
  published: 'success',
  draft: 'warning',
  deprecated: 'danger',
};

export function ProtocolCard({ protocol }: { protocol: Protocol }) {
  return (
    <article className={cn('rounded-lg border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition-shadow')}>
      <div className='flex items-start justify-between gap-3'>
        <Link
          href={`/protocols/${protocol.slug}`}
          className='group flex-1 min-w-0'
        >
          <h3 className='text-base font-semibold text-slate-900 group-hover:text-indigo-600 truncate transition-colors'>
            {protocol.title}
          </h3>
        </Link>
        <Badge variant={STATUS_VARIANT[protocol.status]}>
          {protocol.status}
        </Badge>
      </div>

      <p className='mt-2 text-sm text-slate-600 line-clamp-2'>
        {protocol.description}
      </p>

      <div className='mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500'>
        <span className='bg-slate-100 rounded px-2 py-0.5 font-mono'>
          {protocol.category}
        </span>
        <span className='bg-slate-100 rounded px-2 py-0.5 font-mono'>
          v{protocol.version}
        </span>
      </div>

      <div className='mt-4 flex items-center justify-between text-xs text-slate-500'>
        <span>
          By{' '}
          <span className='font-medium text-slate-700'>{protocol.author.name}</span>
        </span>
        <div className='flex items-center gap-3'>
          <RatingStars rating={protocol.average_rating} size='sm' />
          <span>{protocol.reviews_count} reviews</span>
          <span>▲ {protocol.votes_count}</span>
        </div>
      </div>
    </article>
  );
}
