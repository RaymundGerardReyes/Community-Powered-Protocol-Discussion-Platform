import type { Protocol } from "@/types";

export function ProtocolCard({ protocol }: { protocol: Protocol }) {
  // Tailwind 4.3 card layout — title, tags, rating, votes_count
  return (
    <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm hover:shadow transition-shadow">
      <h3 className="text-lg font-semibold text-slate-900">{protocol.title}</h3>
      <p className="text-sm text-slate-600 line-clamp-2 mt-2">{protocol.content}</p>
      <div className="mt-4 flex items-center justify-between text-xs text-slate-500">
        <span>By {protocol.author}</span>
        <span>Rating: {protocol.rating} ★ ({protocol.reviews_count} reviews)</span>
      </div>
    </div>
  );
}
