import { describe, it, expect } from 'vitest';
import {
  typesenseClient,
  searchProtocols,
  resolveTypesenseConfig,
} from '@/lib/typesense-client';

describe('typesense-client', () => {
  it('gracefully returns null if Typesense search key is not configured', async () => {
    if (!typesenseClient) {
      const result = await searchProtocols('zk-rollup');
      expect(result).toBeNull();
    } else {
      expect(typesenseClient).toBeDefined();
    }
  });

  describe('resolveTypesenseConfig', () => {
    it('defaults to localhost HTTP port 8108 when unconfigured', () => {
      const config = resolveTypesenseConfig({});
      expect(config.host).toBe('localhost');
      expect(config.port).toBe(8108);
      expect(config.protocol).toBe('http');
      expect(config.isValid).toBe(false);
    });

    it('sanitizes Typesense Cloud URL and auto-detects HTTPS port 443', () => {
      const config = resolveTypesenseConfig({
        host: 'https://cluster-xyz.a1.typesense.net/',
        apiKey: 'search-only-key-123',
      });
      expect(config.host).toBe('cluster-xyz.a1.typesense.net');
      expect(config.port).toBe(443);
      expect(config.protocol).toBe('https');
      expect(config.isValid).toBe(true);
      expect(config.apiKey).toBe('search-only-key-123');
    });

    it('sanitizes host with leading http:// protocol', () => {
      const config = resolveTypesenseConfig({
        host: 'http://127.0.0.1:8108/',
        port: 8108,
        protocol: 'http',
        apiKey: 'local-key',
      });
      expect(config.host).toBe('127.0.0.1:8108');
      expect(config.port).toBe(8108);
      expect(config.protocol).toBe('http');
      expect(config.isValid).toBe(true);
    });

    it('respects explicit protocol and port overrides for cloud instances', () => {
      const config = resolveTypesenseConfig({
        host: 'custom-search.internal',
        port: '443',
        protocol: 'https',
        apiKey: 'key-abc',
      });
      expect(config.host).toBe('custom-search.internal');
      expect(config.port).toBe(443);
      expect(config.protocol).toBe('https');
      expect(config.isValid).toBe(true);
    });
  });
});
