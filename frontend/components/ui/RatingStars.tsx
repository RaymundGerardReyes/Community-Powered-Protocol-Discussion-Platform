import { cn } from '@/lib/cn';

interface RatingStarsProps {
  rating: number; // 1–5
  max?: number;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

export function RatingStars({ rating, max = 5, size = 'md', className }: RatingStarsProps) {
  return (
    <span className={cn('inline-flex items-center gap-0.5', className)} aria-label={`Rating: ${rating} out of ${max}`}>
      {Array.from({ length: max }, (_, i) => (
        <svg
          key={i}
          className={cn(
            i < Math.round(rating) ? 'text-amber-400' : 'text-slate-200',
            { 'h-3 w-3': size === 'sm', 'h-4 w-4': size === 'md', 'h-5 w-5': size === 'lg' },
          )}
          fill='currentColor'
          viewBox='0 0 20 20'
          aria-hidden='true'
        >
          <path d='M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z' />
        </svg>
      ))}
    </span>
  );
}
