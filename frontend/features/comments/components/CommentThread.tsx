'use client';
import { useState } from 'react';
import { cn } from '@/lib/cn';
import { VoteButton } from '@/features/votes/components/VoteButton';
import type { Comment } from '@/types';

const MAX_VISUAL_DEPTH = 5;

interface CommentItemProps {
  comment: Comment;
  depth: number;
}

function CommentItem({ comment, depth }: CommentItemProps) {
  const [collapsed, setCollapsed] = useState(false);
  const hasReplies = (comment.replies?.length ?? 0) > 0;
  const effectiveDepth = Math.min(depth, MAX_VISUAL_DEPTH);

  return (
    <div
      className={cn(
        'relative',
        depth > 0 && 'ml-4 border-l-2 border-slate-100 pl-4',
      )}
    >
      <div className='py-2'>
        <div className='flex items-center gap-2 text-xs text-slate-500'>
          <span className='font-medium text-slate-700'>
            {comment.author?.name ?? 'Anonymous'}
          </span>
          <span>·</span>
          <time dateTime={comment.created_at}>
            {new Date(comment.created_at).toLocaleDateString()}
          </time>
          {hasReplies && depth < MAX_VISUAL_DEPTH && (
            <button
              onClick={() => setCollapsed((c) => !c)}
              className='ml-1 text-indigo-500 hover:text-indigo-700 text-xs'
            >
              {collapsed ? `Show ${comment.replies!.length} replies` : 'Collapse'}
            </button>
          )}
        </div>
        <p className='mt-1 text-sm text-slate-800 leading-relaxed'>
          {comment.body}
        </p>
        <div className='mt-2 flex items-center gap-3'>
          <VoteButton
            votableType='App\\Models\\Comment'
            votableId={comment.id}
            currentVote={null}
            count={0}
            queryKey={['thread', comment.thread_id]}
          />
        </div>
      </div>

      {!collapsed && hasReplies && effectiveDepth < MAX_VISUAL_DEPTH && (
        <div>
          {comment.replies!.map((reply) => (
            <CommentItem key={reply.id} comment={reply} depth={depth + 1} />
          ))}
        </div>
      )}
    </div>
  );
}

export function CommentThread({ comments }: { comments: Comment[] }) {
  if (!comments.length) {
    return (
      <p className='py-4 text-sm text-slate-500 italic'>No comments yet. Be the first!</p>
    );
  }
  return (
    <div className='divide-y divide-slate-100'>
      {comments.map((comment) => (
        <CommentItem key={comment.id} comment={comment} depth={0} />
      ))}
    </div>
  );
}
