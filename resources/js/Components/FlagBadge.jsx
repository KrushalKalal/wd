import { useState, useEffect } from "react";
import { Link } from "@inertiajs/react";
import axios from "axios";

/**
 * FlagBadge — drop this inside your Header component.
 * Polls /flagged-stores/count every 60 seconds.
 * Shows a red badge with count. Click → /flagged-stores.
 *
 * Usage in Header.jsx:
 *   import FlagBadge from "@/Components/FlagBadge";
 *   <FlagBadge />
 */
export default function FlagBadge() {
    const [count, setCount] = useState(0);

    const fetchCount = async () => {
        try {
            const res = await axios.get("/flagged-stores/count");
            if (res.data.success) {
                setCount(res.data.count);
            }
        } catch (e) {
            // silent fail — badge just stays at last known count
        }
    };

    useEffect(() => {
        fetchCount(); // immediate on mount

        const interval = setInterval(fetchCount, 60000); // every 60s
        return () => clearInterval(interval);
    }, []);

    if (count === 0) return null; // hide badge when nothing flagged

    return (
        <Link
            href="/flagged-stores"
            className="btn btn-sm position-relative d-inline-flex align-items-center justify-content-center"
            style={{
                width: 36,
                height: 36,
                borderRadius: "50%",
                backgroundColor: "#111",
                color: "white",
                border: "none",
            }}
            title={`${count} flagged store${count > 1 ? "s" : ""} need attention`}
        >
            <i className="fas fa-flag" style={{ fontSize: 14 }}></i>
            <span
                className="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                style={{
                    backgroundColor: "#dc3545",
                    fontSize: 9,
                    minWidth: 16,
                    height: 16,
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    padding: "0 4px",
                }}
            >
                {count > 99 ? "99+" : count}
            </span>
        </Link>
    );
}
