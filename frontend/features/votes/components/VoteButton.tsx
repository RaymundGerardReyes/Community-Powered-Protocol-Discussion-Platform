'use client';

import { useState, useEffect } from 'react';
import { cn } from '@/lib/cn';
import { useVote } from '../hooks/useVote';
import { useAuth } from '@/features/auth/AuthContext';
import type { VoteResponse } from '@/types';
import type { VotableType } from '../api';

export interface VoteButtonProps {
  votableType: VotableType;
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
  const { user, openAuthModal } = useAuth();
  const { mutate, isPending } = useVote();
  const [authPrompt, setAuthPrompt] = useState(false);
  const [activeVote, setActiveVote] = useState<1 | -1 | null>(currentVote);
  const [displayedCount, setDisplayedCount] = useState(count);

  useEffect(() => {
    setActiveVote(currentVote);
  }, [currentVote]);

  useEffect(() => {
    setDisplayedCount(count);
  }, [count]);

  function handleVote(value: 1 | -1) {
    if (!user) {
      setAuthPrompt(true);
      setTimeout(() => setAuthPrompt(false), 3500);
      openAuthModal();
      return;
    }

    const prevVote = activeVote;
    const prevCount = displayedCount;

    // Optimistic toggle
    if (activeVote === value) {
      setActiveVote(null);
      setDisplayedCount((c) => c - value);
    } else if (activeVote === null) {
      setActiveVote(value);
      setDisplayedCount((c) => c + value);
    } else {
      setActiveVote(value);
      setDisplayedCount((c) => c + value * 2);
    }

    mutate(
      {
        votable_type: votableType,
        votable_id: votableId,
        value,
        queryKey,
      },
      {
        onSuccess: (res) => {
          if (res?.current_vote !== undefined) {
            setActiveVote(
              res.current_vote === 1 ? 1 : res.current_vote === -1 ? -1 : null
            );
          }
          if (res?.votes_count !== undefined) {
            setDisplayedCount(res.votes_count);
          }
          onSuccess?.(res);
        },
        onError: (err: unknown) => {
          setActiveVote(prevVote);
          setDisplayedCount(prevCount);
          const status = (err as { status?: number })?.status;
          if (status === 401) {
            setAuthPrompt(true);
            setTimeout(() => setAuthPrompt(false), 3500);
            openAuthModal();
          }
        },
      },
    );
  }

  const upActive = activeVote === 1;
  const downActive = activeVote === -1;

  const UpBtn = (
    <button
      type="button"
      onClick={() => handleVote(1)}
      disabled={isPending}
      aria-label="Upvote"
      aria-pressed={upActive}
      className={cn(
        'flex h-7 w-7 items-center justify-center rounded text-xs transition-all cursor-pointer',
        'hover:bg-indigo-50 active:scale-95 disabled:pointer-events-none disabled:opacity-50',
      )}
      style={
        upActive
          ? { background: 'var(--brand-light)', color: 'var(--brand)' }
          : { color: 'var(--text-muted)' }
      }
    >
      ▲
    </button>
  );

  const CountEl = (
    <span
      className="min-w-[2ch] text-center text-xs font-semibold tabular-nums select-none"
      style={{
        color: upActive
          ? 'var(--brand)'
          : downActive
          ? 'var(--danger-text)'
          : 'var(--text-secondary)',
      }}
    >
      {displayedCount}
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
        'flex h-7 w-7 items-center justify-center rounded text-xs transition-all cursor-pointer',
        'hover:bg-rose-50 active:scale-95 disabled:pointer-events-none disabled:opacity-50',
      )}
      style={
        downActive
          ? { background: 'var(--danger-bg)', color: 'var(--danger-text)' }
          : { color: 'var(--text-muted)' }
      }
    >
      ▼
    </button>
  );

  return (
    <div className="relative inline-flex">
      {layout === 'vertical' ? (
        <div className="flex flex-col items-center gap-0.5">
          {UpBtn}
          {CountEl}
          {DownBtn}
        </div>
      ) : (
        <div className="flex items-center gap-1">
          {UpBtn}
          {CountEl}
          {DownBtn}
        </div>
      )}

      {/* Auth required notification tooltip */}
      {authPrompt && (
        <div
          className="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-[0.7rem] font-medium shadow-lg animate-in fade-in zoom-in-95 duration-150"
          style={{
            background: 'var(--surface-card)',
            border: '1px solid var(--border)',
            color: 'var(--brand)',
            boxShadow: 'var(--shadow-md)',
          }}
          role="alert"
        >
          Sign in to cast your vote
        </div>
      )}
    </div>
  );
}
