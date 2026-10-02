import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { ProtocolFilterBar } from '@/features/protocols/components/ProtocolFilterBar';

const mockPush = vi.fn();
let mockSearchParams = new URLSearchParams();

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: mockPush }),
  usePathname: () => '/protocols',
  useSearchParams: () => mockSearchParams,
}));

function renderWithClient(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      {ui}
    </QueryClientProvider>
  );
}

describe('ProtocolFilterBar Component Integration', () => {
  beforeEach(() => {
    mockPush.mockReset();
    mockSearchParams = new URLSearchParams();
  });

  it('renders sort pills and updates router on click', () => {
    renderWithClient(<ProtocolFilterBar />);

    const topVotedButton = screen.getByRole('button', { name: 'Top Voted' });
    fireEvent.click(topVotedButton);

    expect(mockPush).toHaveBeenCalledWith('/protocols?sort=top');
  });

  it('updates category query param on select change', () => {
    renderWithClient(<ProtocolFilterBar />);

    const categorySelect = screen.getByLabelText('Filter by category');
    fireEvent.change(categorySelect, { target: { value: 'gut-health' } });

    expect(mockPush).toHaveBeenCalledWith('/protocols?category=gut-health');
  });

  it('updates status query param on select change', () => {
    renderWithClient(<ProtocolFilterBar />);

    const statusSelect = screen.getByLabelText('Filter by status');
    fireEvent.change(statusSelect, { target: { value: 'published' } });

    expect(mockPush).toHaveBeenCalledWith('/protocols?status=published');
  });
});
