// hooks/useAuthRestore.ts
import { useEffect, useRef } from "react";
import { useLocation } from "react-router-dom";
import { useStore } from "../store/useStore";
import { refreshToken } from "../api";

const publicRoutes = ["/login", "/register", "/forgot-password"];

export const useAuthRestore = () => {
  const location = useLocation();
  const { setAuth, setInitialized, startTokenRefreshLoop, logout } = useStore();
  const hasRun = useRef(false);

  useEffect(() => {
    if (hasRun.current) return;
    hasRun.current = true;

    const isPublic = publicRoutes.some(
      (route) =>
        location.pathname === route ||
        location.pathname.startsWith(route + "/"),
    );

    if (isPublic) {
      setInitialized(true); // ✅ important: set initialized to true on public routes
      return;
    }

    const restore = async () => {
      try {
        const res = await refreshToken();
        setAuth(res.data.token, res.data.role);
        startTokenRefreshLoop();
      } catch (err: any) {
        if (err.response?.status === 401) {
          logout();
        }
      } finally {
        setInitialized(true); // ✅ always set initialized after the attempt
      }
    };

    restore();
  }, [
    location.pathname,
    setAuth,
    setInitialized,
    startTokenRefreshLoop,
    logout,
  ]);
};
