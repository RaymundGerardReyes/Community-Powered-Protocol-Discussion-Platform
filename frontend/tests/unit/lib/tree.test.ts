import { describe, it, expect } from 'vitest';
import { countTotalComments, insertCommentIntoTree } from '@/features/comments/lib/tree';
import type { Comment } from '@/types';

const mockAuthor = {
  id: 1,
  name: 'Test Author',
  email: 'test@example.com',
  created_at: '2026-09-01T00:00:00Z',
};

describe('Comment Tree Helpers', () => {
  describe('countTotalComments', () => {
    it('returns 0 for empty array', () => {
      expect(countTotalComments([])).toBe(0);
    });

    it('counts flat root comments correctly', () => {
      const comments: Comment[] = [
        { id: 1, thread_id: 1, parent_id: null, content: 'A', author: mockAuthor, created_at: '', updated_at: '', replies: [] },
        { id: 2, thread_id: 1, parent_id: null, content: 'B', author: mockAuthor, created_at: '', updated_at: '', replies: [] },
      ];
      expect(countTotalComments(comments)).toBe(2);
    });

    it('recursively counts nested replies across arbitrary depths', () => {
      const tree: Comment[] = [
        {
          id: 1,
          thread_id: 1,
          parent_id: null,
          content: 'Root 1',
          author: mockAuthor,
          created_at: '',
          updated_at: '',
          replies: [
            {
              id: 2,
              thread_id: 1,
              parent_id: 1,
              content: 'Reply 1.1',
              author: mockAuthor,
              created_at: '',
              updated_at: '',
              replies: [
                {
                  id: 3,
                  thread_id: 1,
                  parent_id: 2,
                  content: 'Reply 1.1.1',
                  author: mockAuthor,
                  created_at: '',
                  updated_at: '',
                  replies: [],
                },
              ],
            },
            {
              id: 4,
              thread_id: 1,
              parent_id: 1,
              content: 'Reply 1.2',
              author: mockAuthor,
              created_at: '',
              updated_at: '',
              replies: [],
            },
          ],
        },
        {
          id: 5,
          thread_id: 1,
          parent_id: null,
          content: 'Root 2',
          author: mockAuthor,
          created_at: '',
          updated_at: '',
          replies: [],
        },
      ];

      expect(countTotalComments(tree)).toBe(5);
    });
  });

  describe('insertCommentIntoTree', () => {
    it('appends top-level comment to empty list', () => {
      const newComment: Comment = {
        id: 10,
        thread_id: 1,
        parent_id: null,
        content: 'New Root',
        author: mockAuthor,
        created_at: '',
        updated_at: '',
        replies: [],
      };

      const result = insertCommentIntoTree([], newComment);
      expect(result).toHaveLength(1);
      expect(result[0].id).toBe(10);
    });

    it('inserts nested reply under target parent comment at root level', () => {
      const tree: Comment[] = [
        { id: 1, thread_id: 1, parent_id: null, content: 'Root', author: mockAuthor, created_at: '', updated_at: '', replies: [] },
      ];
      const newReply: Comment = {
        id: 2,
        thread_id: 1,
        parent_id: 1,
        content: 'Child Reply',
        author: mockAuthor,
        created_at: '',
        updated_at: '',
        replies: [],
      };

      const result = insertCommentIntoTree(tree, newReply);
      expect(result).toHaveLength(1);
      expect(result[0].replies).toHaveLength(1);
      expect(result[0].replies![0].id).toBe(2);
      expect(result[0].replies![0].content).toBe('Child Reply');
    });

    it('recursively traverses and inserts reply under deeply nested parent', () => {
      const tree: Comment[] = [
        {
          id: 1,
          thread_id: 1,
          parent_id: null,
          content: 'Root',
          author: mockAuthor,
          created_at: '',
          updated_at: '',
          replies: [
            {
              id: 2,
              thread_id: 1,
              parent_id: 1,
              content: 'Level 1 Reply',
              author: mockAuthor,
              created_at: '',
              updated_at: '',
              replies: [],
            },
          ],
        },
      ];

      const deepReply: Comment = {
        id: 3,
        thread_id: 1,
        parent_id: 2,
        content: 'Level 2 Reply',
        author: mockAuthor,
        created_at: '',
        updated_at: '',
        replies: [],
      };

      const result = insertCommentIntoTree(tree, deepReply);
      expect(result[0].replies![0].replies).toHaveLength(1);
      expect(result[0].replies![0].replies![0].id).toBe(3);
      expect(result[0].replies![0].replies![0].content).toBe('Level 2 Reply');
    });

    it('avoids duplicate insertion if comment already exists', () => {
      const tree: Comment[] = [
        { id: 1, thread_id: 1, parent_id: null, content: 'Root', author: mockAuthor, created_at: '', updated_at: '', replies: [] },
      ];
      const duplicate: Comment = {
        id: 1,
        thread_id: 1,
        parent_id: null,
        content: 'Root Duplicate',
        author: mockAuthor,
        created_at: '',
        updated_at: '',
        replies: [],
      };

      const result = insertCommentIntoTree(tree, duplicate);
      expect(result).toHaveLength(1);
    });
  });
});
