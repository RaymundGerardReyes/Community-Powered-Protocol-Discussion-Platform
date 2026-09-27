import { describe, it, expect, vi, beforeEach } from 'vitest';
import { castVote } from '@/features/votes/api';
import { apiClient } from '@/lib/api-client';

describe('Votes API Service', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('castVote posts payload to /api/v1/votes and unwraps response data', async () => {
    const payload = {
      votable_type: 'protocol' as const,
      votable_id: 1,
      value: 1 as const,
    };
    const mockVoteResponse = {
      action: 'created' as const,
      current_vote: 1 as const,
      votes_count: 43,
    };
    const mockResponse = {
      data: {
        data: mockVoteResponse,
      },
    };

    vi.spyOn(apiClient, 'post').mockResolvedValueOnce(mockResponse);

    const result = await castVote(payload);

    expect(apiClient.post).toHaveBeenCalledWith('/api/v1/votes', payload);
    expect(result).toEqual(mockVoteResponse);
  });
});
