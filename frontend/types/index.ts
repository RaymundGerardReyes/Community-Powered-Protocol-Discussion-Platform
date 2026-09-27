// Mirrors Laravel 13 API Resources field-for-field.
// Changes here indicate a breaking change in the backend contract.

export interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

export interface Protocol {
  id: number;
  slug: string;
  title: string;
  description: string;
  category: string;
  version: string;
  status: 'draft' | 'published' | 'deprecated';
  score: number;
  votes_count: number;
  average_rating: number;
  reviews_count: number;
  metadata: Record<string, unknown> | null;
  author: User;
  threads?: Thread[];
  reviews?: Review[];
  created_at: string;
  updated_at: string;
}

export interface Thread {
  id: number;
  title: string;
  content: string;
  body?: string;
  protocol_id: number;
  views_count: number;
  votes_count: number;
  comments_count: number;
  author: User;
  protocol?: Protocol;
  comments?: Comment[];
  created_at: string;
  updated_at: string;
}

export interface Comment {
  id: number;
  content: string;
  body?: string;
  thread_id: number;
  parent_id: number | null;
  replies_count?: number;
  votes_count?: number;
  author: User;
  replies?: Comment[];
  created_at: string;
  updated_at: string;
}

export interface Review {
  id: number;
  rating: number;
  feedback: string | null;
  protocol_id: number;
  author: User;
  created_at: string;
  updated_at: string;
}

export interface Vote {
  id: number;
  value: 1 | -1;
  votable_type: string;
  votable_id: number;
  user_id: number;
  created_at: string;
}

export interface VoteResponse {
  action: 'created' | 'updated' | 'removed';
  current_vote: number | Vote | null;
  votes_count: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
}
