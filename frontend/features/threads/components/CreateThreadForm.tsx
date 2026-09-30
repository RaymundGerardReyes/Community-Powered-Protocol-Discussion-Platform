'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/features/auth/AuthContext';
import { createThread } from '@/features/threads/api';
import { Spinner } from '@/components/ui/Spinner';

interface CreateThreadFormProps {
  protocolId: number;
}

export function CreateThreadForm({ protocolId }: CreateThreadFormProps) {
  const router = useRouter();
  const { user, openAuthModal } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  function handleOpen() {
    if (!user) {
      openAuthModal();
      return;
    }
    setIsOpen(true);
    setError(null);
  }

  function handleCancel() {
    setIsOpen(false);
    setTitle('');
    setContent('');
    setError(null);
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!title.trim() || !content.trim()) return;

    setIsSubmitting(true);
    setError(null);

    try {
      await createThread({
        protocol_id: protocolId,
        title: title.trim(),
        content: content.trim(),
      });

      setTitle('');
      setContent('');
      setIsOpen(false);
      router.refresh();
    } catch (err: unknown) {
      const apiErr = err as { errors?: Record<string, string[]>; message?: string };
      const fieldError = apiErr?.errors ? Object.values(apiErr.errors).flat()[0] : null;
      setError(fieldError ?? apiErr?.message ?? 'Failed to post discussion thread.');
    } finally {
      setIsSubmitting(false);
    }
  }

  if (!isOpen) {
    return (
      <div className="mb-4 flex items-center justify-between">
        <p className="text-xs" style={{ color: 'var(--text-secondary)' }}>
          Have a question or insight regarding this protocol?
        </p>
        <button
          type="button"
          onClick={handleOpen}
          className="btn btn-sm btn-primary flex items-center gap-1.5"
        >
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          Start Discussion
        </button>
      </div>
    );
  }

  return (
    <div
      className="card-flat mb-6 p-5 transition-all"
      style={{
        background: 'var(--surface-card)',
        border: '1px solid var(--brand)',
        boxShadow: 'var(--shadow-md)',
      }}
    >
      <div className="mb-4 flex items-center justify-between">
        <h3 className="text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
          Post a Discussion Thread
        </h3>
        <button
          type="button"
          onClick={handleCancel}
          className="text-xs text-slate-400 hover:text-slate-600"
        >
          Cancel
        </button>
      </div>

      {error && (
        <div className="mb-4 rounded-lg p-3 text-xs text-red-700 bg-red-50 border border-red-200">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label htmlFor="thread-title" className="block text-xs font-medium mb-1" style={{ color: 'var(--text-primary)' }}>
            Thread Title
          </label>
          <input
            id="thread-title"
            type="text"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="e.g., Timing Morning Lux Exposure in Winter Climates"
            className="input w-full text-sm"
            required
            disabled={isSubmitting}
          />
        </div>

        <div>
          <label htmlFor="thread-content" className="block text-xs font-medium mb-1" style={{ color: 'var(--text-primary)' }}>
            Topic Details & Questions
          </label>
          <textarea
            id="thread-content"
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder="Describe your question, methodology adaptation, or observations..."
            rows={4}
            className="input w-full text-sm resize-y"
            required
            disabled={isSubmitting}
          />
        </div>

        <div className="flex items-center justify-end gap-2 pt-2">
          <button
            type="button"
            onClick={handleCancel}
            className="btn btn-sm btn-ghost"
            disabled={isSubmitting}
          >
            Cancel
          </button>
          <button
            type="submit"
            className="btn btn-sm btn-primary flex items-center gap-2"
            disabled={isSubmitting || !title.trim() || !content.trim()}
          >
            {isSubmitting && <Spinner className="h-3.5 w-3.5" />}
            {isSubmitting ? 'Posting...' : 'Publish Thread'}
          </button>
        </div>
      </form>
    </div>
  );
}
