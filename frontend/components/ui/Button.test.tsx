import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { Button } from './Button';

describe('Button Component', () => {
  it('renders with default primary variant and md size', () => {
    render(<Button>Click Me</Button>);
    const button = screen.getByRole('button', { name: /click me/i });
    expect(button).toBeInTheDocument();
    expect(button.className).toContain('btn');
    expect(button.className).toContain('btn-primary');
    expect(button.className).toContain('btn-md');
  });

  it('renders different variants correctly', () => {
    const { rerender } = render(<Button variant="secondary">Secondary</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-secondary');

    rerender(<Button variant="ghost">Ghost</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-ghost');

    rerender(<Button variant="danger">Danger</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-red-600/90');
  });

  it('renders different sizes correctly', () => {
    const { rerender } = render(<Button size="sm">Small</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-sm');

    rerender(<Button size="lg">Large</Button>);
    expect(screen.getByRole('button')).toHaveClass('btn-lg');
  });

  it('merges custom className and handles click events', () => {
    const handleClick = vi.fn();

    render(
      <Button className="custom-class" onClick={handleClick}>
        Custom Button
      </Button>,
    );

    const button = screen.getByRole('button');
    expect(button).toHaveClass('custom-class');

    fireEvent.click(button);
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it('respects disabled attribute', () => {
    const handleClick = vi.fn();

    render(
      <Button disabled onClick={handleClick}>
        Disabled Button
      </Button>,
    );

    const button = screen.getByRole('button');
    expect(button).toBeDisabled();

    fireEvent.click(button);
    expect(handleClick).not.toHaveBeenCalled();
  });
});
