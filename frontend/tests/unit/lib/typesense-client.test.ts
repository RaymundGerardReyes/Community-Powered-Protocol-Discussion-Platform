import { describe, it, expect } from 'vitest';
import { typesenseClient, searchProtocols } from '@/lib/typesense-client';

describe('typesense-client', () => {
  it('gracefully returns null if Typesense search key is not configured', async () => {
    if (!typesenseClient) {
      const result = await searchProtocols('zk-rollup');
      expect(result).toBeNull();
    } else {
      expect(typesenseClient).toBeDefined();
    }
  });
});
