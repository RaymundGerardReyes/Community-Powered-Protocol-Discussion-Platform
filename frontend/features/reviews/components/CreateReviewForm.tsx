'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/features/auth/AuthContext';
import { createReview } from '@/features/reviews/api';
import { Spinner } from '@/components/ui/Spinner';

interface CreateReviewFormProps {
  protocolId: number;
  authorId?: number;
  existingReviewerIds?: number[];
}

export function CreateReviewForm({ protocolId, authorId, existingReviewerIds = [] }: CreateReviewFormProps) {
  const router = useRouter();
  const { user, openAuthModal } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [rating, setRating] = useState<number>(5);
  const [hoverRating, setHoverRating] = useState<number | null>(null);
  const [feedback, setFeedback] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const isAuthor = Boolean(user && authorId && user.id === authorId);
  const hasReviewed = Boolean(user && existingReviewerIds.includes(user.id));

  function handleOpen() {
    if (!user) {
      openAuthModal();
      return;
    }
    if (isAuthor || hasReviewed) {
      return;
    }
    setIsOpen(true);
    setError(null);
  }

  function handleCancel() {
    setIsOpen(false);
    setRating(5);
    setFeedback('');
    setError(null);
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!rating) return;

    setIsSubmitting(true);
    setError(null);

    try {
      await createReview({
        protocol_id: protocolId,
        rating,
        feedback: feedback.trim() ? feedback.trim() : undefined,
      });

      setFeedback('');
      setIsOpen(false);
      router.refresh();
    } catch (err: unknown) {
      const apiErr = err as { errors?: Record<string, string[]>; message?: string };
      const fieldError = apiErr?.errors ? Object.values(apiErr.errors).flat()[0] : null;
      setError(fieldError ?? apiErr?.message ?? 'Failed to submit review.');
    } finally {
      setIsSubmitting(false);
    }
  }

  if (isAuthor) {
    return (
      <div
        className="mb-4 rounded-xl p-3.5 text-xs flex items-center gap-2.5"
        style={{
          background: 'var(--surface-card)',
          border: '1px solid var(--border)',
          color: 'var(--text-secondary)',
        }}
      >
        <svg className="h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>You are the author of this protocol. Peer reviews are reserved for external clinical and community reviewers.</span>
      </div>
    );
  }

  if (hasReviewed) {
    return (
      <div
        className="mb-4 rounded-xl p-3.5 text-xs flex items-center gap-2.5"
        style={{
          background: 'var(--surface-card)',
          border: '1px solid var(--border)',
          color: 'var(--text-secondary)',
        }}
      >
        <svg className="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
        </svg>
        <span>You have already submitted a peer review for this protocol. Thank you for contributing your clinical feedback!</span>
      </div>
    );
  }

  if (!isOpen) {
    return (
      <div className="mb-4 flex items-center justify-between">
        <p className="text-xs" style={{ color: 'var(--text-secondary)' }}>
          Have you evaluated or tested this protocol?
        </p>
        <button
          type="button"
          onClick={handleOpen}
          className="btn btn-sm btn-secondary flex items-center gap-1.5"
        >
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
          </svg>
          Submit Review
        </button>
      </div>
    );
  }

  const activeRating = hoverRating ?? rating;

  return (
    <div
      className="card-flat mb-6 p-5 transition-all"
      style={{
        background: 'var(--surface-card)',
        border: '1px solid var(--brand)',
        boxShadow: 'var(--shadow-md)',
      }}
    >
      <div className="mb-4 flex items-center justify-between">
        <h3 className="text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
          Write a Peer Review & Rating
        </h3>
        <button
          type="button"
          onClick={handleCancel}
          className="text-xs text-slate-400 hover:text-slate-600"
        >
          Cancel
        </button>
      </div>

      {error && (
        <div className="mb-4 rounded-lg p-3 text-xs text-red-700 bg-red-50 border border-red-200">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label className="block text-xs font-medium mb-1.5" style={{ color: 'var(--text-primary)' }}>
            Overall Rating (1 to 5 Stars)
          </label>
          <div className="flex items-center gap-1.5">
            {[1, 2, 3, 4, 5].map((star) => (
              <button
                key={star}
                type="button"
                onClick={() => setRating(star)}
                onMouseEnter={() => setHoverRating(star)}
                onMouseLeave={() => setHoverRating(null)}
                className="p-1 text-xl transition-transform hover:scale-110 focus:outline-none"
                aria-label={`${star} star${star > 1 ? 's' : ''}`}
              >
                <span className={star <= activeRating ? 'text-amber-400' : 'text-slate-300'}>
                  ★
                </span>
              </button>
            ))}
            <span className="ml-2 text-xs font-medium" style={{ color: 'var(--text-secondary)' }}>
              {activeRating} of 5 Stars
            </span>
          </div>
        </div>

        <div>
          <label htmlFor="review-feedback" className="block text-xs font-medium mb-1" style={{ color: 'var(--text-primary)' }}>
            Clinical or Practical Feedback (Optional)
          </label>
          <textarea
            id="review-feedback"
            value={feedback}
            onChange={(e) => setFeedback(e.target.value)}
            placeholder="Describe your observations, clinical efficacy, reproducibility, or recommended adjustments..."
            rows={3}
            className="input w-full text-sm resize-y"
            disabled={isSubmitting}
          />
        </div>

        <div className="flex items-center justify-end gap-2 pt-2">
          <button
            type="button"
            onClick={handleCancel}
            className="btn btn-sm btn-ghost"
            disabled={isSubmitting}
          >
            Cancel
          </button>
          <button
            type="submit"
            className="btn btn-sm btn-primary flex items-center gap-2"
            disabled={isSubmitting}
          >
            {isSubmitting && <Spinner className="h-3.5 w-3.5" />}
            {isSubmitting ? 'Submitting...' : 'Post Review'}
          </button>
        </div>
      </form>
    </div>
  );
}
