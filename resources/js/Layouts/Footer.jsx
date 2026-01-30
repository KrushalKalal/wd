export default function Footer() {
    return (
        <>
            <footer className="footer">
                <div className="footer-content">
                    <p className="footer-left mb-0">
                        Wild Drum Beverages © {new Date().getFullYear()}
                    </p>
                    <p className="footer-right mb-0">
                        Designed & Developed By{" "}
                        <a
                            href="https://qubetatechnolab.com/"
                            className="ms-1"
                            target="_blank"
                        >
                            Qubeta Technolab
                        </a>
                    </p>
                </div>

                <style jsx>{`
                    .footer {
                        position: fixed;
                        bottom: 0;
                        left: 190px; /* adjust with sidebar width if needed */
                        right: 0;
                        height: 50px;
                        background: #fff;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 0 20px;
                        box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.08);
                        z-index: 998;
                    }

                    .footer-content {
                        width: 100%;
                        display: flex;
                        justify-content: space-between; /* left & right text */
                        align-items: center;
                        max-width: 100%;
                        margin: 0 auto;
                    }

                    .footer a {
                        color: #111111;
                        text-decoration: none;
                    }

                    .footer a:hover {
                        text-decoration: underline;
                    }

                    .dark-theme .footer {
                        background: #222;
                        color: #fff;
                    }

                    @media (max-width: 992px) {
                        .footer {
                            left: 0;
                            font-size: 12px;
                        }
                    }
                `}</style>
            </footer>
        </>
    );
}
