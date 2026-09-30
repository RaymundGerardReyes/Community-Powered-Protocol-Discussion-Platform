'use client';

import { useQuery } from '@tanstack/react-query';
import { useAuth } from '@/features/auth/AuthContext';
import { fetchMyVotes, type UserVotesMap } from '../api';

export function useUserVotes() {
  const { user } = useAuth();

  return useQuery<UserVotesMap>({
    queryKey: ['user-votes', user?.id],
    queryFn: async () => {
      const res = await fetchMyVotes();
      return res ?? { protocol: {}, thread: {}, comment: {} };
    },
    enabled: !!user,
    staleTime: 1000 * 60 * 5, // 5 minutes fresh
  });
}
