import { apiClient } from '@/lib/api-client';
import type { Comment } from '@/types';

export async function createComment(payload: {
  thread_id: number;
  body: string;
  parent_id?: number | null;
}): Promise<Comment> {
  const { data } = await apiClient.post<{ data: Comment }>(
    '/api/v1/comments',
    payload,
  );
  return data.data;
}

export async function deleteComment(id: number): Promise<void> {
  await apiClient.delete(`/api/v1/comments/${id}`);
}
