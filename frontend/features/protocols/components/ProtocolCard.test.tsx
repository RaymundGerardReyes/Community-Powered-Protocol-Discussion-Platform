import { render, screen } from "@testing-library/react";
import { describe, it, expect } from "vitest";
import { ProtocolCard } from "./ProtocolCard";

describe("ProtocolCard", () => {
  it("renders protocol title and author", () => {
    const mockProtocol = {
      id: 1,
      title: "Consensus Layer Upgrade",
      content: "Details about protocol upgrade...",
      tags: ["consensus"],
      author: "Alice",
      rating: 4.8,
      votes_count: 42,
      reviews_count: 5,
      created_at: "2026-09-24T00:00:00Z",
    };

    render(<ProtocolCard protocol={mockProtocol} />);
    expect(screen.getByText("Consensus Layer Upgrade")).toBeInTheDocument();
    expect(screen.getByText("By Alice")).toBeInTheDocument();
  });
});
