'use client';
import { CommentForm } from './CommentForm';

export function CommentFormWrapper({ threadId }: { threadId: number }) {
  return <CommentForm threadId={threadId} />;
}
