import { cn } from '@/lib/cn';

interface CardProps extends React.HTMLAttributes<HTMLDivElement> {
  hoverable?: boolean;
}

export function Card({ className, hoverable = false, ...props }: CardProps) {
  return (
    <div
      className={cn(
        hoverable ? 'card' : 'card-flat',
        'p-5',
        className,
      )}
      style={{
        background: 'var(--surface-card)',
        borderColor: 'var(--surface-overlay)',
        color: 'var(--text-primary)',
      }}
      {...props}
    />
  );
}
