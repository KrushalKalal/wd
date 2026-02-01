import { useEffect, useState } from "react";
import { Head } from "@inertiajs/react";
import Footer from "./Footer";
import Header from "./Header";
import Sidebar from "./Sidebar";

export default function MainLayout({ children, title, auth }) {
    const [isLoading, setIsLoading] = useState(true);
    const [isSidebarOpen, setIsSidebarOpen] = useState(false); // default closed

    // Load JS/CSS assets
    useEffect(() => {
        const scripts = [
            "/assets/js/jquery-3.7.1.min.js",
            "/assets/js/bootstrap.bundle.min.js",
            "/assets/js/script.js",
        ];
        const styles = [
            "/assets/css/bootstrap.min.css",
            "/assets/css/style.css",
        ];

        let loadedAssets = 0;
        const totalAssets = scripts.length + styles.length;
        const checkAllLoaded = () => {
            loadedAssets += 1;
            if (loadedAssets === totalAssets) setIsLoading(false);
        };

        scripts.forEach((src) => {
            const script = document.createElement("script");
            script.src = src;
            script.async = false;
            script.onload = checkAllLoaded;
            script.onerror = checkAllLoaded;
            document.body.appendChild(script);
        });
        styles.forEach((href) => {
            const link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = href;
            link.onload = checkAllLoaded;
            link.onerror = checkAllLoaded;
            document.head.appendChild(link);
        });

        return () => {
            scripts.forEach((src) => {
                const s = document.querySelector(`script[src="${src}"]`);
                if (s) document.body.removeChild(s);
            });
            styles.forEach((href) => {
                const l = document.querySelector(`link[href="${href}"]`);
                if (l) document.head.removeChild(l);
            });
        };
    }, []);

    if (isLoading) return <Loader />;

    return (
        <div className={`main-wrapper ${isSidebarOpen ? "sidebar-open" : ""}`}>
            <Head title={title || "WOW Recycle"} />

            {/* Sidebar */}
            <Sidebar isOpen={isSidebarOpen} />

            {/* Header */}
            <Header
                auth={auth}
                toggleSidebar={() => setIsSidebarOpen(!isSidebarOpen)}
            />

            {/* Page Content */}
            <div className="page-wrapper">
                <div className="content">{children}</div>
            </div>

            {/* Footer */}
            <Footer />

            {/* Mobile overlay */}
            {isSidebarOpen && (
                <div
                    className="sidebar-overlay d-lg-none"
                    onClick={() => setIsSidebarOpen(false)}
                />
            )}

            <style jsx>{`
                :root {
                    --sidebar-width: 230px;
                    --header-height: 70px;
                    --footer-height: 50px;
                }

                .main-wrapper {
                    display: flex;
                    min-height: 100%;
                    background: #ffffff; /* light theme default */
                    justify-content: center;
                }

                .page-wrapper {
                    flex: 1;
                    margin-top: 20px;
                    margin-left: 0;
                    margin-bottom: var(--footer-height);
                    padding: 5px;
                    width: 85%;
                    transition: all 0.3s ease;
                }

                .content {
                    max-width: 1400px;
                    margin: 0 auto;
                }

                .sidebar-overlay {
                    position: fixed;
                    inset: 0;
                    background: rgba(0, 0, 0, 0.45);
                    z-index: 998;
                }

                @media (min-width: 992px) {
                    .page-wrapper {
                        margin-left: var(--sidebar-width);
                    }
                }
                @media (max-width: 992px) {
                    .page-wrapper {
                        width: 90%;
                    }
                }
            `}</style>
        </div>
    );
}

/* Loader Component */
const Loader = () => (
    <div className="loader-wrapper">
        <div className="loader-card">
            <img src="/assets/img/wd_logo.png" className="loader-logo" />
            <div className="loader-ring" />
            <div className="loader-text">Preparing your dashboard...</div>
        </div>
        <style jsx>{`
            .loader-wrapper {
                position: fixed;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f8fbfd;
                z-index: 9999;
            }
            .loader-card {
                background: #fff;
                padding: 28px;
                border-radius: 14px;
                text-align: center;
            }
            .loader-logo {
                width: 110px;
            }
            .loader-ring {
                width: 50px;
                height: 50px;
                border: 5px solid #ddd;
                border-top-color: #000;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }
            .loader-text {
                margin-top: 10px;
                font-weight: 600;
            }
        `}</style>
    </div>
);
