// Client-side Typesense client using a SEARCH-ONLY key (never the admin key).
import Typesense from "typesense";

export const typesenseClient = new Typesense.Client({
  nodes: [
    {
      host: process.env.NEXT_PUBLIC_TYPESENSE_HOST || "localhost",
      port: Number(process.env.NEXT_PUBLIC_TYPESENSE_PORT) || 8108,
      protocol: process.env.NEXT_PUBLIC_TYPESENSE_PROTOCOL || "http",
    },
  ],
  apiKey: process.env.NEXT_PUBLIC_TYPESENSE_SEARCH_KEY || "",
  connectionTimeoutSeconds: 2,
});
