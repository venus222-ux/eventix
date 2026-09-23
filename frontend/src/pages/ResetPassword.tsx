import { useState, FormEvent, useEffect } from "react";
import { useNavigate, useParams, useSearchParams } from "react-router-dom";
import API from "../api";
import { toast } from "react-toastify";
import { useStore } from "../store/useStore";

import styles from "../styles/ResetPassword.module.css";

export default function ResetPassword() {
  // Try to get token from path or query
  const { token: tokenParam } = useParams();
  const [searchParams] = useSearchParams();
  const email = searchParams.get("email") || "";
  const token = searchParams.get("token") || tokenParam || "";

  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [loading, setLoading] = useState(false);

  const navigate = useNavigate();

  // If token or email missing, show error immediately
  useEffect(() => {
    if (!token || !email) {
      toast.error("Invalid password reset link");
    }
  }, [token, email]);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (!token || !email) {
      toast.error("Invalid password reset link");
      return;
    }

    if (password !== passwordConfirmation) {
      toast.error("Passwords do not match");
      return;
    }

    if (password.length < 6) {
      toast.error("Password must be at least 6 characters");
      return;
    }

    setLoading(true);

    try {
      await API.post("/reset-password", {
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      });

      useStore.getState().logout();

      toast.success("Password reset successful! Please log in.");
      navigate("/login");
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        err?.response?.data?.error ||
        "Password reset failed. Please try again.";
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className={styles.wrapper}>
      <div className={styles.card}>
        <h2 className={styles.title}>
          <span>🔒</span> Reset Password
        </h2>

        <p className={styles.subtitle}>
          Enter your new password for <strong>{email || "your account"}</strong>
        </p>

        <form onSubmit={handleSubmit}>
          <input type="hidden" name="email" value={email} />

          <div className={styles.formGroup}>
            <input
              className={styles.input}
              type="password"
              placeholder="New password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={6}
              autoComplete="new-password"
              autoFocus
            />
          </div>

          <div className={styles.formGroup}>
            <input
              className={styles.input}
              type="password"
              placeholder="Confirm new password"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              required
              autoComplete="new-password"
            />
          </div>

          <button type="submit" className={styles.btn} disabled={loading}>
            {loading ? "Resetting..." : "Reset Password"}
          </button>
        </form>
      </div>
    </div>
  );
}
