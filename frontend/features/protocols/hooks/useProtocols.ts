import { useQuery } from '@tanstack/react-query';
import { fetchProtocols, type ProtocolFilters } from '../api';

export function useProtocols(filters?: ProtocolFilters) {
  return useQuery({
    queryKey: ['protocols', filters],
    queryFn: () => fetchProtocols(filters),
    placeholderData: (prev) => prev, // keep previous data while fetching
    staleTime: 30_000,
    refetchOnWindowFocus: false,
  });
}
