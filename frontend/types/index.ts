// Mirror Laravel 13 API Resources field-for-field.
export interface Protocol {
  id: number;
  title: string;
  content: string;
  tags: string[];
  author: string;
  rating: number;
  votes_count: number;
  reviews_count: number;
  created_at: string;
}

export interface Thread {
  id: number;
  title: string;
  body: string;
  protocol_id: number;
  votes_count: number;
  created_at: string;
}

export interface Comment {
  id: number;
  body: string;
  parent_id: number | null;
  votes_count: number;
  replies?: Comment[];
}

export interface Review {
  id: number;
  rating: number;
  feedback: string | null;
}
