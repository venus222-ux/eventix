
import { Link, NavLink, useNavigate } from "react-router-dom";
import { useStore } from "../store/useStore";
import { useCartStore } from "../store/useCartStore";
import { logoutRequest } from "../api";
import styles from "./Navbar.module.css";

export default function Navbar() {
  const { isAuth, initialized, logout, theme, toggleTheme } = useStore();
  const navigate = useNavigate();

  // IMPORTANT:
  // Aceste hook-uri trebuie să fie înainte de if (!initialized)
  // pentru a păstra aceeași ordine a hook-urilor la fiecare render.
  const cartCount = useCartStore((s) => s.seats.length);
  const hasPending = useCartStore((s) => !!s.pendingOrder);

  if (!initialized) {
    // optional: show empty navbar, spinner, or skeleton
    return (
      <div
        className={`${styles.navWrapper} ${
          theme === "dark" ? styles.dark : ""
        }`}
      >
        <nav className={styles.glassNav}>
          <span>Loading...</span>
        </nav>
      </div>
    );
  }

  const handleLogout = async () => {
    try {
      await logoutRequest();
    } catch {
    } finally {
      // Clear cart + pending reservation before logging out.
      useCartStore.getState().reset();

      logout();
      navigate("/login");
    }
  };

  return (
    <div
      className={`${styles.navWrapper} ${
        theme === "dark" ? styles.dark : ""
      }`}
    >
      <nav className={styles.glassNav}>
        <Link className={styles.brand} to="/">
          <span className={styles.brandIcon}>⚡</span>
          <span className={styles.brandText}>MESSENGER</span>
        </Link>

        <div className={styles.navGroup}>
          {isAuth ? (
            <>
              <NavLink
                to="/dashboard"
                className={({ isActive }) =>
                  `${styles.link} ${
                    isActive ? styles.activeLink : ""
                  }`
                }
              >
                Dashboard
              </NavLink>

              <NavLink
                to="/events"
                className={({ isActive }) =>
                  `${styles.link} ${
                    isActive ? styles.activeLink : ""
                  }`
                }
              >
                Events
              </NavLink>

              <NavLink
                to="/profile"
                className={({ isActive }) =>
                  `${styles.link} ${
                    isActive ? styles.activeLink : ""
                  }`
                }
              >
                Profile
              </NavLink>
            </>
          ) : (
            <>
              <NavLink
                to="/login"
                className={({ isActive }) =>
                  `${styles.link} ${
                    isActive ? styles.activeLink : ""
                  }`
                }
              >
                Login
              </NavLink>

              <NavLink
                to="/register"
                className={({ isActive }) =>
                  `${styles.link} ${
                    isActive ? styles.activeLink : ""
                  }`
                }
              >
                Register
              </NavLink>
            </>
          )}
        </div>

        <div className={styles.controls}>
          {/* Cart */}
          {isAuth && (
            <NavLink
              to="/cart"
              className={styles.iconBtn}
              aria-label="Cart"
              title="Cart"
              style={{ position: "relative" }}
            >
              🛒

              {(cartCount > 0 || hasPending) && (
                <span className="badge rounded-pill text-bg-danger position-absolute top-0 start-100 translate-middle">
                  {cartCount > 0 ? cartCount : "!"}
                </span>
              )}
            </NavLink>
          )}

          {/* Theme */}
          <button
            className={styles.iconBtn}
            onClick={toggleTheme}
            aria-label={`Switch to ${
              theme === "light" ? "dark" : "light"
            } mode`}
            title="Toggle theme"
          >
            {theme === "light" ? "🌙" : "☀️"}
          </button>

          {/* Logout */}
          {isAuth && (
            <button
              className={styles.logoutBtn}
              onClick={handleLogout}
            >
              Logout
            </button>
          )}
        </div>
      </nav>
    </div>
  );
}

