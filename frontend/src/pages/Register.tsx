import { useState, FormEvent } from "react";
import { useNavigate, Link } from "react-router-dom";
import { toast } from "react-toastify";
import { register } from "../api";
import { useStore } from "../store/useStore";
import styles from "./Register.module.css";

import type { RegisterRequest } from "@/types";

export default function Register() {
  const [form, setForm] = useState<RegisterRequest>({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
  });
  const [loading, setLoading] = useState(false);

  const navigate = useNavigate();
  const setAuth = useStore((state) => state.setAuth);

  const passwordsMatch = form.password === form.password_confirmation;

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleRegister = async (e: FormEvent) => {
    e.preventDefault();

    if (!passwordsMatch) {
      toast.error("Passwords do not match");
      return;
    }

    if (form.password.length < 6) {
      toast.error("Password must be at least 6 characters");
      return;
    }

    setLoading(true);

    try {
      const response = await register(form);
      const { token, role } = response.data;

      setAuth(token, role);
      toast.success("Registration successful!");
      navigate(role === "admin" ? "/admin/dashboard" : "/dashboard");
    } catch (error: any) {
      if (error.response?.data?.errors) {
        const errors = error.response.data.errors;
        Object.keys(errors).forEach((key) => {
          toast.error(`${key}: ${errors[key][0]}`);
        });
      } else if (error.response?.data?.message) {
        toast.error(error.response.data.message);
      } else {
        toast.error("Registration failed. Please try again.");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className={styles.wrapper}>
      <div className={styles.card}>
        <div className={styles.header}>
          <h2 className={styles.title}>
            <span>📝</span> Create Account
          </h2>
          <p className={styles.subtitle}>Join our community today</p>
        </div>

        <form onSubmit={handleRegister}>
          {/* Name */}
          <div className={styles.formGroup}>
            <label className={styles.label}>Full Name</label>
            <input
              className={styles.input}
              placeholder="Enter your full name"
              name="name"
              value={form.name}
              onChange={handleChange}
              required
              autoComplete="name"
            />
          </div>

          {/* Email */}
          <div className={styles.formGroup}>
            <label className={styles.label}>Email Address</label>
            <input
              className={styles.input}
              type="email"
              placeholder="you@example.com"
              name="email"
              value={form.email}
              onChange={handleChange}
              required
              autoComplete="email"
            />
          </div>

          {/* Password */}
          <div className={styles.formGroup}>
            <label className={styles.label}>Password</label>
            <input
              className={styles.input}
              type="password"
              placeholder="Minimum 6 characters"
              name="password"
              value={form.password}
              onChange={handleChange}
              required
              minLength={6}
              autoComplete="new-password"
            />
          </div>

          {/* Confirm Password with live feedback */}
          <div className={styles.formGroup}>
            <label className={styles.label}>Confirm Password</label>
            <input
              className={styles.input}
              type="password"
              placeholder="Re-enter your password"
              name="password_confirmation"
              value={form.password_confirmation}
              onChange={handleChange}
              required
              autoComplete="new-password"
            />
            {form.password_confirmation && (
              <>
                {passwordsMatch ? (
                  <span className={styles.success}>✅ Passwords match</span>
                ) : (
                  <span className={styles.error}>
                    ❌ Passwords do not match
                  </span>
                )}
              </>
            )}
          </div>

          {/* Submit */}
          <button type="submit" className={styles.btn} disabled={loading}>
            {loading ? (
              <>
                <span
                  className={`${styles.spinner} spinner-border spinner-border-sm`}
                  role="status"
                  aria-hidden="true"
                />
                Creating Account...
              </>
            ) : (
              "Register"
            )}
          </button>
        </form>

        <div className={styles.linkContainer}>
          Already have an account?{" "}
          <Link to="/login" className={styles.link}>
            Sign In
          </Link>
        </div>
      </div>
    </div>
  );
}
