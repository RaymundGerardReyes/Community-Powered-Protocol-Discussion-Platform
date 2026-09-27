'use client';

import { useComments } from '../hooks/useComments';
import { countTotalComments } from '../lib/tree';
import { CommentForm } from './CommentForm';
import { CommentThread } from './CommentThread';
import type { Comment } from '@/types';

export interface ThreadDiscussionProps {
  threadId: number;
  initialComments: Comment[];
  initialCount?: number;
}

export function ThreadDiscussion({
  threadId,
  initialComments,
  initialCount = 0,
}: ThreadDiscussionProps) {
  const { data: comments = initialComments } = useComments(threadId, initialComments);

  // Compute live count from active comment tree or fallback to initial server count
  const liveCount = comments.length > 0 ? countTotalComments(comments) : initialCount;

  return (
    <section>
      {/* Section header with live comment count badge */}
      <div className="flex items-center gap-2 mb-5">
        <h2 className="section-title">Discussion</h2>
        <span
          className="badge badge-info"
          style={{ borderRadius: '999px' }}
        >
          {liveCount}
        </span>
      </div>

      {/* Root comment box */}
      <div className="card-flat p-5 mb-6">
        <h3
          className="text-xs font-semibold uppercase tracking-wider mb-3"
          style={{ color: 'var(--text-muted)' }}
        >
          Leave a response
        </h3>
        <CommentForm threadId={threadId} />
      </div>

      {/* Dynamic recursive comment tree */}
      <CommentThread comments={comments} threadId={threadId} />
    </section>
  );
}
