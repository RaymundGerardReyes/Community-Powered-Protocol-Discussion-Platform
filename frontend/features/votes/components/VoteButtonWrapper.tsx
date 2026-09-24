'use client';
import { VoteButton } from './VoteButton';

interface Props {
  votableType: string;
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
