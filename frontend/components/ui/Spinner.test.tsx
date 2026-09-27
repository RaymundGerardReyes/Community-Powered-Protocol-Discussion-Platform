import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Spinner } from './Spinner';

describe('Spinner Component', () => {
  it('renders with accessible aria-label and spin animation', () => {
    render(<Spinner />);
    const spinner = screen.getByLabelText('Loading');
    expect(spinner).toBeInTheDocument();
    expect(spinner).toHaveClass('animate-spin', 'text-indigo-600', 'h-5', 'w-5');
  });

  it('allows custom className override', () => {
    render(<Spinner className="h-8 w-8 text-white" />);
    const spinner = screen.getByLabelText('Loading');
    expect(spinner).toHaveClass('animate-spin', 'h-8', 'w-8', 'text-white');
  });
});
