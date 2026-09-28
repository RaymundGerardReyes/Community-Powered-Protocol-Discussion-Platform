import Link from 'next/link';
import { VoteButtonWrapper } from '@/features/votes/components/VoteButtonWrapper';
import type { Thread } from '@/types';

export function ThreadCard({ thread }: { thread: Thread }) {
  return (
    <article
      className='card-flat group relative flex items-start gap-4 p-4 transition-all hover:-translate-y-0.5'
      style={{ cursor: 'pointer' }}
    >
      {/* Vote column - elevated above the stretched card link */}
      <div className='flex flex-col items-center gap-1 shrink-0 pt-0.5 relative z-10'>
        <VoteButtonWrapper
          votableType='thread'
          votableId={thread.id}
          count={thread.votes_count}
          queryKey={['thread', thread.id]}
        />
      </div>

      {/* Content */}
      <div className='min-w-0 flex-1'>
        {/* Title with full card hit area overlay */}
        <Link
          href={`/threads/${thread.id}`}
          className='block focus:outline-none after:absolute after:inset-0 after:z-0'
        >
          <h3
            className='text-sm font-semibold leading-snug line-clamp-2 transition-colors group-hover:underline'
            style={{ color: 'var(--text-primary)' }}
          >
            {thread.title}
          </h3>
        </Link>

        {(thread.content ?? thread.body) && (
          <p
            className='mt-1 text-xs leading-relaxed line-clamp-2 relative z-10 pointer-events-none'
            style={{ color: 'var(--text-secondary)' }}
          >
            {thread.content ?? thread.body}
          </p>
        )}

        {/* Meta */}
        <div
          className='mt-2 flex flex-wrap items-center gap-3 text-xs relative z-10 pointer-events-none'
          style={{ color: 'var(--text-muted)' }}
        >
          <span className='font-medium' style={{ color: 'var(--text-secondary)' }}>
            {thread.author?.name}
          </span>

          <span className='flex items-center gap-1'>
            <svg className='h-3 w-3' fill='none' viewBox='0 0 24 24' stroke='currentColor' aria-hidden>
              <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'/>
            </svg>
            {thread.comments_count}
          </span>

          <span className='flex items-center gap-1'>
            <svg className='h-3 w-3' fill='none' viewBox='0 0 24 24' stroke='currentColor' aria-hidden>
              <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/>
              <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'/>
            </svg>
            {thread.views_count}
          </span>

          <time dateTime={thread.created_at}>
            {new Date(thread.created_at).toLocaleDateString('en-US', {
              month: 'short', day: 'numeric', year: 'numeric',
            })}
          </time>
        </div>
      </div>
    </article>
  );
}
