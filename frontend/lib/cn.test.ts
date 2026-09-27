import { describe, it, expect } from 'vitest';
import { cn } from './cn';

describe('cn utility', () => {
  it('concatenates single and multiple class names', () => {
    expect(cn('btn', 'btn-primary')).toBe('btn btn-primary');
  });

  it('filters out falsy and nullish values', () => {
    expect(cn('btn', false && 'btn-primary', null, undefined, '', 'active')).toBe('btn active');
  });

  it('merges conflicting Tailwind classes intelligently using twMerge', () => {
    expect(cn('px-2 py-1', 'px-4')).toBe('py-1 px-4');
    expect(cn('text-red-500', 'text-blue-500')).toBe('text-blue-500');
  });

  it('handles conditional class objects', () => {
    expect(cn('base', { 'is-active': true, 'is-hidden': false })).toBe('base is-active');
  });
});
