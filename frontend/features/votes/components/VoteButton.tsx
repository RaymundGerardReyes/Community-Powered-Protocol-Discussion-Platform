"use client";
import { useVote } from "../hooks/useVote";

interface Props {
  votableType: "thread" | "comment";
  votableId: number;
  count: number;
}

export function VoteButton({ votableType, votableId, count }: Props) {
  const { mutate, isPending } = useVote();
  return (
    <button
      disabled={isPending}
      onClick={() => mutate({ votable_type: votableType, votable_id: votableId, value: 1 })}
      className="flex items-center gap-1 text-sm font-medium text-slate-700 hover:text-indigo-600 transition-colors disabled:opacity-50"
    >
      ▲ {count}
    </button>
  );
}
