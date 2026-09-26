import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createComment, deleteComment } from './api';
import { apiClient } from '@/lib/api-client';

vi.mock('@/lib/api-client', () => ({
  apiClient: {
    post: vi.fn(),
    delete: vi.fn(),
  },
}));

describe('Comments API Integration', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('posts comment to /api/v1/threads/{thread_id}/comments with content', async () => {
    const mockComment = {
      id: 15,
      thread_id: 3,
      parent_id: null,
      content: 'Discussion on cross-chain fraud proofs',
      created_at: '2026-09-26T00:00:00Z',
      updated_at: '2026-09-26T00:00:00Z',
    };

    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: { data: mockComment },
    });

    const result = await createComment({
      thread_id: 3,
      content: 'Discussion on cross-chain fraud proofs',
      parent_id: null,
    });

    expect(apiClient.post).toHaveBeenCalledWith(
      '/api/v1/threads/3/comments',
      {
        content: 'Discussion on cross-chain fraud proofs',
        parent_id: null,
      }
    );
    expect(result).toEqual(mockComment);
  });

  it('deletes comment via /api/v1/comments/{id}', async () => {
    vi.mocked(apiClient.delete).mockResolvedValueOnce({
      data: { message: 'Comment deleted successfully' },
    });

    await deleteComment(15);

    expect(apiClient.delete).toHaveBeenCalledWith('/api/v1/comments/15');
  });
});
