'use client';
import { VoteButton } from './VoteButton';

interface Props {
  votableType: 'App\\Models\\Thread' | 'App\\Models\\Comment';
  votableId: number;
  count: number;
  queryKey?: unknown[];
}

export function VoteButtonWrapper({ votableType, votableId, count, queryKey }: Props) {
  return (
    <VoteButton
      votableType={votableType}
      votableId={votableId}
      currentVote={null}
      count={count}
      queryKey={queryKey}
    />
  );
}
