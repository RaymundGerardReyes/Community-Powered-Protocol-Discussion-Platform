import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { useVote } from './useVote';
import * as voteApi from '../api';

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  });

  const Wrapper = ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );

  return { queryClient, Wrapper };
}

describe('useVote Hook Integration', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('optimistically increments vote count and updates query cache', async () => {
    const { queryClient, Wrapper } = createWrapper();
    const queryKey = ['thread', 1];

    queryClient.setQueryData(queryKey, { id: 1, title: 'Test Thread', votes_count: 5 });

    vi.spyOn(voteApi, 'castVote').mockResolvedValueOnce({
      action: 'created',
      current_vote: 1,
      votes_count: 6,
    });

    const { result } = renderHook(() => useVote(), { wrapper: Wrapper });

    result.current.mutate({
      votable_type: 'thread',
      votable_id: 1,
      value: 1,
      queryKey,
    });

    // Check optimistic update in cache once onMutate has executed
    await waitFor(() => {
      const optimisticData = queryClient.getQueryData<{ votes_count: number }>(queryKey);
      expect(optimisticData?.votes_count).toBe(6);
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
  });

  it('rolls back to previous cache snapshot if vote mutation fails', async () => {
    const { queryClient, Wrapper } = createWrapper();
    const queryKey = ['thread', 2];

    queryClient.setQueryData(queryKey, { id: 2, title: 'Failing Thread', votes_count: 10 });

    vi.spyOn(voteApi, 'castVote').mockRejectedValueOnce(new Error('Network failure'));

    const { result } = renderHook(() => useVote(), { wrapper: Wrapper });

    result.current.mutate({
      votable_type: 'thread',
      votable_id: 2,
      value: -1,
      queryKey,
    });

    // Wait for mutation failure
    await waitFor(() => expect(result.current.isError).toBe(true));

    // Verify cache rolled back to 10
    const rolledBackData = queryClient.getQueryData<{ votes_count: number }>(queryKey);
    expect(rolledBackData?.votes_count).toBe(10);
  });
});
