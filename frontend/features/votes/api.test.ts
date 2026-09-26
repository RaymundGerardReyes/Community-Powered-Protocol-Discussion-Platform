import { describe, it, expect, vi, beforeEach } from 'vitest';
import { castVote } from './api';
import { apiClient } from '@/lib/api-client';

vi.mock('@/lib/api-client', () => ({
  apiClient: {
    post: vi.fn(),
  },
}));

describe('Votes API Integration', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('casts vote via /api/v1/votes with polymorphic votable_type', async () => {
    const mockVoteResponse = {
      action: 'created' as const,
      current_vote: 1,
      votes_count: 5,
    };

    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: { data: mockVoteResponse },
    });

    const result = await castVote({
      votable_type: 'thread',
      votable_id: 11,
      value: 1,
    });

    expect(apiClient.post).toHaveBeenCalledWith(
      '/api/v1/votes',
      {
        votable_type: 'thread',
        votable_id: 11,
        value: 1,
      }
    );
    expect(result).toEqual(mockVoteResponse);
  });
});
