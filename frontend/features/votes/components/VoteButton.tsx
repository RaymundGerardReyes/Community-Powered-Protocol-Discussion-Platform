'use client';
import { cn } from '@/lib/cn';
import { useVote } from '../hooks/useVote';
import type { VoteResponse } from '@/types';

export interface VoteButtonProps {
  votableType: 'App\\Models\\Protocol' | 'App\\Models\\Thread' | 'App\\Models\\Comment' | string;
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
      className={cn(
        'flex h-7 w-7 items-center justify-center rounded text-xs transition-colors',
        'hover:bg-indigo-50 disabled:pointer-events-none disabled:opacity-50',
        upActive
          ? 'bg-indigo-100 text-indigo-600 font-semibold'
          : 'text-slate-500 hover:text-indigo-600'
      )}
    >
      ▲
    </button>
  );

  const CountEl = (
    <span
      className={cn(
        'min-w-[2ch] text-center text-xs font-semibold tabular-nums',
        upActive ? 'text-indigo-600' : downActive ? 'text-red-500' : 'text-slate-700'
      )}
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
      className={cn(
        'flex h-7 w-7 items-center justify-center rounded text-xs transition-colors',
        'hover:bg-red-50 disabled:pointer-events-none disabled:opacity-50',
        downActive
          ? 'bg-red-100 text-red-500 font-semibold'
          : 'text-slate-500 hover:text-red-500'
      )}
    >
      ▼
    </button>
  );

  if (layout === 'vertical') {
    return (
      <div className="flex flex-col items-center gap-0.5">
        {UpBtn}
        {CountEl}
        {DownBtn}
      </div>
    );
  }

  return (
    <div className="flex items-center gap-1">
      {UpBtn}
      {CountEl}
      {DownBtn}
    </div>
  );
}
