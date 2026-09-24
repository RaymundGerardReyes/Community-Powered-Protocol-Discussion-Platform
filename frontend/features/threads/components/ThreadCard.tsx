import Link from 'next/link';
import { VoteButton } from '@/features/votes/components/VoteButton';
import type { Thread } from '@/types';

export function ThreadCard({ thread }: { thread: Thread }) {
  return (
    <article className='rounded-lg border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow'>
      <div className='flex items-start gap-3'>
        <VoteButton
          votableType='App\\Models\\Thread'
          votableId={thread.id}
          currentVote={null}
          count={thread.votes_count}
          queryKey={['thread', thread.id]}
        />
        <div className='min-w-0 flex-1'>
          <Link
            href={`/threads/${thread.id}`}
            className='font-medium text-slate-900 hover:text-indigo-600 transition-colors'
          >
            {thread.title}
          </Link>
          <p className='mt-1 text-sm text-slate-500 line-clamp-2'>{thread.body}</p>
          <div className='mt-2 flex items-center gap-3 text-xs text-slate-400'>
            <span>By {thread.author?.name}</span>
            <span>{thread.comments_count} comments</span>
            <span>{thread.views_count} views</span>
            <time dateTime={thread.created_at}>
              {new Date(thread.created_at).toLocaleDateString()}
            </time>
          </div>
        </div>
      </div>
    </article>
  );
}
