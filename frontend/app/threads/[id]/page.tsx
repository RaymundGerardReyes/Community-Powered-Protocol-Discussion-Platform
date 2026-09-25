import type { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import { fetchThread } from '@/features/threads/api';
import { CommentThread } from '@/features/comments/components/CommentThread';
import { CommentFormWrapper } from '@/features/comments/components/CommentFormWrapper';
import { VoteButtonWrapper } from '@/features/votes/components/VoteButtonWrapper';
import type { ApiError } from '@/lib/api-client';

interface PageProps {
  params: Promise<{ id: string }>;
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { id } = await params;
  try {
    const thread = await fetchThread(id);
    return {
      title: `${thread.title} | Protocol Hub`,
      description: thread.body.slice(0, 160),
    };
  } catch {
    return { title: 'Thread | Protocol Hub' };
  }
}

function AuthorAvatar({ name }: { name: string }) {
  const initials = name
    .split(' ')
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
  const hue = [...name].reduce((acc, c) => acc + c.charCodeAt(0), 0) % 360;

  return (
    <span
      className='inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white'
      style={{ background: `hsl(${hue}, 60%, 45%)` }}
      aria-hidden
    >
      {initials}
    </span>
  );
}

export default async function ThreadDetailPage({ params }: PageProps) {
  const { id } = await params;

  let thread;
  try {
    thread = await fetchThread(id);
  } catch (err) {
    if ((err as ApiError).status === 404) notFound();
    throw err;
  }

  const comments = thread.comments ?? [];

  return (
    <div className='mx-auto max-w-4xl px-4 py-10'>

      {/* ── Breadcrumb ───────────────────────────────────────────────── */}
      <nav
        className='mb-6 flex items-center gap-1.5 text-xs flex-wrap'
        style={{ color: 'var(--text-muted)' }}
        aria-label='Breadcrumb'
      >
        <Link
          href='/protocols'
          className='transition-colors hover:underline'
          style={{ color: 'var(--brand)' }}
        >
          Protocols
        </Link>
        <span>/</span>
        <Link
          href={`/protocols/${thread.protocol_id}`}
          className='transition-colors hover:underline'
          style={{ color: 'var(--brand)' }}
        >
          Protocol #{thread.protocol_id}
        </Link>
        <span>/</span>
        <span className='truncate max-w-[180px]' style={{ color: 'var(--text-secondary)' }}>
          {thread.title}
        </span>
      </nav>

      {/* ── Thread Main Card ─────────────────────────────────────────── */}
      <article className='card-flat p-5 sm:p-7 mb-8'>
        {/* Mobile: stack vote + content; Desktop: side-by-side */}
        <div className='flex flex-col sm:flex-row items-start gap-4 sm:gap-6'>

          {/* Vote column — horizontal on mobile, vertical on sm+ */}
          <div className='flex sm:flex-col items-center gap-2 sm:gap-0.5 shrink-0'>
            <VoteButtonWrapper
              votableType='App\Models\Thread'
              votableId={thread.id}
              count={thread.votes_count}
              queryKey={['thread', thread.id]}
              layout='vertical'
            />
          </div>

          {/* Main content */}
          <div className='min-w-0 flex-1'>
            <h1
              className='text-xl sm:text-2xl font-bold tracking-tight leading-snug'
              style={{ color: 'var(--text-primary)' }}
            >
              {thread.title}
            </h1>

            {/* Author + metadata */}
            <div
              className='mt-3 flex flex-wrap items-center gap-3 text-xs'
              style={{ color: 'var(--text-muted)' }}
            >
              <div className='flex items-center gap-2'>
                <AuthorAvatar name={thread.author?.name ?? 'Anonymous'} />
                <span className='font-medium' style={{ color: 'var(--text-secondary)' }}>
                  {thread.author?.name ?? 'Anonymous'}
                </span>
              </div>
              <span>·</span>
              <time dateTime={thread.created_at}>
                {new Date(thread.created_at).toLocaleDateString('en-US', {
                  month: 'short', day: 'numeric', year: 'numeric',
                })}
              </time>
              <span>·</span>
              <span className='flex items-center gap-1'>
                <svg className='h-3.5 w-3.5' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                  <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/>
                  <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'/>
                </svg>
                {thread.views_count} views
              </span>
              <span>·</span>
              <span className='flex items-center gap-1'>
                <svg className='h-3.5 w-3.5' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                  <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'/>
                </svg>
                {thread.comments_count} comments
              </span>
            </div>

            {/* Divider */}
            <div className='divider my-5' />

            {/* Body text */}
            <div
              className='text-sm leading-relaxed whitespace-pre-wrap'
              style={{ color: 'var(--text-secondary)' }}
            >
              {thread.body}
            </div>
          </div>
        </div>
      </article>

      {/* ── Comments Section ─────────────────────────────────────────── */}
      <section>
        {/* Section header */}
        <div className='flex items-center gap-2 mb-5'>
          <h2 className='section-title'>Discussion</h2>
          <span
            className='badge badge-info'
            style={{ borderRadius: '999px' }}
          >
            {thread.comments_count}
          </span>
        </div>

        {/* New comment box */}
        <div className='card-flat p-5 mb-6'>
          <h3
            className='text-xs font-semibold uppercase tracking-wider mb-3'
            style={{ color: 'var(--text-muted)' }}
          >
            Leave a response
          </h3>
          <CommentFormWrapper threadId={thread.id} />
        </div>

        {/* Comment tree */}
        <CommentThread comments={comments} />
      </section>
    </div>
  );
}
