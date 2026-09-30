import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createReview } from '@/features/reviews/api';
import { apiClient } from '@/lib/api-client';

describe('Reviews API Service', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('createReview sends rating and feedback to POST /api/v1/reviews', async () => {
    const payload = {
      protocol_id: 1,
      rating: 5,
      feedback: 'Excellent security considerations.',
    };
    const mockCreatedReview = {
      id: 99,
      protocol_id: 1,
      rating: 5,
      feedback: 'Excellent security considerations.',
      author: {
        id: 1,
        name: 'Vitalik B.',
        email: 'vitalik@protocol.io',
        created_at: '2026-09-01T00:00:00Z',
      },
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };

    vi.spyOn(apiClient, 'post').mockResolvedValueOnce({
      data: { data: mockCreatedReview },
    });

    const result = await createReview(payload);

    expect(apiClient.post).toHaveBeenCalledWith('/api/v1/protocols/1/reviews', {
      rating: 5,
      feedback: 'Excellent security considerations.',
      summary: 'Excellent security considerations.',
      verdict: undefined,
    });
    expect(result).toEqual(mockCreatedReview);
  });
});
