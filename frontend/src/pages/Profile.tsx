import { useEffect, useState, ChangeEvent, FormEvent } from "react";
import API from "../api";
import { toast } from "react-toastify";
import { useStore } from "../store/useStore";
import styles from "../styles/Profile.module.css";

import type { ProfileData, ProfileFormData } from "@/types";

const Profile = () => {
  const [profile, setProfile] = useState<ProfileData | null>(null);

  const [formData, setFormData] = useState<ProfileFormData>({
    email: "",
    password: "",
    password_confirmation: "",
  });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    API.get("/profile")
      .then((res) => {
        const userData = res.data.data; // ← get the `data` key
        setProfile(userData);
        setFormData((prev) => ({ ...prev, email: userData.email || "" }));
        setLoading(false);
      })
      .catch(() => {
        toast.error("Failed to load profile");
        setError("Failed to load profile");
        setLoading(false);
      });
  }, []);

  const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
    setFormData((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleUpdate = (e: FormEvent) => {
    e.preventDefault();
    API.put("/profile", formData)
      .then((res) => toast.success(res.data.message))
      .catch((err) =>
        toast.error(err.response?.data?.message || "Update failed"),
      );
  };

  const handleDelete = async () => {
    if (!window.confirm("Are you sure?")) return;

    setDeleting(true);
    try {
      await API.delete("/profile");
      toast.success("Account deleted");
      useStore.getState().logout();
      window.location.replace("/login");
    } catch {
      toast.error("Failed to delete account");
    } finally {
      setDeleting(false);
    }
  };

  if (loading)
    return <div className={styles.loading}>Loading your profile...</div>;
  if (error)
    return (
      <div className={styles.container} style={{ color: "red" }}>
        {error}
      </div>
    );

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <span style={{ fontSize: "2rem" }}>👤</span>
        <h2 className={styles.title}>Account Settings</h2>
      </header>

      <section className={styles.infoSection}>
        <div>
          <span className={styles.infoLabel}>Email Address</span>
          <div className={styles.infoValue}>{profile?.email || "N/A"}</div>
        </div>
        <div>
          <span className={styles.infoLabel}>Member Since</span>
          <div className={styles.infoValue}>
            {profile?.created_at
              ? new Date(profile.created_at).toLocaleDateString()
              : "Unknown"}
          </div>
        </div>
      </section>

      <form onSubmit={handleUpdate} autoComplete="off">
        <div className={styles.formGroup}>
          <label className={styles.label}>Email</label>
          <input
            className={styles.input}
            name="email"
            type="email"
            value={formData.email}
            onChange={handleChange}
            required
          />
        </div>

        <div className={styles.formGroup}>
          <label className={styles.label}>New Password</label>
          <input
            type="password"
            className={styles.input}
            name="password"
            placeholder="Leave blank to keep current"
            value={formData.password}
            onChange={handleChange}
            autoComplete="new-password"
          />
        </div>

        <div className={styles.formGroup}>
          <label className={styles.label}>Confirm New Password</label>
          <input
            type="password"
            className={styles.input}
            name="password_confirmation"
            value={formData.password_confirmation}
            onChange={handleChange}
            autoComplete="new-password"
          />
        </div>

        <button type="submit" className={styles.btnPrimary}>
          Save Changes
        </button>
      </form>

      <div className={styles.dangerZone}>
        <h3 className={styles.dangerTitle}>Danger Zone</h3>
        <p
          style={{
            fontSize: "0.85rem",
            color: "#64748b",
            marginBottom: "1rem",
          }}
        >
          Once you delete your account, there is no going back. Please be
          certain.
        </p>
        <button
          className={styles.btnDanger}
          onClick={handleDelete}
          disabled={deleting}
        >
          {deleting ? "Deleting..." : "Delete Account"}
        </button>
      </div>
    </div>
  );
};

export default Profile;
