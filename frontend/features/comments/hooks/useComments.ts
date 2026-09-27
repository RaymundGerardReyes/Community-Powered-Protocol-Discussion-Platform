'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchComments } from '../api';
import type { Comment } from '@/types';

export function useComments(threadId?: number | string, initialComments?: Comment[]) {
  const numericId = threadId !== undefined && threadId !== null ? Number(threadId) : undefined;

  return useQuery<Comment[]>({
    queryKey: ['comments', numericId],
    queryFn: () => fetchComments(numericId!),
    initialData: initialComments,
    enabled: numericId !== undefined && !isNaN(numericId),
    staleTime: 1000 * 30, // 30 seconds
  });
}
