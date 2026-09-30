import { RatingStars } from '@/components/ui/RatingStars';
import type { Review } from '@/types';

function AuthorAvatar({ name }: { name: string }) {
  const initials = name
    .split(' ')
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
  const hue = [...name].reduce((acc, c) => acc + c.charCodeAt(0), 0) % 360;

  return (
    <span
      className="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[0.65rem] font-semibold text-white"
      style={{ background: `hsl(${hue}, 60%, 45%)` }}
      aria-hidden
    >
      {initials}
    </span>
  );
}

export function ReviewList({ reviews }: { reviews: Review[] }) {
  if (!reviews.length) {
    return (
      <div className="py-6 text-center text-xs" style={{ color: 'var(--text-muted)' }}>
        <p className="italic">No peer reviews yet. Be the first to evaluate this protocol!</p>
      </div>
    );
  }

  return (
    <ul className="divide-y" style={{ borderColor: 'var(--surface-overlay)' }}>
      {reviews.map((review) => (
        <li key={review.id} className="py-4 first:pt-0 last:pb-0">
          <div className="flex items-center justify-between gap-2">
            <div className="flex items-center gap-2 min-w-0">
              <AuthorAvatar name={review.author?.name ?? 'Anonymous'} />
              <span
                className="text-xs font-medium truncate"
                style={{ color: 'var(--text-primary)' }}
              >
                {review.author?.name ?? 'Anonymous'}
              </span>
            </div>
            <RatingStars rating={review.rating} size="sm" />
          </div>

          {(review.feedback || (review as any).summary || (review as any).findings) && (
            <p
              className="mt-2 text-xs leading-relaxed"
              style={{ color: 'var(--text-secondary)' }}
            >
              {review.feedback || (review as any).summary || (review as any).findings}
            </p>
          )}

          <time
            className="mt-2 block text-[0.65rem]"
            style={{ color: 'var(--text-muted)' }}
            dateTime={review.created_at}
          >
            {new Date(review.created_at).toLocaleDateString('en-US', {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
            })}
          </time>
        </li>
      ))}
    </ul>
  );
}
