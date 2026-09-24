'use client';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { castVote } from '../api';
import type { VoteResponse } from '@/types';

interface VotePayload {
  votable_type: 'App\\Models\\Thread' | 'App\\Models\\Comment';
  votable_id: number;
  value: 1 | -1;
  /** Query key to optimistically update, e.g. ['thread', 5] */
  queryKey?: unknown[];
}

export function useVote() {
  const queryClient = useQueryClient();

  return useMutation<VoteResponse, Error, VotePayload>({
    mutationFn: ({ votable_type, votable_id, value }) =>
      castVote({ votable_type, votable_id, value }),
    onMutate: async ({ queryKey, value }) => {
      if (!queryKey) return;
      await queryClient.cancelQueries({ queryKey });
      const snapshot = queryClient.getQueryData(queryKey);
      queryClient.setQueryData(queryKey, (old: Record<string, unknown> | undefined) => {
        if (!old) return old;
        const current = (old as { votes_count?: number }).votes_count ?? 0;
        return { ...old, votes_count: current + value };
      });
      return { snapshot };
    },
    onError: (_err, { queryKey }, context) => {
      if (queryKey && context?.snapshot) {
        queryClient.setQueryData(queryKey, context.snapshot);
      }
    },
    onSettled: (_data, _err, { queryKey }) => {
      if (queryKey) queryClient.invalidateQueries({ queryKey });
    },
  });
}
