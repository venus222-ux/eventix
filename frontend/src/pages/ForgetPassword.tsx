import { useState, FormEvent } from "react";
import { Link } from "react-router-dom";
import API from "../api";
import { toast } from "react-toastify";

import styles from "../styles/ForgotPassword.module.css";

export default function ForgotPassword() {
  const [email, setEmail] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (!email.trim()) {
      toast.error("Please enter your email");
      return;
    }

    setIsSubmitting(true);

    try {
      await API.post("/forgot-password", { email: email.trim() });
      toast.success("Reset link sent! Check your inbox.");
      setEmail(""); // optional: clear field after success
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        "Failed to send reset link. Please try again.";
      toast.error(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className={styles.wrapper}>
      <div className={styles.card}>
        <h2 className={styles.title}>
          <span>🔑</span> Forgot Password
        </h2>

        <p className={styles.subtitle}>
          Enter your email and we'll send you a link to reset your password.
        </p>

        <form onSubmit={handleSubmit}>
          <div className={styles.formGroup}>
            <input
              className={styles.input}
              type="email"
              placeholder="your@email.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoComplete="email"
              autoFocus
              disabled={isSubmitting}
            />
          </div>

          <button type="submit" className={styles.btn} disabled={isSubmitting}>
            {isSubmitting ? "Sending..." : "Send Reset Link"}
          </button>
        </form>

        <div className={styles.info}>
          Remember your password? <Link to="/login">Back to Sign In</Link>
        </div>
      </div>
    </div>
  );
}
