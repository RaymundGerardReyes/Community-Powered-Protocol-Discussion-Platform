import { render } from '@testing-library/react';
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
    const { getByText } = render(<ProtocolCard protocol={mockProtocol} />);
    expect(getByText('Consensus Layer Upgrade')).toBeDefined();
    expect(getByText('Alice')).toBeDefined();
  });

  it('renders the status badge', () => {
    const { getByText } = render(<ProtocolCard protocol={mockProtocol} />);
    expect(getByText(/published/i)).toBeDefined();
  });

  it('renders the category and version', () => {
    const { getByText } = render(<ProtocolCard protocol={mockProtocol} />);
    expect(getByText('infrastructure')).toBeDefined();
    expect(getByText('v1.0.0')).toBeDefined();
  });

  it('renders votes and reviews stats', () => {
    const { getByText } = render(<ProtocolCard protocol={mockProtocol} />);
    expect(getByText(/42/)).toBeDefined();
    expect(getByText(/5/)).toBeDefined();
  });
});
