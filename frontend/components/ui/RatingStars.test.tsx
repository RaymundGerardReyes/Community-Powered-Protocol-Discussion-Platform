import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { RatingStars } from './RatingStars';

describe('RatingStars Component', () => {
  it('renders correct number of stars and formatted aria-label', () => {
    const { container } = render(<RatingStars rating={4.2} max={5} />);
    const wrapper = screen.getByLabelText('Rating: 4.2 out of 5');
    expect(wrapper).toBeInTheDocument();

    const stars = container.querySelectorAll('svg');
    expect(stars).toHaveLength(5);
  });

  it('correctly calculates filled vs empty stars based on rounding', () => {
    // 3.6 rounds to 4 filled stars out of 5
    const { container } = render(<RatingStars rating={3.6} max={5} />);
    const filledStars = container.querySelectorAll('svg.star-filled');
    const emptyStars = container.querySelectorAll('svg.star-empty');

    expect(filledStars).toHaveLength(4);
    expect(emptyStars).toHaveLength(1);
  });

  it('renders zero rating correctly', () => {
    const { container } = render(<RatingStars rating={0} max={5} />);
    const filledStars = container.querySelectorAll('svg.star-filled');
    const emptyStars = container.querySelectorAll('svg.star-empty');

    expect(filledStars).toHaveLength(0);
    expect(emptyStars).toHaveLength(5);
  });

  it('applies correct size classes', () => {
    const { container, rerender } = render(<RatingStars rating={5} size="sm" />);
    expect(container.querySelector('svg')).toHaveClass('h-3', 'w-3');

    rerender(<RatingStars rating={5} size="md" />);
    expect(container.querySelector('svg')).toHaveClass('h-4', 'w-4');

    rerender(<RatingStars rating={5} size="lg" />);
    expect(container.querySelector('svg')).toHaveClass('h-5', 'w-5');
  });
});
