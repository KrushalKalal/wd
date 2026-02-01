import { useState, useRef, useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";

export default function Header({ toggleSidebar }) {
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const dropdownRef = useRef();

    const { auth } = usePage().props;
    const user = auth?.user || { name: "Admin", email: "" };

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (
                dropdownRef.current &&
                !dropdownRef.current.contains(e.target)
            ) {
                setIsDropdownOpen(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        return () =>
            document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    return (
        <>
            <header className="header d-flex align-items-center justify-content-between">
                {/* LEFT SIDE */}
                <div className="d-flex align-items-center gap-3">
                    {/* Sidebar Toggle */}
                    <button
                        className="btn btn-sm border-0 d-lg-none"
                        onClick={toggleSidebar}
                    >
                        <i className="fa fa-bars fa-lg"></i>
                    </button>

                    {/* Logo */}
                    <Link href={route("dashboard")}>
                        <img
                            src="/assets/img/wd_logo.png"
                            alt="Logo"
                            style={{ height: "45px", width: "45px" }}
                        />
                    </Link>
                </div>

                {/* RIGHT SIDE */}
                <div className="d-flex align-items-center gap-3">
                    {/* Notification Icon example */}
                    {/* <button className="btn btn-sm position-relative border-0 bg-transparent">
                        <i className="fa fa-bell fa-lg"></i>
                        <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                        </span>
                    </button> */}

                    {/* User Dropdown */}
                    <div className="dropdown" ref={dropdownRef}>
                        <button
                            className="btn border-0 bg-transparent d-flex align-items-center"
                            onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                        >
                            <span className="avatar avatar-sm">
                                <img
                                    src="/assets/img/profiles/avatar-12.jpg"
                                    alt={user.name}
                                    className="rounded-circle"
                                />
                            </span>
                        </button>

                        {isDropdownOpen && (
                            <div className="dropdown-menu show shadow position-absolute end-0 mt-2">
                                <div className="card mb-0">
                                    <div className="card-header d-flex align-items-center gap-2">
                                        <span className="avatar avatar-md">
                                            <img
                                                src="/assets/img/profiles/avatar-12.jpg"
                                                alt={user.name}
                                                className="rounded-circle"
                                            />
                                        </span>
                                        <div>
                                            <h6 className="mb-0">
                                                {user.name}
                                            </h6>
                                            <small>{user.email}</small>
                                        </div>
                                    </div>

                                    <div className="card-footer p-2">
                                        <Link
                                            href={route("logout")}
                                            method="post"
                                            as="button"
                                            className="dropdown-item text-danger"
                                        >
                                            <i className="fa fa-sign-out me-2"></i>
                                            Logout
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            <style jsx>{`
                .header {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 60px;
                    background: #fff;
                    padding: 0 20px;
                    z-index: 999;
                    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
                }
            `}</style>
        </>
    );
}
