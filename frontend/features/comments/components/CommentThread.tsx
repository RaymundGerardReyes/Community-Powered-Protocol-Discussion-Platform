import type { Comment } from "@/types";

// Recursive comment renderer with depth limit
export function CommentThread({ comment, depth = 0 }: { comment: Comment; depth?: number }) {
  return (
    <div style={{ marginLeft: Math.min(depth, 5) * 16 }} className="border-l border-slate-200 pl-3 py-1">
      <p className="text-slate-800 text-sm">{comment.body}</p>
      {comment.replies?.map((reply) => (
        <CommentThread key={reply.id} comment={reply} depth={depth + 1} />
      ))}
    </div>
  );
}
