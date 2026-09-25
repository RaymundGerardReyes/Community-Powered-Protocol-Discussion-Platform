'use client';
import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { createComment } from '../api';
import { Button } from '@/components/ui/Button';

interface CommentFormProps {
  threadId: number;
  parentId?: number | null;
  onSuccess?: () => void;
  onCancel?: () => void;
}

export function CommentForm({ threadId, parentId, onSuccess, onCancel }: CommentFormProps) {
  const [body, setBody] = useState('');
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: () => createComment({ thread_id: threadId, body, parent_id: parentId }),
    onSuccess: () => {
      setBody('');
      queryClient.invalidateQueries({ queryKey: ['thread', threadId] });
      onSuccess?.();
    },
  });

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        if (body.trim()) mutation.mutate();
      }}
      className="space-y-3"
    >
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
            background: 'rgba(239, 68, 68, 0.1)',
            border: '1px solid rgba(239, 68, 68, 0.25)',
            color: '#f87171',
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
