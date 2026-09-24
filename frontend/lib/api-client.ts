// Single source of truth for talking to the Laravel 13 REST API.
// No component should call fetch()/axios directly — import this instead.
import axios from "axios";

export const apiClient = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_BASE_URL,
  headers: { Accept: "application/json" },
  withCredentials: true,
});

// TODO: add response/error interceptors for consistent error shape and Sanctum CSRF.
