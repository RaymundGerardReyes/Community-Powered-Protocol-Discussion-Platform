'use client';
import { useState } from 'react';
import { cn } from '@/lib/cn';
import { VoteButton } from '@/features/votes/components/VoteButton';
import { CommentForm } from './CommentForm';
import type { Comment } from '@/types';

const MAX_VISUAL_DEPTH = 12;

function formatCommentTimestamp(dateStr: string): string {
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  const datePart = date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
  const timePart = date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
  });
  return `${datePart} • ${timePart}`;
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
      className="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[0.6rem] font-semibold text-white"
      style={{ background: `hsl(${hue}, 60%, 45%)` }}
      aria-hidden
    >
      {initials}
    </span>
  );
}

interface CommentItemProps {
  comment: Comment;
  depth: number;
}

function CommentItem({ comment, depth }: CommentItemProps) {
  const [collapsed, setCollapsed] = useState(false);
  const [isReplying, setIsReplying] = useState(false);

  const hasReplies = (comment.replies?.length ?? 0) > 0;
  const effectiveDepth = Math.min(depth, MAX_VISUAL_DEPTH);

  return (
    <div
      className={cn(
        'relative transition-all',
        depth > 0 && 'comment-indent mt-3',
      )}
    >
      <div
        className="rounded-lg p-3 transition-colors"
        style={{ background: 'var(--surface-card)', border: '1px solid var(--border)' }}
      >
        {/* Header: Author + Timestamp + Collapse */}
        <div className="flex items-center justify-between gap-2 text-xs">
          <div className="flex items-center gap-2 min-w-0">
            <AuthorAvatar name={comment.author?.name ?? 'Anonymous'} />
            <span
              className="font-medium truncate"
              style={{ color: 'var(--text-primary)' }}
            >
              {comment.author?.name ?? 'Anonymous'}
            </span>
            <span style={{ color: 'var(--text-muted)' }}>·</span>
            <time
              style={{ color: 'var(--text-muted)' }}
              dateTime={comment.created_at}
            >
              {formatCommentTimestamp(comment.created_at)}
            </time>
          </div>

          {hasReplies && (
            <button
              onClick={() => setCollapsed((c) => !c)}
              className="text-[0.7rem] font-medium hover:underline transition-colors"
              style={{ color: 'var(--brand)' }}
            >
              {collapsed
                ? `+ Show ${comment.replies!.length} ${comment.replies!.length === 1 ? 'reply' : 'replies'}`
                : '− Collapse'}
            </button>
          )}
        </div>

        {/* Comment Body */}
        {!collapsed && (
          <>
            <p
              className="mt-2 text-xs leading-relaxed whitespace-pre-wrap"
              style={{ color: 'var(--text-secondary)' }}
            >
              {comment.content ?? comment.body}
            </p>

            {/* Actions: Vote + Reply */}
            <div className="mt-3 flex items-center gap-4 text-xs">
              <VoteButton
                votableType="comment"
                votableId={comment.id}
                currentVote={null}
                count={comment.votes_count ?? 0}
                queryKey={['thread', comment.thread_id]}
                layout="horizontal"
              />

              {depth < MAX_VISUAL_DEPTH && (
                <button
                  onClick={() => setIsReplying((r) => !r)}
                  className="flex items-center gap-1 font-medium transition-colors hover:text-indigo-600"
                  style={{ color: isReplying ? 'var(--brand)' : 'var(--text-muted)' }}
                >
                  <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                  </svg>
                  <span>{isReplying ? 'Cancel' : 'Reply'}</span>
                </button>
              )}
            </div>

            {/* Inline reply form */}
            {isReplying && (
              <div
                className="mt-3 pt-3"
                style={{ borderTop: '1px solid var(--surface-overlay)' }}
              >
                <CommentForm
                  threadId={comment.thread_id}
                  parentId={comment.id}
                  onSuccess={() => setIsReplying(false)}
                  onCancel={() => setIsReplying(false)}
                />
              </div>
            )}
          </>
        )}
      </div>

      {/* Render child replies recursively */}
      {!collapsed && hasReplies && (
        <div className="space-y-2 mt-2">
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
      <div
        className="rounded-xl p-8 text-center"
        style={{
          background: 'var(--surface-card)',
          border: '1px solid var(--surface-overlay)',
          color: 'var(--text-muted)',
        }}
      >
        <p className="text-sm italic">
          No comments yet. Start the conversation!
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {comments.map((comment) => (
        <CommentItem key={comment.id} comment={comment} depth={0} />
      ))}
    </div>
  );
}
