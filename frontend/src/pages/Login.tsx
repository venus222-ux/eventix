import { useState, FormEvent } from "react";
import { useNavigate, Link } from "react-router-dom";
import { toast } from "react-toastify";
import { useStore } from "../store/useStore";
import { login } from "../api";
import styles from "../styles/Login.module.css";

export default function Login() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const navigate = useNavigate();
  const setAuth = useStore((state) => state.setAuth);
  const startTokenRefreshLoop = useStore(
    (state) => state.startTokenRefreshLoop,
  );

  const handleLogin = async (e: FormEvent) => {
    e.preventDefault();

    try {
      const res = await login({ email, password });
      const { token, role } = res.data;

      setAuth(token, role);
      startTokenRefreshLoop();

      toast.success("Welcome back! 👋");

      navigate(role === "admin" ? "/admin/dashboard" : "/dashboard");
    } catch (err: any) {
      console.error(err);

      let msg = "Login failed. Please try again.";

      if (err.response) {
        // The request was made and the server responded with a status code outside 2xx
        if (err.response.status === 401) {
          msg = "The credentials are incorrect.";
        } else if (err.response.data?.message) {
          msg = err.response.data.message;
        }
      } else if (err.request) {
        // Request was made but no response received
        msg = "Network error. Please check your connection.";
      } else {
        // Something else happened
        msg = err.message || msg;
      }

      toast.error(msg);
    }
  };

  return (
    <div className={styles.wrapper}>
      <div className={styles.card}>
        <h2 className={styles.title}>
          <span>🔐</span> Sign In
        </h2>

        <form onSubmit={handleLogin}>
          <div className={styles.formGroup}>
            <input
              className={styles.input}
              type="email"
              placeholder="Email address"
              value={email}
              onChange={(e) => setEmail(e.target.value.trim())}
              required
              autoFocus
              autoComplete="email"
            />
          </div>

          <div className={styles.formGroup}>
            <input
              className={styles.input}
              type="password"
              placeholder="Password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              autoComplete="current-password"
            />
          </div>

          <button type="submit" className={styles.btn}>
            Log In
          </button>
        </form>

        <div className={styles.links}>
          <Link to="/register" className={styles.link}>
            Create account
          </Link>

          <Link to="/forgot-password" className={styles.link}>
            Forgot password?
          </Link>
        </div>
      </div>
    </div>
  );
}
