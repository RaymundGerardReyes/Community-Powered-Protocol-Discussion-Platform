import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createComment, deleteComment } from '@/features/comments/api';
import { apiClient } from '@/lib/api-client';

describe('Comments API Service', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('createComment sends nested POST to /api/v1/threads/{thread_id}/comments with content body', async () => {
    const payload = {
      thread_id: 5,
      content: 'New feedback on thread',
      parent_id: 1,
    };
    const mockCreatedComment = {
      id: 3,
      thread_id: 5,
      parent_id: 1,
      content: 'New feedback on thread',
    };
    vi.spyOn(apiClient, 'post').mockResolvedValueOnce({
      data: { data: mockCreatedComment },
    });

    const result = await createComment(payload);

    expect(apiClient.post).toHaveBeenCalledWith(
      '/api/v1/threads/5/comments',
      {
        content: 'New feedback on thread',
        parent_id: 1,
      },
    );
    expect(result).toEqual(mockCreatedComment);
  });

  it('deleteComment sends DELETE request to /api/v1/comments/{id}', async () => {
    vi.spyOn(apiClient, 'delete').mockResolvedValueOnce({ data: null });

    await deleteComment(42);

    expect(apiClient.delete).toHaveBeenCalledWith('/api/v1/comments/42');
  });
});
