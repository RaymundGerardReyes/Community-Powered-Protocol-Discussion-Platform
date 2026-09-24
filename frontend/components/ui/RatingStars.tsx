export function RatingStars({ rating }: { rating: number }) {
  return (
    <div aria-label={`Rated ${rating} out of 5`} className="text-amber-400">
      {"★".repeat(Math.round(rating))}
    </div>
  );
}
