import { apiClient } from '@/lib/api-client';
import type { Review } from '@/types';

export async function createReview(payload: {
  protocol_id: number;
  rating: number;
  feedback?: string;
  summary?: string;
  verdict?: 'approved' | 'changes_requested' | 'rejected';
}): Promise<Review> {
  const { data } = await apiClient.post<{ data: Review }>(
    `/api/v1/protocols/${payload.protocol_id}/reviews`,
    {
      rating: payload.rating,
      feedback: payload.feedback ?? payload.summary,
      summary: payload.summary ?? payload.feedback,
      verdict: payload.verdict,
    },
  );
  return data.data;
}
