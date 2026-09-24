'use client';
import { cn } from '@/lib/cn';
import { useVote } from '../hooks/useVote';
import type { VoteResponse } from '@/types';

export interface VoteButtonProps {
  votableType: string;
  votableId: number;
  currentVote: 1 | -1 | null;
  count: number;
  queryKey?: unknown[];
  onSuccess?: (result: VoteResponse) => void;
  layout?: 'horizontal' | 'vertical';
}

export function VoteButton({
  votableType,
  votableId,
  currentVote,
  count,
  queryKey,
  onSuccess,
  layout = 'horizontal',
}: VoteButtonProps) {
  const { mutate, isPending } = useVote();

  function handleVote(value: 1 | -1) {
    mutate(
      {
        votable_type: votableType as 'App\\Models\\Protocol' | 'App\\Models\\Thread' | 'App\\Models\\Comment',
        votable_id: votableId,
        value,
        queryKey,
      },
      { onSuccess },
    );
  }

  const upActive = currentVote === 1;
  const downActive = currentVote === -1;

  const UpBtn = (
    <button
      type="button"
      onClick={() => handleVote(1)}
      disabled={isPending}
      aria-label="Upvote"
      aria-pressed={upActive}
      className={cn('vote-btn vote-btn-up', upActive && 'active')}
      style={upActive ? { color: '#818cf8', background: 'rgba(99,102,241,0.15)' } : { color: 'var(--text-muted)' }}
    >
      ▲
    </button>
  );

  const CountEl = (
    <span
      className="tabular-nums text-xs font-semibold min-w-[2ch] text-center"
      style={{
        color: upActive ? '#818cf8' : downActive ? '#f87171' : 'var(--text-secondary)',
      }}
    >
      {count}
    </span>
  );

  const DownBtn = (
    <button
      type="button"
      onClick={() => handleVote(-1)}
      disabled={isPending}
      aria-label="Downvote"
      aria-pressed={downActive}
      className={cn('vote-btn vote-btn-down', downActive && 'active')}
      style={downActive ? { color: '#f87171', background: 'rgba(239,68,68,0.12)' } : { color: 'var(--text-muted)' }}
    >
      ▼
    </button>
  );

  if (layout === 'vertical') {
    return (
      <div className="flex flex-col items-center">
        {UpBtn}
        {CountEl}
        {DownBtn}
      </div>
    );
  }

  return (
    <div className="flex items-center gap-0.5">
      {UpBtn}
      {CountEl}
      {DownBtn}
    </div>
  );
}
