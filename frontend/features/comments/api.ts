import { apiClient } from '@/lib/api-client';
import type { Comment } from '@/types';

export interface CreateCommentPayload {
  thread_id: number;
  content?: string;
  body?: string;
  parent_id?: number | null;
}

export async function createComment(payload: CreateCommentPayload): Promise<Comment> {
  const content = payload.content ?? payload.body ?? '';
  const { data } = await apiClient.post<{ data: Comment }>(
    `/api/v1/threads/${payload.thread_id}/comments`,
    {
      content,
      parent_id: payload.parent_id,
    },
  );
  return data.data;
}

export async function deleteComment(id: number): Promise<void> {
  await apiClient.delete(`/api/v1/comments/${id}`);
}
