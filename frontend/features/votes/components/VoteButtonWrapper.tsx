'use client';

import { VoteButton } from './VoteButton';
import type { VotableType } from '../api';

interface Props {
  votableType: VotableType;
  votableId: number;
  count: number;
  queryKey?: unknown[];
  layout?: 'horizontal' | 'vertical';
}

export function VoteButtonWrapper({
  votableType,
  votableId,
  count,
  queryKey,
  layout = 'horizontal',
}: Props) {
  return (
    <VoteButton
      votableType={votableType}
      votableId={votableId}
      currentVote={null}
      count={count}
      queryKey={queryKey}
      layout={layout}
    />
  );
}
