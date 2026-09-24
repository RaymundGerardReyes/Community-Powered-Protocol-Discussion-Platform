import { useMutation } from "@tanstack/react-query";
import { apiClient } from "@/lib/api-client";

interface VotePayload {
  votable_type: "thread" | "comment";
  votable_id: number;
  value: 1 | -1;
}

export function useVote() {
  return useMutation({
    mutationFn: (payload: VotePayload) => apiClient.post("/api/v1/votes", payload),
    // TODO: optimistic update + rollback on error.
  });
}
