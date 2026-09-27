import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ReviewList } from '@/features/reviews/components/ReviewList';
import type { Review } from '@/types';

describe('ReviewList Component Integration', () => {
  it('renders empty message when review array is empty', () => {
    render(<ReviewList reviews={[]} />);
    expect(
      screen.getByText('No peer reviews yet. Be the first to evaluate this protocol!'),
    ).toBeInTheDocument();
  });

  it('renders list of reviews with author, rating stars, and feedback', () => {
    const mockReviews: Review[] = [
      {
        id: 1,
        protocol_id: 10,
        rating: 5,
        feedback: 'Incredible architecture and sound cryptography.',
        created_at: '2026-09-15T12:00:00Z',
        updated_at: '2026-09-15T12:00:00Z',
        author: {
          id: 101,
          name: 'Alice Cryptographer',
          email: 'alice@protocol.io',
          created_at: '2026-09-01T00:00:00Z',
        },
      },
      {
        id: 2,
        protocol_id: 10,
        rating: 3,
        feedback: 'Needs clearer sequencer liveness fallbacks.',
        created_at: '2026-09-16T15:00:00Z',
        updated_at: '2026-09-16T15:00:00Z',
        author: {
          id: 102,
          name: 'Bob Auditor',
          email: 'bob@protocol.io',
          created_at: '2026-09-01T00:00:00Z',
        },
      },
    ];

    render(<ReviewList reviews={mockReviews} />);

    expect(screen.getByText('Alice Cryptographer')).toBeInTheDocument();
    expect(
      screen.getByText('Incredible architecture and sound cryptography.'),
    ).toBeInTheDocument();

    expect(screen.getByText('Bob Auditor')).toBeInTheDocument();
    expect(
      screen.getByText('Needs clearer sequencer liveness fallbacks.'),
    ).toBeInTheDocument();

    expect(screen.getByLabelText('Rating: 5.0 out of 5')).toBeInTheDocument();
    expect(screen.getByLabelText('Rating: 3.0 out of 5')).toBeInTheDocument();
  });
});
