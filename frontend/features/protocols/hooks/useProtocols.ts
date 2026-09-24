import { useQuery } from "@tanstack/react-query";
import { fetchProtocols } from "../api";

export function useProtocols(filters?: Record<string, string>) {
  return useQuery({
    queryKey: ["protocols", filters],
    queryFn: () => fetchProtocols(filters),
  });
}
