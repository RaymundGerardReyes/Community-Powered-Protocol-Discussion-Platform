import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  fetchProtocols,
  fetchProtocol,
  fetchProtocolThreads,
  fetchProtocolReviews,
  createProtocol,
} from '@/features/protocols/api';
import { apiClient } from '@/lib/api-client';

describe('Protocols API Service', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('fetchProtocols passes query parameters to apiClient.get', async () => {
    const mockData = {
      data: [{ id: 1, title: 'Protocol 1', slug: 'protocol-1' }],
      meta: { current_page: 1, last_page: 1, total: 1, per_page: 15 },
    };
    vi.spyOn(apiClient, 'get').mockResolvedValueOnce({ data: mockData });

    const result = await fetchProtocols({ sort: 'top', category: 'defi' });

    expect(apiClient.get).toHaveBeenCalledWith('/api/v1/protocols', {
      params: { sort: 'top', category: 'defi' },
    });
    expect(result).toEqual(mockData);
  });

  it('fetchProtocol retrieves protocol by slug or ID', async () => {
    const mockProtocol = { id: 1, slug: 'zk-rollup', title: 'ZK Rollup' };
    vi.spyOn(apiClient, 'get').mockResolvedValueOnce({
      data: { data: mockProtocol },
    });

    const result = await fetchProtocol('zk-rollup');

    expect(apiClient.get).toHaveBeenCalledWith('/api/v1/protocols/zk-rollup');
    expect(result).toEqual(mockProtocol);
  });

  it('fetchProtocolThreads retrieves paginated threads for protocol', async () => {
    const mockThreads = {
      data: [{ id: 10, title: 'Discussion 1' }],
      meta: { current_page: 1, last_page: 1, total: 1, per_page: 10 },
    };
    vi.spyOn(apiClient, 'get').mockResolvedValueOnce({ data: mockThreads });

    const result = await fetchProtocolThreads(1, { page: '2' });

    expect(apiClient.get).toHaveBeenCalledWith('/api/v1/protocols/1/threads', {
      params: { page: '2' },
    });
    expect(result).toEqual(mockThreads);
  });

  it('fetchProtocolReviews retrieves paginated reviews for protocol', async () => {
    const mockReviews = {
      data: [{ id: 5, rating: 5, feedback: 'Great!' }],
      meta: { current_page: 1, last_page: 1, total: 1, per_page: 10 },
    };
    vi.spyOn(apiClient, 'get').mockResolvedValueOnce({ data: mockReviews });

    const result = await fetchProtocolReviews(1);

    expect(apiClient.get).toHaveBeenCalledWith('/api/v1/protocols/1/reviews', {
      params: undefined,
    });
    expect(result).toEqual(mockReviews);
  });

  it('createProtocol sends payload via POST /api/v1/protocols', async () => {
    const newProtocol = {
      title: 'Decentralized Sequencer',
      description: 'L2 sequencing specification',
      status: 'draft' as const,
      category: 'layer2' as const,
      version: '1.0.0',
      metadata: null,
    };
    const createdProtocol = { id: 2, ...newProtocol, slug: 'decentralized-sequencer' };
    vi.spyOn(apiClient, 'post').mockResolvedValueOnce({
      data: { data: createdProtocol },
    });

    const result = await createProtocol(newProtocol);

    expect(apiClient.post).toHaveBeenCalledWith('/api/v1/protocols', newProtocol);
    expect(result).toEqual(createdProtocol);
  });
});
