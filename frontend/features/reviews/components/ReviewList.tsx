import { RatingStars } from '@/components/ui/RatingStars';
import type { Review } from '@/types';

export function ReviewList({ reviews }: { reviews: Review[] }) {
  if (!reviews.length) {
    return <p className='py-4 text-sm text-slate-500 italic'>No reviews yet.</p>;
  }
  return (
    <ul className='divide-y divide-slate-100'>
      {reviews.map((review) => (
        <li key={review.id} className='py-4'>
          <div className='flex items-center justify-between'>
            <span className='text-sm font-medium text-slate-700'>
              {review.author?.name ?? 'Anonymous'}
            </span>
            <RatingStars rating={review.rating} size='sm' />
          </div>
          {review.feedback && (
            <p className='mt-1 text-sm text-slate-600 leading-relaxed'>
              {review.feedback}
            </p>
          )}
          <time className='mt-1 block text-xs text-slate-400' dateTime={review.created_at}>
            {new Date(review.created_at).toLocaleDateString()}
          </time>
        </li>
      ))}
    </ul>
  );
}
