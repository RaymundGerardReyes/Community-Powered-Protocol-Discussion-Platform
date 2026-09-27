import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import React from 'react';
import { SearchBar } from '@/features/protocols/components/SearchBar';

const pushMock = vi.fn();
const mockParams = new URLSearchParams('');

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: pushMock }),
  usePathname: () => '/protocols',
  useSearchParams: () => mockParams,
}));

describe('SearchBar Component Integration', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders search input with accessible label and non-interfering padding', () => {
    render(<SearchBar />);
    const input = screen.getByPlaceholderText(/search protocols by name/i);
    expect(input).toBeInTheDocument();
    expect(input).toHaveAttribute('type', 'search');
    // Ensure padding prevents collision with magnifying glass icon
    expect(input.style.paddingLeft).toBe('2.5rem');
  });

  it('navigates with search parameter on form submit', () => {
    render(<SearchBar />);
    const input = screen.getByPlaceholderText(/search protocols by name/i);
    fireEvent.change(input, { target: { value: 'ZK-Rollup' } });
    fireEvent.submit(input.closest('form')!);

    expect(pushMock).toHaveBeenCalledWith('/protocols?search=ZK-Rollup');
  });

  it('displays clear button when input has text and clears on click', () => {
    render(<SearchBar />);
    const input = screen.getByPlaceholderText(/search protocols by name/i);
    expect(screen.queryByRole('button', { name: /clear search/i })).not.toBeInTheDocument();

    fireEvent.change(input, { target: { value: 'DeFi' } });
    const clearBtn = screen.getByRole('button', { name: /clear search/i });
    expect(clearBtn).toBeInTheDocument();

    fireEvent.click(clearBtn);
    expect(input).toHaveValue('');
    expect(pushMock).toHaveBeenCalledWith('/protocols?');
  });
});
