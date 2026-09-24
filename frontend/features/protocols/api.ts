import { apiClient } from "@/lib/api-client";
import type { Protocol } from "@/types";

export async function fetchProtocols(params?: Record<string, string>) {
  const { data } = await apiClient.get<{ data: Protocol[] }>("/api/v1/protocols", { params });
  return data.data;
}

export async function fetchProtocol(id: number | string) {
  const { data } = await apiClient.get<{ data: Protocol }>(`/api/v1/protocols/${id}`);
  return data.data;
}
