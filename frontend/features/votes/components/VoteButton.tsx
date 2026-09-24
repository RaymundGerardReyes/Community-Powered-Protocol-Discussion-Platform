'use client';
import { cn } from '@/lib/cn';
import { useVote } from '../hooks/useVote';
import type { VoteResponse } from '@/types';

interface VoteButtonProps {
  votableType: 'App\\Models\\Thread' | 'App\\Models\\Comment';
  votableId: number;
  currentVote: 1 | -1 | null;
  count: number;
  queryKey?: unknown[];
  onSuccess?: (result: VoteResponse) => void;
}

export function VoteButton({
  votableType,
  votableId,
  currentVote,
  count,
  queryKey,
  onSuccess,
}: VoteButtonProps) {
  const { mutate, isPending } = useVote();

  function handleVote(value: 1 | -1) {
    mutate(
      { votable_type: votableType, votable_id: votableId, value, queryKey },
      { onSuccess },
    );
  }

  return (
    <div className='flex items-center gap-1'>
      <button
        onClick={() => handleVote(1)}
        disabled={isPending}
        aria-label='Upvote'
        aria-pressed={currentVote === 1}
        className={cn(
          'flex h-8 w-8 items-center justify-center rounded-md text-sm transition-colors',
          'hover:bg-indigo-50 disabled:pointer-events-none disabled:opacity-50',
          currentVote === 1
            ? 'text-indigo-600 font-semibold'
            : 'text-slate-500 hover:text-indigo-600',
        )}
      >
        ▲
      </button>
      <span
        className={cn(
          'min-w-[2ch] text-center text-sm font-medium tabular-nums',
          currentVote === 1 ? 'text-indigo-600' : currentVote === -1 ? 'text-red-500' : 'text-slate-700',
        )}
      >
        {count}
      </span>
      <button
        onClick={() => handleVote(-1)}
        disabled={isPending}
        aria-label='Downvote'
        aria-pressed={currentVote === -1}
        className={cn(
          'flex h-8 w-8 items-center justify-center rounded-md text-sm transition-colors',
          'hover:bg-red-50 disabled:pointer-events-none disabled:opacity-50',
          currentVote === -1
            ? 'text-red-500 font-semibold'
            : 'text-slate-500 hover:text-red-500',
        )}
      >
        ▼
      </button>
    </div>
  );
}
