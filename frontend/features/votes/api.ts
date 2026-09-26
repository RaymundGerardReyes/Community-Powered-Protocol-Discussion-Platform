import { apiClient } from '@/lib/api-client';
import type { VoteResponse } from '@/types';

export type VotableType =
  | 'protocol'
  | 'thread'
  | 'comment'
  | 'App\\Models\\Protocol'
  | 'App\\Models\\Thread'
  | 'App\\Models\\Comment'
  | string;

export async function castVote(payload: {
  votable_type: VotableType;
  votable_id: number;
  value: 1 | -1;
}): Promise<VoteResponse> {
  const { data } = await apiClient.post<{ data: VoteResponse }>(
    '/api/v1/votes',
    payload,
  );
  return data.data;
}
