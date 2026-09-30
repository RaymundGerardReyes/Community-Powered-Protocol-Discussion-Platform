import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { CreateReviewForm } from '@/features/reviews/components/CreateReviewForm';
import * as AuthContextModule from '@/features/auth/AuthContext';
import * as ReviewsApiModule from '@/features/reviews/api';

vi.mock('@/features/reviews/api');
vi.mock('next/navigation', () => ({
  useRouter: () => ({
    refresh: vi.fn(),
  }),
}));

describe('CreateReviewForm Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders "Submit Review" button when user is unauthenticated', () => {
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: null,
      token: null,
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    render(<CreateReviewForm protocolId={11} authorId={3} />);

    expect(screen.getByRole('button', { name: /Submit Review/i })).toBeInTheDocument();
  });

  it('renders author banner when current user is the protocol author', () => {
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: {
        id: 3,
        name: 'Dr. Andrew H.',
        email: 'andrew@protocol.io',
        created_at: '2026-09-24T00:00:00Z',
      },
      token: 'fake-token',
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    render(<CreateReviewForm protocolId={11} authorId={3} />);

    expect(
      screen.getByText(/You are the author of this protocol. Peer reviews are reserved for external clinical and community reviewers./i)
    ).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /Submit Review/i })).not.toBeInTheDocument();
  });

  it('renders already-reviewed banner when user has already submitted a review', () => {
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: {
        id: 4,
        name: 'Dr. Rhonda P.',
        email: 'rhonda@protocol.io',
        created_at: '2026-09-24T00:00:00Z',
      },
      token: 'fake-token',
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    render(<CreateReviewForm protocolId={11} authorId={3} existingReviewerIds={[4, 5]} />);

    expect(
      screen.getByText(/You have already submitted a peer review for this protocol./i)
    ).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /Submit Review/i })).not.toBeInTheDocument();
  });

  it('allows a third-party authenticated clinician to open and submit a review', async () => {
    vi.spyOn(AuthContextModule, 'useAuth').mockReturnValue({
      user: {
        id: 5,
        name: 'Elena Rostova PT',
        email: 'elena@protocol.io',
        created_at: '2026-09-24T00:00:00Z',
      },
      token: 'fake-token',
      isLoading: false,
      login: vi.fn(),
      logout: vi.fn(),
      openAuthModal: vi.fn(),
      closeAuthModal: vi.fn(),
      isAuthModalOpen: false,
    });

    vi.mocked(ReviewsApiModule.createReview).mockResolvedValue({
      id: 101,
      protocol_id: 11,
      rating: 5,
      feedback: 'Excellent methodology and biomechanics.',
      created_at: '2026-09-30T10:00:00Z',
      updated_at: '2026-09-30T10:00:00Z',
      author: {
        id: 5,
        name: 'Elena Rostova PT',
        email: 'elena@protocol.io',
        created_at: '2026-09-24T00:00:00Z',
      },
    });

    render(<CreateReviewForm protocolId={11} authorId={3} existingReviewerIds={[]} />);

    // Open form
    const openBtn = screen.getByRole('button', { name: /Submit Review/i });
    fireEvent.click(openBtn);

    expect(screen.getByText(/Write a Peer Review & Rating/i)).toBeInTheDocument();

    // Type feedback
    const textarea = screen.getByPlaceholderText(/Describe your observations, clinical efficacy/i);
    fireEvent.change(textarea, { target: { value: 'Excellent methodology and biomechanics.' } });

    // Submit
    const submitBtn = screen.getByRole('button', { name: /Post Review/i });
    fireEvent.click(submitBtn);

    await waitFor(() => {
      expect(ReviewsApiModule.createReview).toHaveBeenCalledWith({
        protocol_id: 11,
        rating: 5,
        feedback: 'Excellent methodology and biomechanics.',
      });
    });
  });
});
