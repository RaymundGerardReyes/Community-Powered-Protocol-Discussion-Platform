'use client';

import { VoteButton } from './VoteButton';
import type { VotableType } from '../api';

interface Props {
  votableType: VotableType;
  votableId: number;
  count: number;
  currentVote?: 1 | -1 | null;
  queryKey?: unknown[];
  layout?: 'horizontal' | 'vertical';
}

export function VoteButtonWrapper({
  votableType,
  votableId,
  count,
  currentVote,
  queryKey,
  layout = 'horizontal',
}: Props) {
  return (
    <VoteButton
      votableType={votableType}
      votableId={votableId}
      currentVote={currentVote}
      count={count}
      queryKey={queryKey}
      layout={layout}
    />
  );
}
