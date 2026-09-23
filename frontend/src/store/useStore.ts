import { create } from "zustand";
import API from "../api";
import type { AppState } from "@/types";

export const useStore = create<AppState>((set, get) => {
  let intervalId: ReturnType<typeof setInterval> | null = null;

  return {
    isAuth: false,
    token: null,
    role: null,
    initialized: false,
    theme: (localStorage.getItem("theme") as "light" | "dark") || "light",

    setAuth: (token, role) =>
      set({
        isAuth: true,
        token,
        role,
      }),

    logout: () => {
      if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
      }

      set({
        isAuth: false,
        token: null,
        role: null,
      });
    },

    setToken: (token) =>
      set((state) => ({
        token,
        isAuth: !!token,
        role: token ? state.role : null,
      })),

    toggleTheme: () => {
      set((state) => {
        const newTheme = state.theme === "light" ? "dark" : "light";
        localStorage.setItem("theme", newTheme);
        document.documentElement.setAttribute("data-bs-theme", newTheme);
        return { theme: newTheme };
      });
    },

    setInitialized: (value) => set({ initialized: value }),

    stopTokenRefreshLoop: () => {
      if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
      }
    },

    startTokenRefreshLoop: () => {
      if (intervalId) return;

      const refreshInterval = 1000 * 60 * 10; // 10 min

      intervalId = setInterval(async () => {
        try {
          const res = await API.post("/refresh");
          const { token, role } = res.data;

          get().setAuth(token, role);
        } catch (err) {
          console.error("Failed to refresh token:", err);
          get().logout();
        }
      }, refreshInterval);
    },
  };
});
