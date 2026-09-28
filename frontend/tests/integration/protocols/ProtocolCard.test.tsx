import { render } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { ProtocolCard } from '@/features/protocols/components/ProtocolCard';
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
  average_rating: 4.5,
  reviews_count: 8,
  metadata: {},
  author: {
    id: 2,
    name: 'Satoshi N.',
    email: 'satoshi@protocol.io',
    created_at: '2026-09-01T00:00:00Z',
  },
  created_at: '2026-09-24T00:00:00.000000Z',
  updated_at: '2026-09-24T00:00:00.000000Z',
};

describe('ProtocolCard', () => {
  it('renders protocol title and author', () => {
    const { getByText } = render(<ProtocolCard protocol={mockProtocol} />);
    expect(getByText('Consensus Layer Upgrade')).toBeDefined();
    expect(getByText('Satoshi N.')).toBeDefined();
  });

  it('renders category and status badges', () => {
    const { getByText } = render(<ProtocolCard protocol={mockProtocol} />);
    expect(getByText('infrastructure')).toBeDefined();
    expect(getByText('published')).toBeDefined();
  });

  it('renders rating and vote counts', () => {
    const { getByText } = render(<ProtocolCard protocol={mockProtocol} />);
    expect(getByText(/8 reviews/i)).toBeDefined();
    expect(getByText(/42/)).toBeDefined();
  });

  it('links to the correct protocol details page', () => {
    const { getByRole } = render(<ProtocolCard protocol={mockProtocol} />);
    const link = getByRole('link');
    expect(link.getAttribute('href')).toBe('/protocols/consensus-layer-upgrade');
  });

  it('implements accessible stretched-link overlay for full-card clickable surface', () => {
    const { container, getByRole } = render(<ProtocolCard protocol={mockProtocol} />);
    const article = container.querySelector('article');
    expect(article).toHaveClass('relative');
    expect(article).toHaveClass('group');
    expect(article).toHaveStyle({ cursor: 'pointer' });

    const link = getByRole('link');
    expect(link.className).toContain('after:absolute');
    expect(link.className).toContain('after:inset-0');
    expect(link.className).toContain('after:z-0');
  });
});

