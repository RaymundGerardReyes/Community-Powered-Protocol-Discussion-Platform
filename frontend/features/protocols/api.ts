import { cache } from 'react';
import { apiClient } from '@/lib/api-client';
import type { PaginatedResponse, Protocol, Review, Thread } from '@/types';

export interface ProtocolFilters {
  status?: string;
  category?: string;
  search?: string;
  sort?: 'latest' | 'top' | 'rating';
  page?: string;
}

export async function fetchProtocols(
  filters?: ProtocolFilters,
): Promise<PaginatedResponse<Protocol>> {
  const { data } = await apiClient.get<PaginatedResponse<Protocol>>(
    '/api/v1/protocols',
    { params: filters },
  );
  return data;
}

export const fetchProtocol = cache(async (slug: string): Promise<Protocol> => {
  const { data } = await apiClient.get<{ data: Protocol }>(
    `/api/v1/protocols/${slug}`,
  );
  return data.data;
});


export async function fetchProtocolThreads(
  protocolId: number,
  params?: { page?: string },
): Promise<PaginatedResponse<Thread>> {
  const { data } = await apiClient.get<PaginatedResponse<Thread>>(
    `/api/v1/protocols/${protocolId}/threads`,
    { params },
  );
  return data;
}

export async function fetchProtocolReviews(
  protocolId: number,
  params?: { page?: string },
): Promise<PaginatedResponse<Review>> {
  const { data } = await apiClient.get<PaginatedResponse<Review>>(
    `/api/v1/protocols/${protocolId}/reviews`,
    { params },
  );
  return data;
}

export async function createProtocol(
  payload: Omit<Protocol, 'id' | 'slug' | 'score' | 'votes_count' | 'average_rating' | 'reviews_count' | 'author' | 'created_at' | 'updated_at'>,
): Promise<Protocol> {
  const { data } = await apiClient.post<{ data: Protocol }>(
    '/api/v1/protocols',
    payload,
  );
  return data.data;
}
