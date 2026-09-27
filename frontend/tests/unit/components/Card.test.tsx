import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Card } from '@/components/ui/Card';

describe('Card Component', () => {
  it('renders flat card by default', () => {
    render(<Card>Card Content</Card>);
    const card = screen.getByText('Card Content');
    expect(card).toBeInTheDocument();
    expect(card).toHaveClass('card-flat');
    expect(card).not.toHaveClass('card');
  });

  it('renders hoverable card when hoverable is true', () => {
    render(<Card hoverable>Hoverable Content</Card>);
    const card = screen.getByText('Hoverable Content');
    expect(card).toHaveClass('card');
    expect(card).not.toHaveClass('card-flat');
  });

  it('merges custom className and preserves custom attributes', () => {
    render(
      <Card className="extra-class" data-testid="custom-card" role="region">
        Inner Element
      </Card>,
    );
    const card = screen.getByTestId('custom-card');
    expect(card).toHaveClass('extra-class', 'card-flat', 'p-5');
    expect(card).toHaveAttribute('role', 'region');
  });
});
