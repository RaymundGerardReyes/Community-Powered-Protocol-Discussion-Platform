import { describe, it, expect, vi, beforeEach } from 'vitest';
import { fetchThread, createThread } from '@/features/threads/api';
import { apiClient } from '@/lib/api-client';

describe('Threads API Service', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('fetchThread retrieves thread by id', async () => {
    const mockThread = { id: 10, title: 'Consensus Debate' };
    vi.spyOn(apiClient, 'get').mockResolvedValueOnce({
      data: { data: mockThread },
    });

    const result = await fetchThread(10);

    expect(apiClient.get).toHaveBeenCalledWith('/api/v1/threads/10');
    expect(result).toEqual(mockThread);
  });

  it('createThread posts new thread payload to /api/v1/threads', async () => {
    const payload = {
      protocol_id: 1,
      title: 'State Sync Latency',
      body: 'Can we optimize batch proofs?',
    };
    const createdThread = { id: 11, ...payload, comments_count: 0, views_count: 0 };
    vi.spyOn(apiClient, 'post').mockResolvedValueOnce({
      data: { data: createdThread },
    });

    const result = await createThread(payload);

    expect(apiClient.post).toHaveBeenCalledWith('/api/v1/protocols/1/threads', {
      title: 'State Sync Latency',
      body: 'Can we optimize batch proofs?',
      content: 'Can we optimize batch proofs?',
    });
    expect(result).toEqual(createdThread);
  });
});
