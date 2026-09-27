import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createReview } from './api';
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
      verdict: 'approved',
      feedback: 'Excellent security considerations.',
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };

    vi.spyOn(apiClient, 'post').mockResolvedValueOnce({
      data: { data: mockCreatedReview },
    });

    const result = await createReview(payload);

    expect(apiClient.post).toHaveBeenCalledWith('/api/v1/reviews', payload);
    expect(result).toEqual(mockCreatedReview);
  });
});
