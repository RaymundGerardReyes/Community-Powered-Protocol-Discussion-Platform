import Link from 'next/link';
import { cn } from '@/lib/cn';
import type { Protocol } from '@/types';

const STATUS_CONFIG: Record<Protocol['status'], { cls: string; label: string }> = {
  published:  { cls: 'badge-published',  label: 'Published'  },
  draft:      { cls: 'badge-draft',      label: 'Draft'      },
  deprecated: { cls: 'badge-deprecated', label: 'Deprecated' },
};

function StarRating({ rating }: { rating: number }) {
  return (
    <span className="inline-flex items-center gap-0.5" aria-label={`${rating.toFixed(1)} stars`}>
      {Array.from({ length: 5 }, (_, i) => (
        <svg
          key={i}
          className={cn('h-3 w-3', i < Math.round(rating) ? 'star-filled' : 'star-empty')}
          fill="currentColor" viewBox="0 0 20 20" aria-hidden
        >
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
        </svg>
      ))}
    </span>
  );
}

function AuthorAvatar({ name }: { name: string }) {
  const initials = name.split(' ').map((p) => p[0]).join('').slice(0, 2).toUpperCase();
  // Deterministic hue from name
  const hue = [...name].reduce((acc, c) => acc + c.charCodeAt(0), 0) % 360;
  return (
    <span
      className="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[0.6rem] font-semibold text-white"
      style={{ background: `hsl(${hue}, 60%, 45%)` }}
      aria-hidden
    >
      {initials}
    </span>
  );
}

export function ProtocolCard({ protocol }: { protocol: Protocol }) {
  const status = STATUS_CONFIG[protocol.status] ?? { cls: 'badge-neutral', label: protocol.status };

  return (
    <article className="card flex flex-col h-full p-5">
      {/* Top row: status badge */}
      <div className="flex items-center justify-between gap-2 mb-3">
        <div className="flex items-center gap-1.5 flex-wrap">
          <span className={`chip`}>{protocol.category}</span>
          <span className={`chip font-mono`}>v{protocol.version}</span>
        </div>
        <span className={`badge ${status.cls} shrink-0`}>{status.label}</span>
      </div>

      {/* Title */}
      <Link
        href={`/protocols/${protocol.slug}`}
        className="group flex-1"
      >
        <h3
          className="text-sm font-semibold leading-snug line-clamp-2 group-hover:text-gradient-brand transition-colors"
          style={{ color: 'var(--text-primary)' }}
        >
          {protocol.title}
        </h3>
      </Link>

      {/* Description */}
      <p
        className="mt-2 text-xs leading-relaxed line-clamp-2 flex-1"
        style={{ color: 'var(--text-secondary)' }}
      >
        {protocol.description}
      </p>

      {/* Divider */}
      <div className="my-3" style={{ height: '1px', background: 'var(--surface-overlay)' }} />

      {/* Footer: author + stats */}
      <div className="flex items-center justify-between gap-2">
        <div className="flex items-center gap-1.5 min-w-0">
          <AuthorAvatar name={protocol.author.name} />
          <span
            className="text-xs truncate"
            style={{ color: 'var(--text-muted)' }}
          >
            {protocol.author.name}
          </span>
        </div>

        <div className="flex items-center gap-2.5 shrink-0">
          <StarRating rating={protocol.average_rating} />
          <span
            className="text-xs tabular-nums"
            style={{ color: 'var(--text-muted)' }}
            title={`${protocol.votes_count} votes`}
          >
            <span style={{ color: 'var(--brand)' }}>▲</span>{' '}
            {protocol.votes_count}
          </span>
          <span
            className="text-xs tabular-nums"
            style={{ color: 'var(--text-muted)' }}
            title={`${protocol.reviews_count} reviews`}
          >
            ✦ {protocol.reviews_count}
          </span>
        </div>
      </div>
    </article>
  );
}
