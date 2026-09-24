'use client';
import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { createComment } from '../api';
import { Button } from '@/components/ui/Button';

interface CommentFormProps {
  threadId: number;
  parentId?: number | null;
  onSuccess?: () => void;
}

export function CommentForm({ threadId, parentId, onSuccess }: CommentFormProps) {
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
      onSubmit={(e) => { e.preventDefault(); if (body.trim()) mutation.mutate(); }}
      className='space-y-3'
    >
      <label htmlFor={`comment-${threadId}-${parentId ?? 'root'}`} className='sr-only'>
        {parentId ? 'Write a reply' : 'Write a comment'}
      </label>
      <textarea
        id={`comment-${threadId}-${parentId ?? 'root'}`}
        value={body}
        onChange={(e) => setBody(e.target.value)}
        placeholder={parentId ? 'Write a reply…' : 'Write a comment…'}
        rows={3}
        className='w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none'
        required
      />
      {mutation.isError && (
        <p className='text-xs text-red-600'>
          {(mutation.error as { message?: string })?.message ?? 'Failed to post comment.'}
        </p>
      )}
      <Button
        type='submit'
        size='sm'
        disabled={mutation.isPending || !body.trim()}
      >
        {mutation.isPending ? 'Posting…' : parentId ? 'Post Reply' : 'Post Comment'}
      </Button>
    </form>
  );
}
