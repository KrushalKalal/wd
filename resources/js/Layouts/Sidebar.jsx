import { Link, usePage } from "@inertiajs/react";
import { useState } from "react";

export default function Sidebar({ isOpen = true }) {
    const { auth, url } = usePage().props;
    const role = auth?.role;
    const currentUrl = url;

    // Track which category is expanded
    const [expandedCategories, setExpandedCategories] = useState({});

    const toggleCategory = (categoryLabel) => {
        setExpandedCategories((prev) => ({
            ...prev,
            [categoryLabel]: !prev[categoryLabel],
        }));
    };

    const menusByRole = {
        "Master Admin": [
            {
                label: "Dashboard",
                icon: "fa fa-home",
                href: "/dashboard",
                type: "single",
            },
            {
                label: "Location Masters",
                icon: "fa fa-map-marker-alt",
                type: "category",
                items: [
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
                ],
            },
            {
                label: "Company & Branch",
                icon: "fa fa-building",
                type: "category",
                items: [
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
                ],
            },
            {
                label: "Employee Masters",
                icon: "fa fa-users",
                type: "category",
                items: [
                    {
                        label: "Department Master",
                        icon: "fa fa-building",
                        href: "/department-masters",
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
                ],
            },
            {
                label: "Store & Products",
                icon: "fa fa-store",
                type: "category",
                items: [
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
                        icon: "fa fa-boxes-stacked",
                        href: route("store-product.index"),
                    },

                    {
                        label: "Stock Approval",
                        icon: "fa fa-boxes-stacked",
                        href: route("stock-approvals.index"),
                    },
                    {
                        label: "Store Visit",
                        icon: "fa fa-boxes-stacked",
                        href: route("store-visits.index"),
                    },
                ],
            },
            {
                label: "Category Masters",
                icon: "fa fa-tags",
                type: "category",
                items: [
                    {
                        label: "Product Category",
                        icon: "fa fa-cubes",
                        href: "/product-category-masters",
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
                ],
            },
            {
                label: "Other Masters",
                icon: "fa fa-cogs",
                type: "category",
                items: [
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
                ],
            },
            {
                label: "Reports",
                icon: "fa fa-file",
                href: "/",
                type: "single",
            },
        ],
        "Country Head": [
            {
                label: "Dashboard",
                icon: "fa fa-home",
                href: "/dashboard",
                type: "single",
            },
            {
                label: "Zone Master",
                icon: "fa fa-map",
                href: "/",
                type: "single",
            },
            {
                label: "State Master",
                icon: "fa fa-flag",
                href: "/",
                type: "single",
            },
            { label: "Reports", icon: "fa fa-file", href: "/", type: "single" },
        ],
        "Sales Employee": [
            {
                label: "Dashboard",
                icon: "fa fa-home",
                href: "/dashboard",
                type: "single",
            },
            {
                label: "My Stores",
                icon: "fa fa-store",
                href: "/",
                type: "single",
            },
            {
                label: "Sales Entry",
                icon: "fa fa-plus",
                href: "/",
                type: "single",
            },
        ],
    };

    const menus = menusByRole[role] || [];

    // Check if a menu item is active
    const isActive = (href) => {
        return currentUrl === href || currentUrl.startsWith(href + "/");
    };

    // Check if any child in category is active
    const isCategoryActive = (items) => {
        return items?.some((item) => isActive(item.href));
    };

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
                                {item.type === "single" ? (
                                    <Link
                                        href={item.href}
                                        className={`sidebar-link ${isActive(item.href) ? "active" : ""}`}
                                    >
                                        <i className={item.icon}></i>
                                        <span>{item.label}</span>
                                    </Link>
                                ) : (
                                    <>
                                        <div
                                            className={`sidebar-category ${isCategoryActive(item.items) ? "active" : ""}`}
                                            onClick={() =>
                                                toggleCategory(item.label)
                                            }
                                        >
                                            <i className={item.icon}></i>
                                            <span>{item.label}</span>
                                            <i
                                                className={`fa fa-chevron-${expandedCategories[item.label] ? "up" : "down"} chevron`}
                                            ></i>
                                        </div>
                                        {expandedCategories[item.label] && (
                                            <ul className="sidebar-submenu">
                                                {item.items.map(
                                                    (subItem, j) => (
                                                        <li key={j}>
                                                            <Link
                                                                href={
                                                                    subItem.href
                                                                }
                                                                className={`sidebar-link submenu-link ${isActive(subItem.href) ? "active" : ""}`}
                                                            >
                                                                <i
                                                                    className={
                                                                        subItem.icon
                                                                    }
                                                                ></i>
                                                                <span>
                                                                    {
                                                                        subItem.label
                                                                    }
                                                                </span>
                                                            </Link>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        )}
                                    </>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            <style jsx>{`
                .sidebar-logo {
                    flex-shrink: 0;
                    text-align: center;
                    position: sticky;
                    top: 0;
                    background: #000;
                    z-index: 10;
                }

                .sidebar .sidebar-logo {
                    height: 70px !important;
                    width: 120px !important;
                    padding: 0 !important;
                }

                .sidebar-logo img {
                    width: 80px;
                    margin-left: 41%;
                }

                .sidebar-menu-wrapper {
                    flex: 1;
                    overflow-y: auto;
                    padding: 20px 0;
                }

                .sidebar .sidebar-menu {
                    margin-top: 70px;
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
                    position: relative;
                }

                .sidebar-link:hover {
                    background: #111;
                    color: #fff;
                }

                .sidebar-link.active {
                    color: #fff;
                }

                .sidebar-link.active::before {
                    content: "";
                    position: absolute;
                    left: 0;
                    top: 0;
                    bottom: 0;
                    width: 4px;
                    background: #fff;
                }

                .sidebar-link i {
                    width: 20px;
                }

                /* Category styles */
                .sidebar-category {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 20px;
                    color: #fff;
                    cursor: pointer;
                    transition: all 0.2s;
                    font-weight: 500;
                    border-radius: 8px;
                }

                .sidebar-category:hover {
                    background: #111;
                }

                .sidebar-category.active {
                    background: #0d6efd22;
                    color: #edeff2;
                }

                .sidebar-category i {
                    width: 20px;
                }

                .sidebar-category .chevron {
                    margin-left: auto;
                    font-size: 12px;
                }

                /* Submenu styles */
                .sidebar-submenu {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                    background: #0a0a0a;
                }

                .submenu-link {
                    padding-left: 52px !important;
                    font-size: 14px;
                }

                .submenu-link i {
                    font-size: 12px;
                }

                .sidebar {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 230px;
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
                        width: 230px; /* toggle width */
                    }
                }
            `}</style>
        </>
    );
}
