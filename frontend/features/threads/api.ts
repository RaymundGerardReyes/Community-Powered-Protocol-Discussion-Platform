import { apiClient } from '@/lib/api-client';
import type { PaginatedResponse, Thread } from '@/types';

export async function fetchThread(id: number | string): Promise<Thread> {
  const { data } = await apiClient.get<{ data: Thread }>(
    `/api/v1/threads/${id}`,
  );
  return data.data;
}

export async function createThread(payload: {
  protocol_id: number;
  title: string;
  body: string;
}): Promise<Thread> {
  const { data } = await apiClient.post<{ data: Thread }>(
    '/api/v1/threads',
    payload,
  );
  return data.data;
}
