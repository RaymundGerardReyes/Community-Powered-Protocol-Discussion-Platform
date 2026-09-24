import { apiClient } from '@/lib/api-client';
import type { VoteResponse } from '@/types';

export async function castVote(payload: {
  votable_type: 'App\\Models\\Thread' | 'App\\Models\\Comment';
  votable_id: number;
  value: 1 | -1;
}): Promise<VoteResponse> {
  const { data } = await apiClient.post<{ data: VoteResponse }>(
    '/api/v1/votes',
    payload,
  );
  return data.data;
}
