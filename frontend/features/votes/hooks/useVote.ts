'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { castVote, type VotableType, type UserVotesMap } from '../api';
import type { Comment, VoteResponse } from '@/types';

interface VotePayload {
  votable_type: VotableType;
  votable_id: number;
  value: 1 | -1;
  /** Query key to optimistically update, e.g. ['thread', 5] or ['comments', 3] */
  queryKey?: unknown[];
}

interface VoteContext {
  snapshot?: unknown;
  userVotesSnapshot?: unknown;
}

export function useVote() {
  const queryClient = useQueryClient();

  return useMutation<VoteResponse, Error, VotePayload, VoteContext>({
    mutationFn: ({ votable_type, votable_id, value }) =>
      castVote({ votable_type, votable_id, value }),

    onMutate: async ({ votable_type, votable_id, value, queryKey }) => {
      // 1. Cancel active queries that might overwrite our optimistic update
      if (queryKey) {
        await queryClient.cancelQueries({ queryKey });
      }
      await queryClient.cancelQueries({ queryKey: ['user-votes'] });

      const snapshot = queryKey ? queryClient.getQueryData(queryKey) : undefined;
      const userVotesSnapshot = queryClient.getQueriesData({ queryKey: ['user-votes'] });

      const normalizedType = votable_type
        .replace(/^App\\Models\\/i, '')
        .toLowerCase() as keyof UserVotesMap;

      // 2. Optimistically update user-votes map
      queryClient.setQueriesData<UserVotesMap>({ queryKey: ['user-votes'] }, (old) => {
        if (!old || !old[normalizedType]) return old;
        const currentVal = old[normalizedType][String(votable_id)];
        const nextSub = { ...old[normalizedType] };

        if (currentVal === value) {
          delete nextSub[String(votable_id)];
        } else {
          nextSub[String(votable_id)] = value;
        }

        return {
          ...old,
          [normalizedType]: nextSub,
        };
      });

      // 3. Optimistically update queryKey target data
      if (queryKey) {
        queryClient.setQueryData(queryKey, (old: unknown) => {
          if (!old) return old;

          // If the cached query is an array of comments
          if (Array.isArray(old)) {
            const updateComments = (list: Comment[]): Comment[] =>
              list.map((c) => {
                if (c.id === votable_id) {
                  return { ...c, votes_count: (c.votes_count ?? 0) + value };
                }
                if (c.replies?.length) {
                  return { ...c, replies: updateComments(c.replies) };
                }
                return c;
              });

            return updateComments(old as Comment[]);
          }

          // If the cached query is an entity object with votes_count
          if (typeof old === 'object' && old !== null && 'votes_count' in old) {
            const current = (old as { votes_count?: number }).votes_count ?? 0;
            return { ...old, votes_count: current + value };
          }

          return old;
        });
      }

      return { snapshot, userVotesSnapshot };
    },

    onError: (_err, { queryKey }, context) => {
      if (queryKey && context?.snapshot !== undefined) {
        queryClient.setQueryData(queryKey, context.snapshot);
      }
      if (context?.userVotesSnapshot) {
        const entries = context.userVotesSnapshot as [unknown[], unknown][];
        entries.forEach(([key, val]) => queryClient.setQueryData(key, val));
      }
    },

    onSettled: (_data, _err, { queryKey }) => {
      queryClient.invalidateQueries({ queryKey: ['user-votes'] });
      if (queryKey) {
        queryClient.invalidateQueries({ queryKey });
      }
    },
  });
}
