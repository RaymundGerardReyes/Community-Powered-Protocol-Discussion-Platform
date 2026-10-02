'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchCategories, type CategoryOption } from '../api';

export function useCategories() {
  return useQuery<CategoryOption[]>({
    queryKey: ['protocol-categories'],
    queryFn: async () => {
      try {
        const res = await fetchCategories();
        return Array.isArray(res) ? res : [];
      } catch {
        return [];
      }
    },
    staleTime: 1000 * 60 * 10, // 10 minutes fresh
  });
}

