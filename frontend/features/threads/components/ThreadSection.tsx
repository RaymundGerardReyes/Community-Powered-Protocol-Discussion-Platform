'use client';

import { useState, useEffect } from 'react';
import type { Thread } from '@/types';
import { CreateThreadForm } from './CreateThreadForm';
import { ThreadCard } from './ThreadCard';

interface ThreadSectionProps {
  protocolId: number;
  initialThreads: Thread[];
}

export function ThreadSection({ protocolId, initialThreads }: ThreadSectionProps) {
  const [threads, setThreads] = useState<Thread[]>(initialThreads);

  useEffect(() => {
    setThreads(initialThreads);
  }, [initialThreads]);

  function handleThreadCreated(newThread: Thread) {
    setThreads((prev) => {
      // Prevent duplicates if already present
      if (prev.some((t) => t.id === newThread.id)) return prev;
      return [newThread, ...prev];
    });
  }

  return (
    <section className="mb-10">
      <div className="flex items-center justify-between mb-4">
        <h2 className="section-title mb-0">
          Discussion Threads
          <span
            className="badge badge-info ml-2"
            style={{ borderRadius: '999px', verticalAlign: 'middle' }}
          >
            {threads.length}
          </span>
        </h2>
      </div>

      <CreateThreadForm protocolId={protocolId} onThreadCreated={handleThreadCreated} />

      {threads.length > 0 ? (
        <ul className="space-y-3">
          {threads.map((thread) => (
            <li key={thread.id}>
              <ThreadCard thread={thread} />
            </li>
          ))}
        </ul>
      ) : (
        <div
          className="rounded-xl p-8 text-center text-sm italic"
          style={{
            background: 'var(--surface-card)',
            border: '1px solid var(--border)',
            color: 'var(--text-muted)',
          }}
        >
          No discussion threads yet.
        </div>
      )}
    </section>
  );
}
