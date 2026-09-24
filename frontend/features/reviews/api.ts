import { apiClient } from '@/lib/api-client';
import type { Review } from '@/types';

export async function createReview(payload: {
  protocol_id: number;
  rating: number;
  feedback?: string;
}): Promise<Review> {
  const { data } = await apiClient.post<{ data: Review }>(
    '/api/v1/reviews',
    payload,
  );
  return data.data;
}
