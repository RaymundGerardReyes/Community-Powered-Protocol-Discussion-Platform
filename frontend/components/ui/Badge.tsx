import { cn } from '@/lib/cn';

interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info';
}

export function Badge({ variant = 'default', className, ...props }: BadgeProps) {
  const variantClass = {
    default: 'badge-neutral',
    success: 'badge-published',
    warning: 'badge-draft',
    danger: 'badge-deprecated',
    info: 'badge-info',
  }[variant];

  return (
    <span
      className={cn('badge', variantClass, className)}
      {...props}
    />
  );
}
