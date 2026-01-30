import { Link, usePage } from "@inertiajs/react";

export default function Sidebar({ isOpen = true }) {
    const { auth } = usePage().props;
    const role = auth?.role;

    const menusByRole = {
        "Master Admin": [
            { label: "Dashboard", icon: "fa fa-home", href: "/dashboard" },
            {
                label: "Company Master",
                icon: "fa fa-building",
                href: route("company-master.index"),
            },
            {
                label: "Branch Master",
                icon: "fa fa-code-branch",
                href: route("branch-master.index"),
            },
            {
                label: "State Master",
                icon: "fa fa-flag",
                href: route("state-master.index"),
            },
            {
                label: "City Master",
                icon: "fa fa-city",
                href: route("city-master.index"),
            },
            {
                label: "Area Master",
                icon: "fa fa-location",
                href: route("area-master.index"),
            },
            {
                label: "Department Master",
                icon: "fa fa-building",
                href: "/department-masters",
            },
            {
                label: "Category One",
                icon: "fa fa-tags",
                href: "/category-one-masters",
            },
            {
                label: "Category Two",
                icon: "fa fa-tags",
                href: "/category-two-masters",
            },
            {
                label: "Category Three",
                icon: "fa fa-tags",
                href: "/category-three-masters",
            },
            {
                label: "Product Category",
                icon: "fa fa-cubes",
                href: "/product-category-masters",
            },
            {
                label: "Store Master",
                icon: "fa fa-store",
                href: route("store-master.index"),
            },
            {
                label: "Product Master",
                icon: "fa fa-box",
                href: route("product-master.index"),
            },
            {
                label: "Store Products",
                icon: "fa fa-product",
                href: route("store-product.index"),
            },
            {
                label: "Offer Master",
                icon: "fa fa-gift",
                href: route("offer-master.index"),
            },
            {
                label: "Question Master",
                icon: "fa fa-question-circle",
                href: route("question-master.index"),
            },
            {
                label: "Employee Master",
                icon: "fa fa-user",
                href: route("employee-master.index"),
            },
            {
                label: "Employee Target",
                icon: "fa fa-bullseye",
                href: route("employee-target.index"),
            },
            { label: "Reports", icon: "fa fa-file", href: "/" },
        ],
        "Country Head": [
            { label: "Dashboard", icon: "fa fa-home", href: "/dashboard" },
            { label: "Zone Master", icon: "fa fa-map", href: "/" },
            { label: "State Master", icon: "fa fa-flag", href: "/" },
            { label: "Reports", icon: "fa fa-file", href: "/" },
        ],
        "Sales Employee": [
            { label: "Dashboard", icon: "fa fa-home", href: "/dashboard" },
            { label: "My Stores", icon: "fa fa-store", href: "/" },
            { label: "Sales Entry", icon: "fa fa-plus", href: "/" },
        ],
    };

    const menus = menusByRole[role] || [];

    return (
        <>
            <div className={`sidebar ${isOpen ? "open" : ""}`}>
                {/* Logo fixed at top */}
                <div className="sidebar-logo">
                    <img src="/assets/img/wd_logo.png" alt="Logo" />
                </div>

                {/* Scrollable menu */}
                <div className="sidebar-menu-wrapper">
                    <ul className="sidebar-menu">
                        {menus.map((item, i) => (
                            <li key={i}>
                                <Link href={item.href} className="sidebar-link">
                                    <i className={item.icon}></i>
                                    <span>{item.label}</span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            <style jsx>{`
                .sidebar-logo {
                    flex-shrink: 0;
                    text-align: center;
                }

                .sidebar .sidebar-logo {
                    position: fixed;
                    height: 70px !important;
                    width: 120px !important;
                    padding: 0 !important;
                }

                .sidebar-logo img {
                    width: 80px;
                    margin-left: 41%;
                }

                .sidebar-menu-wrapper {
                    flex: 1; /* take remaining height */
                    overflow-y: auto; /* scroll menu if content too long */
                    padding: 20px 0;
                }

                .sidebar .sidebar-menu {
                    margin-top: 66px;
                    padding: 0;
                }

                .sidebar .sidebar-menu .sidebar-menu {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                }

                .sidebar-link {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 20px;
                    color: #ffffffcc;
                    text-decoration: none;
                    border-radius: 8px;
                    transition: 0.2s;
                }

                .sidebar-link:hover {
                    background: #111;
                    color: #fff;
                }

                .sidebar-link i {
                    width: 20px;
                }

                .sidebar {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 200px;
                    height: 100vh;
                    background: #000;
                    color: #fff;
                    display: flex;
                    flex-direction: column;
                    transition: all 0.3s ease;
                    z-index: 1000;
                }

                /* Mobile (<992px) */
                @media (max-width: 992px) {
                    .sidebar {
                        width: 0; /* hidden by default */
                        overflow: hidden;
                        position: fixed;
                        top: 0;
                        left: 0;
                        height: 100vh;
                        background: #000; /* same dark background */
                        z-index: 1100; /* above page content */
                        transition: width 0.3s ease;
                        margin-left: 0 !important; /* remove old -575px */
                    }

                    .sidebar.open {
                        width: 190px; /* toggle width */
                    }
                }
            `}</style>
        </>
    );
}
