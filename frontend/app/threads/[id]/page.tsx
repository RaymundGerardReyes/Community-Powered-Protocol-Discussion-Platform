import type { Metadata } from 'next';
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
    return { title: thread.title };
  } catch {
    return { title: 'Thread' };
  }
}

export default async function ThreadDetailPage({ params }: PageProps) {
  const { id } = await params;

  let thread;
  try {
    thread = await fetchThread(id);
  } catch (err) {
    const apiErr = err as ApiError;
    if (apiErr.status === 404) notFound();
    throw err;
  }

  const comments = thread.comments ?? [];

  return (
    <div className='mx-auto max-w-3xl px-4 py-8'>
      <article>
        <h1 className='text-2xl font-bold text-slate-900'>{thread.title}</h1>
        <div className='mt-2 flex items-center gap-3 text-sm text-slate-500'>
          <span>By <span className='font-medium text-slate-700'>{thread.author?.name}</span></span>
          <span>{thread.comments_count} comments</span>
          <span>{thread.views_count} views</span>
        </div>
        <p className='mt-4 text-slate-700 leading-relaxed whitespace-pre-wrap'>{thread.body}</p>
        <div className='mt-4'>
          <VoteButtonWrapper
            votableType='App\\Models\\Thread'
            votableId={thread.id}
            count={thread.votes_count}
            queryKey={['thread', thread.id]}
          />
        </div>
      </article>

      <hr className='my-8 border-slate-200' />

      <section>
        <h2 className='mb-4 text-lg font-semibold text-slate-900'>
          Comments ({thread.comments_count})
        </h2>
        <div className='mb-6'>
          <CommentFormWrapper threadId={thread.id} />
        </div>
        <CommentThread comments={comments} />
      </section>
    </div>
  );
}
