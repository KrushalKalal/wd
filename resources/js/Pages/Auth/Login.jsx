import { useEffect, useState } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";
import AlertModal from "../AlertModel";

export default function Login({ status, errors: serverErrors }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    // AlertModal ke liye state
    const [alert, setAlert] = useState({
        show: false,
        type: "error",
        message: "",
    });

    useEffect(() => {
        return () => reset("password");
    }, []);

    // Backend se aaya error (serverErrors.email) ko AlertModal me dikhao
    useEffect(() => {
        if (serverErrors?.email) {
            setAlert({
                show: true,
                type: "error",
                message: serverErrors.email,
            });
        }
    }, [serverErrors]);

    const handleSubmit = (e) => {
        e.preventDefault();
        // Submit karne se pehle alert reset kar do
        setAlert({ show: false, type: "error", message: "" });
        post(route("login"), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleAlertClose = () => {
        setAlert({ show: false, type: "error", message: "" });
    };

    return (
        <GuestLayout title="Log in">
            <Head title="Log in" />

            <div className="container-fluid p-0">
                <div className="row g-0 vh-98.5">
                    {/* Right Panel (Form) */}
                    <div className="col-lg-12 d-flex align-items-center justify-content-center">
                        <div className="w-100" style={{ maxWidth: 500 }}>
                            <form onSubmit={handleSubmit} className="auth-card">
                                {/* Logo Centered */}
                                <div className="text-center mb-2 d-flex justify-content-center">
                                    <img
                                        src="/assets/img/wd_logo.png"
                                        alt="logo"
                                        className="logo-sm mx-auto"
                                    />
                                </div>

                                <div className="mb-3 text-center">
                                    <h2 className="mb-1">Sign in</h2>
                                    <p className="text-muted mb-0">
                                        Enter your details to access your
                                        account
                                    </p>
                                </div>

                                {/* Success message (like "Logged out successfully") */}
                                {status && (
                                    <div className="alert alert-success">
                                        {status}
                                    </div>
                                )}

                                {/* Email */}
                                <div className="mb-3">
                                    <label className="form-label">
                                        Email address
                                    </label>
                                    <input
                                        type="email"
                                        className={`form-control ${
                                            errors.email ? "is-invalid" : ""
                                        }`}
                                        value={data.email}
                                        onChange={(e) =>
                                            setData("email", e.target.value)
                                        }
                                        required
                                        autoFocus
                                    />
                                    {errors.email && (
                                        <div className="invalid-feedback">
                                            {errors.email}
                                        </div>
                                    )}
                                </div>

                                {/* Password */}
                                <div className="mb-3">
                                    <label className="form-label">
                                        Password
                                    </label>
                                    <div className="position-relative">
                                        <input
                                            type="password"
                                            className={`form-control ${
                                                errors.password
                                                    ? "is-invalid"
                                                    : ""
                                            }`}
                                            value={data.password}
                                            onChange={(e) =>
                                                setData(
                                                    "password",
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <button
                                            type="button"
                                            className="password-toggle"
                                            tabIndex="-1"
                                            aria-hidden
                                        >
                                            <i className="ti ti-eye-off" />
                                        </button>
                                        {errors.password && (
                                            <div className="invalid-feedback">
                                                {errors.password}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Remember + Forgot */}
                                <div className="d-flex align-items-center justify-content-between mb-3">
                                    <div className="form-check">
                                        <input
                                            type="checkbox"
                                            className="form-check-input"
                                            checked={data.remember}
                                            onChange={(e) =>
                                                setData(
                                                    "remember",
                                                    e.target.checked,
                                                )
                                            }
                                        />
                                        <label className="form-check-label">
                                            Remember me
                                        </label>
                                    </div>
                                    <Link
                                        href={route("password.request")}
                                        className="text-decoration-none"
                                        style={{ color: "#000" }}
                                    >
                                        Forgot password?
                                    </Link>
                                </div>

                                {/* Button */}
                                <div className="mb-3">
                                    <button
                                        type="submit"
                                        className="btn btn-primary w-100"
                                        disabled={processing}
                                    >
                                        Sign In
                                    </button>
                                </div>

                                <div className="text-center mt-3">
                                    <small style={{ color: "#000" }}>
                                        © {new Date().getFullYear()}{" "}
                                        <strong style={{ color: "#000" }}>
                                            Wild Drum Beverages
                                        </strong>
                                    </small>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {/* AlertModal - Backend errors (deactivated, wrong credentials, etc.) */}
            <AlertModal
                show={alert.show}
                type={alert.type}
                message={alert.message}
                onClose={handleAlertClose}
            />

            {/* Styles */}
            <style jsx>{`
                :root {
                    --muted: #6c757d;
                }

                .auth-card {
                    background: rgba(255, 255, 255, 0.92);
                    border-radius: 14px;
                    padding: 38px;
                    box-shadow: 0 12px 40px rgba(10, 61, 98, 0.09);
                    border: 1px solid rgba(10, 61, 98, 0.08);
                }

                .logo-sm {
                    width: 110px;
                    display: block;
                }

                .form-label {
                    font-weight: 600;
                    color: #000000;
                }

                /* Updated Navy Button */
                .btn-primary {
                    background: #000000 !important;
                    border-color: #000000 !important;
                    padding: 10px 14px;
                    font-weight: 600;
                    border-radius: 8px;
                }
                .btn-primary:hover,
                .btn-primary:focus {
                    background: #111111 !important;
                    border-color: #111111 !important;
                }

                /* Password icon button */
                .password-toggle {
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    color: var(--muted);
                    font-size: 1rem;
                }

                @media (max-width: 991px) {
                    .auth-card {
                        margin: 32px 14px;
                        padding: 32px;
                    }
                }

                @media (max-width: 480px) {
                    .auth-card {
                        padding: 24px;
                    }
                }
            `}</style>
        </GuestLayout>
    );
}
