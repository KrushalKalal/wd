import { useEffect, useState } from "react";
import { Head } from "@inertiajs/react";

export default function GuestLayout({ children, title }) {
    const [isLoading, setIsLoading] = useState(true);

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
            if (loadedAssets === totalAssets) {
                setIsLoading(false);
            }
        };

        scripts.forEach((src) => {
            const script = document.createElement("script");
            script.src = src;
            script.async = false;
            script.onload = checkAllLoaded;
            script.onerror = () => {
                checkAllLoaded();
            };
            document.body.appendChild(script);
        });

        styles.forEach((href) => {
            const link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = href;
            link.onload = checkAllLoaded;
            link.onerror = () => {
                checkAllLoaded();
            };
            document.head.appendChild(link);
        });

        return () => {
            scripts.forEach((src) => {
                const script = document.querySelector(`script[src="${src}"]`);
                if (script) document.body.removeChild(script);
            });
            styles.forEach((href) => {
                const link = document.querySelector(`link[href="${href}"]`);
                if (link) document.head.removeChild(link);
            });
        };
    }, []);

    if (isLoading) {
        return (
            <div className="guest-loader">
                <div className="guest-loader-card">
                    <img
                        src="/assets/img/wd_logo.png"
                        alt="logo"
                        className="g-logo"
                    />
                    <div className="g-ring" />
                </div>

                <style jsx>{`
                    :root {
                        --black: #000000;
                        --dark: #111111;
                        --gray: #6c757d;
                        --light: #ffffff;
                    }
                    .guest-loader {
                        position: fixed;
                        inset: 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: #ffffff;
                        z-index: 9999;
                        padding: 20px;
                    }
                    .guest-loader-card {
                        background: #ffffff;
                        border: 1px solid rgba(0, 0, 0, 0.08);
                    }
                    .g-logo {
                        width: 110px;
                        display: block;
                        margin: 0 auto 14px;
                    }
                    .g-ring {
                        width: 48px;
                        height: 48px;
                        border-radius: 50%;
                        border: 5px solid rgba(255, 255, 255, 0.18);
                        border-top-color: #ffffff;
                        animation: spin 0.9s linear infinite;
                        margin: 0 auto;
                    }
                    @keyframes spin {
                        to {
                            transform: rotate(360deg);
                        }
                    }
                `}</style>
            </div>
        );
    }

    return (
        <div className="guest-wrapper">
            <Head title={title || "WOW Recycle"} />
            <div className="guest-outer">
                <div className="guest-inner">{children}</div>
            </div>

            <style jsx>{`
                :root {
                    --navy: #0a3d62;
                    --navy-2: #113a5c;
                    --glass: rgba(255, 255, 255, 0.85);
                }
                .guest-wrapper {
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(
                        180deg,
                        #f5f8fb 0%,
                        #ffffff 100%
                    );
                    padding: 20px;
                }
                .guest-outer {
                    width: 100%;
                    max-width: 1200px;
                    display: flex;
                    gap: 24px;
                    align-items: center;
                    justify-content: center;
                }
                .guest-inner {
                    width: 100%;
                }

                @media (max-width: 991px) {
                    .guest-outer {
                        gap: 12px;
                        padding: 20px 4px;
                    }
                }
            `}</style>
        </div>
    );
}
