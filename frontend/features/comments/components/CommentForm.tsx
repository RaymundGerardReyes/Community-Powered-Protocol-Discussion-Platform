'use client';

import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { createComment } from '../api';
import { Button } from '@/components/ui/Button';
import { useAuth } from '@/features/auth/AuthContext';

interface CommentFormProps {
  threadId: number;
  parentId?: number | null;
  onSuccess?: () => void;
  onCancel?: () => void;
}

export function CommentForm({ threadId, parentId, onSuccess, onCancel }: CommentFormProps) {
  const { user, openAuthModal } = useAuth();
  const [body, setBody] = useState('');
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: () => createComment({ thread_id: threadId, content: body, body, parent_id: parentId }),
    onSuccess: () => {
      setBody('');
      queryClient.invalidateQueries({ queryKey: ['thread', threadId] });
      onSuccess?.();
    },
  });

  if (!user) {
    return (
      <div
        className="rounded-lg p-4 text-center space-y-2"
        style={{
          background: 'var(--surface-muted)',
          border: '1px dashed var(--border)',
        }}
      >
        <p className="text-xs font-medium" style={{ color: 'var(--text-secondary)' }}>
          You must be signed in to join this protocol discussion.
        </p>
        <Button
          type="button"
          variant="primary"
          size="sm"
          onClick={openAuthModal}
          className="text-xs"
        >
          Sign In / Demo Accounts
        </Button>
      </div>
    );
  }

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        if (body.trim()) mutation.mutate();
      }}
      className="space-y-3"
    >
      <div className="flex items-center justify-between text-[0.72rem]" style={{ color: 'var(--text-muted)' }}>
        <span>
          Posting as <strong style={{ color: 'var(--text-primary)' }}>{user.name}</strong>
        </span>
      </div>

      <label htmlFor={`comment-${threadId}-${parentId ?? 'root'}`} className="sr-only">
        {parentId ? 'Write a reply' : 'Write a comment'}
      </label>
      <div className="relative">
        <textarea
          id={`comment-${threadId}-${parentId ?? 'root'}`}
          value={body}
          onChange={(e) => setBody(e.target.value)}
          placeholder={parentId ? 'Write your reply…' : 'Share your thoughts on this protocol discussion…'}
          rows={parentId ? 2 : 3}
          className="input resize-none w-full text-sm leading-relaxed"
          style={{
            background: 'var(--surface-card)',
            borderColor: 'var(--border)',
            color: 'var(--text-primary)',
          }}
          required
        />
      </div>

      {mutation.isError && (
        <div
          className="rounded-lg p-2.5 text-xs"
          style={{
            background: 'var(--danger-bg)',
            border: '1px solid #fecaca',
            color: 'var(--danger-text)',
          }}
          role="alert"
        >
          {(mutation.error as { message?: string })?.message ?? 'Failed to post comment. Please try again.'}
        </div>
      )}

      <div className="flex items-center justify-between gap-2">
        <span className="text-[0.7rem]" style={{ color: 'var(--text-muted)' }}>
          Supports plain text and markdown
        </span>
        <div className="flex items-center gap-2">
          {onCancel && (
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={onCancel}
              disabled={mutation.isPending}
            >
              Cancel
            </Button>
          )}
          <Button
            type="submit"
            variant="primary"
            size="sm"
            disabled={mutation.isPending || !body.trim()}
          >
            {mutation.isPending ? 'Posting…' : parentId ? 'Post Reply' : 'Post Comment'}
          </Button>
        </div>
      </div>
    </form>
  );
}
