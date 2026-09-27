import type { Comment } from '@/types';

/**
 * Recursively counts the total number of comments and nested replies in a comment tree.
 */
export function countTotalComments(comments: Comment[]): number {
  let count = 0;
  for (const comment of comments) {
    count += 1;
    if (comment.replies && comment.replies.length > 0) {
      count += countTotalComments(comment.replies);
    }
  }
  return count;
}

/**
 * Inserts a newly created comment or nested reply into an existing comment tree
 * preserving immutability and preventing duplicate insertions.
 */
export function insertCommentIntoTree(comments: Comment[], newComment: Comment): Comment[] {
  // If top-level comment (no parent_id)
  if (!newComment.parent_id) {
    if (comments.some((c) => c.id === newComment.id)) {
      return comments;
    }
    return [...comments, { ...newComment, replies: newComment.replies ?? [] }];
  }

  // If nested reply, find parent recursively and append
  return comments.map((comment) => {
    if (comment.id === newComment.parent_id) {
      const existingReplies = comment.replies ?? [];
      if (existingReplies.some((r) => r.id === newComment.id)) {
        return comment;
      }
      return {
        ...comment,
        replies: [...existingReplies, { ...newComment, replies: newComment.replies ?? [] }],
      };
    }

    if (comment.replies && comment.replies.length > 0) {
      return {
        ...comment,
        replies: insertCommentIntoTree(comment.replies, newComment),
      };
    }

    return comment;
  });
}
