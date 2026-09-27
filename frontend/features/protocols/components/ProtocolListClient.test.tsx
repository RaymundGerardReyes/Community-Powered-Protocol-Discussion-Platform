import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { ProtocolListClient } from './ProtocolListClient';
import * as useProtocolsHook from '../hooks/useProtocols';

vi.mock('next/navigation', () => ({
  useSearchParams: () => new URLSearchParams(),
}));

function renderWithClient(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>);
}

describe('ProtocolListClient Component Integration', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('renders loading spinner when fetching protocols', () => {
    vi.spyOn(useProtocolsHook, 'useProtocols').mockReturnValue({
      isLoading: true,
      isError: false,
      data: undefined,
      error: null,
    } as any);

    renderWithClient(<ProtocolListClient initialParams={{}} />);
    expect(screen.getByLabelText('Loading')).toBeInTheDocument();
  });

  it('renders error message when query fails', () => {
    vi.spyOn(useProtocolsHook, 'useProtocols').mockReturnValue({
      isLoading: false,
      isError: true,
      data: undefined,
      error: { message: 'Failed to fetch protocols from backend.' },
    } as any);

    renderWithClient(<ProtocolListClient initialParams={{}} />);
    expect(
      screen.getByText('Failed to fetch protocols from backend.'),
    ).toBeInTheDocument();
  });

  it('renders empty message when no protocols match filters', () => {
    vi.spyOn(useProtocolsHook, 'useProtocols').mockReturnValue({
      isLoading: false,
      isError: false,
      data: { data: [], meta: { current_page: 1, last_page: 1, total: 0 } },
      error: null,
    } as any);

    renderWithClient(<ProtocolListClient initialParams={{}} />);
    expect(
      screen.getByText('No protocols found matching your filters.'),
    ).toBeInTheDocument();
  });

  it('renders protocol cards and pagination when data is present', () => {
    const mockProtocols = [
      {
        id: 1,
        slug: 'zk-rollup',
        title: 'Decentralized ZK Rollup',
        description: 'Next-gen L2 scaling protocol',
        category: 'layer2',
        status: 'published',
        votes_count: 42,
        average_rating: 4.8,
        reviews_count: 12,
        score: 480,
        author: { id: 1, name: 'Vitalik B.' },
        created_at: '2026-09-01T00:00:00Z',
      },
    ];

    vi.spyOn(useProtocolsHook, 'useProtocols').mockReturnValue({
      isLoading: false,
      isError: false,
      data: {
        data: mockProtocols,
        meta: { current_page: 1, last_page: 2, total: 16 },
      },
      error: null,
    } as any);

    renderWithClient(<ProtocolListClient initialParams={{}} />);

    expect(screen.getByText('Decentralized ZK Rollup')).toBeInTheDocument();
    expect(screen.getByText('16 protocols')).toBeInTheDocument();
    expect(screen.getByRole('navigation', { name: 'Pagination' })).toBeInTheDocument();
  });
});
