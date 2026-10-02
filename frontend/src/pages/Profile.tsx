import { useEffect, useState, ChangeEvent, FormEvent } from "react";
import { Country } from "country-state-city";
import API from "../api";
import { toast } from "react-toastify";
import { useStore } from "../store/useStore";
import BillingForm from "../components/BillingForm";
import styles from "../styles/Profile.module.css";

import type { ProfileData, ProfileFormData } from "@/types";

const Profile = () => {
  const [profile, setProfile] = useState<ProfileData | null>(null);

  const [formData, setFormData] = useState<ProfileFormData>({
    email: "",
    password: "",
    password_confirmation: "",
    billing_country: "",
    billing_city: "",
    billing_postal_code: "",
    billing_street: "",
  });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [deleting, setDeleting] = useState(false);

  // Stare pentru activare/dezactivare formular editare adresă
  const [isEditingBilling, setIsEditingBilling] = useState(false);

  useEffect(() => {
    API.get("/profile")
      .then((res) => {
        const userData = res.data.data;
        setProfile(userData);
        setFormData((prev) => ({
          ...prev,
          email: userData.email || "",
          billing_country: userData.billing_country || "",
          billing_city: userData.billing_city || "",
          billing_postal_code: userData.billing_postal_code || "",
          billing_street: userData.billing_street || "",
        }));
        setLoading(false);
      })
      .catch(() => {
        toast.error("Failed to load profile");
        setError("Failed to load profile");
        setLoading(false);
      });
  }, []);

  const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  // Revenire la adresa salvată (buton "Renunță")
  const cancelBillingEdit = () => {
    setFormData((prev) => ({
      ...prev,
      billing_country: profile?.billing_country ?? "",
      billing_city: profile?.billing_city ?? "",
      billing_postal_code: profile?.billing_postal_code ?? "",
      billing_street: profile?.billing_street ?? "",
    }));
    setIsEditingBilling(false);
  };

  const handleUpdate = (e: FormEvent) => {
    e.preventDefault();
    API.put("/profile", formData)
      .then((res) => {
        toast.success(res.data.message || "Profile updated successfully");
        setProfile((prev) =>
          prev
            ? {
                ...prev,
                email: formData.email,
                billing_country: formData.billing_country,
                billing_city: formData.billing_city,
                billing_postal_code: formData.billing_postal_code,
                billing_street: formData.billing_street,
              }
            : null
        );
        setFormData((prev) => ({
          ...prev,
          password: "",
          password_confirmation: "",
        }));
        setIsEditingBilling(false);
      })
      .catch((err) =>
        toast.error(err.response?.data?.message || "Update failed")
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

  const hasBillingAddress = Boolean(
    profile?.billing_country ||
      profile?.billing_city ||
      profile?.billing_street
  );

  const getCountryName = (code?: string) => {
    if (!code) return "";
    const country = Country.getCountryByCode(code);
    return country ? country.name : code;
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
        {/* Email & Password */}
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

        {/* Billing Information Section */}
        <div className="card mt-4 p-3 shadow-sm border">
          <div className="d-flex justify-content-between align-items-center mb-2">
            <h3 className="m-0 fs-5 fw-bold">Minimal Billing Information</h3>

            {!isEditingBilling ? (
              hasBillingAddress ? (
                <button
                  type="button"
                  className="btn btn-sm btn-link text-decoration-none"
                  onClick={() => setIsEditingBilling(true)}
                >
                  ✏️ Editează adresa
                </button>
              ) : (
                <button
                  type="button"
                  className="btn btn-sm btn-outline-primary"
                  onClick={() => setIsEditingBilling(true)}
                >
                  + Adaugă adresă
                </button>
              )
            ) : (
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary"
                onClick={cancelBillingEdit}
              >
                Renunță
              </button>
            )}
          </div>

          <p className="text-muted small mb-3">
            Required for VAT validation and invoice generation.
          </p>

          {/* Vizualizare adresă salvată */}
          {!isEditingBilling && (
            <div className="bg-light p-3 rounded border">
              {hasBillingAddress ? (
                <div className="d-flex flex-column gap-1">
                  <div>
                    <strong>Țară:</strong>{" "}
                    {getCountryName(profile?.billing_country)}
                  </div>
                  <div>
                    <strong>Oraș:</strong> {profile?.billing_city || "N/A"}
                  </div>
                  {profile?.billing_postal_code && (
                    <div>
                      <strong>Cod Poștal:</strong> {profile.billing_postal_code}
                    </div>
                  )}
                  {profile?.billing_street && (
                    <div>
                      <strong>Stradă:</strong> {profile.billing_street}
                    </div>
                  )}
                </div>
              ) : (
                <div className="text-muted small">
                  Nu ai nicio adresă de facturare salvată. Apasă pe{" "}
                  <strong>+ Adaugă adresă</strong> pentru a introduce datele.
                </div>
              )}
            </div>
          )}

          {/* Formular de editare adresă */}
          {isEditingBilling && (
            <BillingForm
              value={{
                billing_country: formData.billing_country ?? "",
                billing_city: formData.billing_city ?? "",
                billing_postal_code: formData.billing_postal_code ?? "",
                billing_street: formData.billing_street ?? "",
              }}
              onChange={(b) => setFormData((prev) => ({ ...prev, ...b }))}
            />
          )}
        </div>

        <button type="submit" className={`${styles.btnPrimary} mt-4`}>
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
