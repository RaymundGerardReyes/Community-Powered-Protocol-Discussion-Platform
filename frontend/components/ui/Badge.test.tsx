import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Badge } from './Badge';

describe('Badge Component', () => {
  it('renders default badge with badge-neutral class', () => {
    render(<Badge>Default Badge</Badge>);
    const badge = screen.getByText('Default Badge');
    expect(badge).toBeInTheDocument();
    expect(badge).toHaveClass('badge', 'badge-neutral');
  });

  it('renders success badge with badge-published class', () => {
    render(<Badge variant="success">Published</Badge>);
    const badge = screen.getByText('Published');
    expect(badge).toHaveClass('badge', 'badge-published');
  });

  it('renders warning badge with badge-draft class', () => {
    render(<Badge variant="warning">Draft</Badge>);
    const badge = screen.getByText('Draft');
    expect(badge).toHaveClass('badge', 'badge-draft');
  });

  it('renders danger badge with badge-deprecated class', () => {
    render(<Badge variant="danger">Deprecated</Badge>);
    const badge = screen.getByText('Deprecated');
    expect(badge).toHaveClass('badge', 'badge-deprecated');
  });

  it('renders info badge with badge-info class', () => {
    render(<Badge variant="info">Info</Badge>);
    const badge = screen.getByText('Info');
    expect(badge).toHaveClass('badge', 'badge-info');
  });

  it('merges custom className properly', () => {
    render(<Badge className="custom-badge-style">Custom</Badge>);
    const badge = screen.getByText('Custom');
    expect(badge).toHaveClass('custom-badge-style');
  });
});
