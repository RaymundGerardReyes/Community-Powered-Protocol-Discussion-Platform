import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { ThreadCard } from './ThreadCard';
import { AuthProvider } from '@/features/auth/AuthContext';
import type { Thread } from '@/types';

function renderWithClient(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      <AuthProvider>{ui}</AuthProvider>
    </QueryClientProvider>,
  );
}

describe('ThreadCard Component Integration', () => {
  it('renders thread details, author, counts, and vote button', () => {
    const mockThread: Thread = {
      id: 42,
      protocol_id: 1,
      title: 'State Transition Verification Mechanism',
      content: 'Discussion regarding recursive STARK verification on Ethereum L1.',
      votes_count: 14,
      comments_count: 8,
      views_count: 156,
      created_at: '2026-09-18T10:00:00Z',
      updated_at: '2026-09-18T10:00:00Z',
      author: { id: 1, name: 'Vitalik B.' },
    };

    renderWithClient(<ThreadCard thread={mockThread} />);

    // Link and title
    const titleLink = screen.getByRole('link', {
      name: 'State Transition Verification Mechanism',
    });
    expect(titleLink).toHaveAttribute('href', '/threads/42');

    // Description/body
    expect(
      screen.getByText('Discussion regarding recursive STARK verification on Ethereum L1.'),
    ).toBeInTheDocument();

    // Author
    expect(screen.getByText('Vitalik B.')).toBeInTheDocument();

    // Counts
    expect(screen.getByText('8')).toBeInTheDocument();
    expect(screen.getByText('156')).toBeInTheDocument();
  });
});
