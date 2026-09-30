import { cache } from 'react';
import { apiClient } from '@/lib/api-client';
import type { Thread } from '@/types';

export const fetchThread = cache(async (id: number | string): Promise<Thread> => {
  const { data } = await apiClient.get<{ data: Thread }>(
    `/api/v1/threads/${id}`,
  );
  return data.data;
});


export async function createThread(payload: {
  protocol_id: number;
  title: string;
  body?: string;
  content?: string;
}): Promise<Thread> {
  const { data } = await apiClient.post<{ data: Thread }>(
    `/api/v1/protocols/${payload.protocol_id}/threads`,
    {
      title: payload.title,
      body: payload.body ?? payload.content,
      content: payload.content ?? payload.body,
    },
  );
  return data.data;
}
