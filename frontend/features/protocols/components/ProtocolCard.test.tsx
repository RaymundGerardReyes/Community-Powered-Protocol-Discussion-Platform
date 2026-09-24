import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { ProtocolCard } from './ProtocolCard';
import type { Protocol } from '@/types';

const mockProtocol: Protocol = {
  id: 1,
  slug: 'consensus-layer-upgrade',
  title: 'Consensus Layer Upgrade',
  description: 'Details about protocol upgrade...',
  category: 'infrastructure',
  version: '1.0.0',
  status: 'published',
  score: 100,
  votes_count: 42,
  average_rating: 4.8,
  reviews_count: 5,
  metadata: null,
  author: {
    id: 1,
    name: 'Alice',
    email: 'alice@example.com',
    created_at: '2026-09-24T00:00:00Z',
  },
  created_at: '2026-09-24T00:00:00Z',
  updated_at: '2026-09-24T00:00:00Z',
};

describe('ProtocolCard', () => {
  it('renders protocol title and author', () => {
    render(<ProtocolCard protocol={mockProtocol} />);
    expect(screen.getByText('Consensus Layer Upgrade')).toBeInTheDocument();
    expect(screen.getByText('Alice')).toBeInTheDocument();
  });

  it('renders the status badge', () => {
    render(<ProtocolCard protocol={mockProtocol} />);
    expect(screen.getByText('published')).toBeInTheDocument();
  });

  it('renders the category and version', () => {
    render(<ProtocolCard protocol={mockProtocol} />);
    expect(screen.getByText('infrastructure')).toBeInTheDocument();
    expect(screen.getByText('v1.0.0')).toBeInTheDocument();
  });

  it('renders reviews count and votes', () => {
    render(<ProtocolCard protocol={mockProtocol} />);
    expect(screen.getByText('5 reviews')).toBeInTheDocument();
    expect(screen.getByText('▲ 42')).toBeInTheDocument();
  });
});
