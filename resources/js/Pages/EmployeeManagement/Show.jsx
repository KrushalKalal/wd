import React, { useState, useEffect, useRef, useCallback } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";
import AlertModal from "../AlertModel";
import axios from "axios";

export default function EmployeeManagementShow({
    auth,
    employee,
    plan,
    visits,
    route_points,
    selected_date,
    available_dates,
}) {
    const { flash } = usePage().props;

    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [activeVisitId, setActiveVisitId] = useState(null);
    const [remark, setRemark] = useState(plan?.manager_remark || "");
    const [remarkSaving, setRemarkSaving] = useState(false);
    const [activeTab, setActiveTab] = useState("timeline"); // "timeline" | "history"
    const [historyFilter, setHistoryFilter] = useState(""); // search within history dates

    const mapRef = useRef(null);
    const mapInstanceRef = useRef(null);
    const markersRef = useRef([]);
    const infoWindowRef = useRef(null);
    const mapBuiltRef = useRef(false);

    useEffect(() => {
        if (flash?.success)
            setAlert({ show: true, type: "success", message: flash.success });
        if (flash?.error)
            setAlert({ show: true, type: "error", message: flash.error });
    }, [flash]);

    // Reset remark when plan changes (date change)
    useEffect(() => {
        setRemark(plan?.manager_remark || "");
    }, [plan]);

    // ── SVG marker factory ──
    const createSVGMarker = (bgColor, text) => {
        const size = 30;
        const fontSize = text.length > 1 ? 10 : 13;
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size + 8}" viewBox="0 0 ${size} ${size + 8}"><circle cx="${size / 2}" cy="${size / 2}" r="${size / 2 - 1.5}" fill="${bgColor}" stroke="white" stroke-width="2.5"/><text x="${size / 2}" y="${size / 2 + fontSize / 3 + 1}" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="${fontSize}" font-weight="bold">${text}</text><polygon points="${size / 2 - 5},${size - 1} ${size / 2 + 5},${size - 1} ${size / 2},${size + 7}" fill="${bgColor}"/></svg>`;
        return {
            url: "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg),
            scaledSize: new window.google.maps.Size(size, size + 8),
            anchor: new window.google.maps.Point(size / 2, size + 7),
        };
    };

    const buildMap = useCallback(() => {
        if (!mapRef.current || !window.google?.maps) return;
        if (mapBuiltRef.current) return;
        mapBuiltRef.current = true;

        markersRef.current.forEach((m) => m.setMap(null));
        markersRef.current = [];
        if (infoWindowRef.current) infoWindowRef.current.close();

        const map = new window.google.maps.Map(mapRef.current, {
            zoom: 13,
            center: { lat: 20.5937, lng: 78.9629 },
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });
        mapInstanceRef.current = map;
        infoWindowRef.current = new window.google.maps.InfoWindow();

        const bounds = new window.google.maps.LatLngBounds();
        const pathCoords = [];
        let visitCounter = 0;

        route_points.forEach((pt) => {
            const pos = { lat: pt.lat, lng: pt.lng };
            pathCoords.push(pos);
            bounds.extend(pos);

            let icon;
            if (pt.type === "day_start") {
                icon = createSVGMarker("#198754", "S");
            } else if (pt.type === "day_end") {
                icon = createSVGMarker("#dc3545", "E");
            } else if (pt.type === "skipped") {
                icon = createSVGMarker("#adb5bd", "✕");
            } else if (pt.type === "planned_visit") {
                visitCounter++;
                icon = createSVGMarker("#0d6efd", String(visitCounter));
            } else if (pt.type === "walkin_visit") {
                visitCounter++;
                icon = createSVGMarker("#e67e00", String(visitCounter));
            }

            const marker = new window.google.maps.Marker({
                position: pos,
                map,
                title: pt.label,
                icon,
            });

            marker.addListener("click", () => {
                infoWindowRef.current.setContent(buildInfoContent(pt));
                infoWindowRef.current.open(map, marker);
                if (pt.visit_id) setActiveVisitId(pt.visit_id);
            });

            markersRef.current.push(marker);
        });

        if (pathCoords.length > 1) {
            new window.google.maps.Polyline({
                path: pathCoords,
                geodesic: true,
                strokeColor: "#0d6efd",
                strokeOpacity: 0.75,
                strokeWeight: 3,
                map,
            });
        }

        if (pathCoords.length > 0) {
            map.fitBounds(bounds);
            if (pathCoords.length === 1) map.setZoom(15);
        }
    }, [route_points]); // eslint-disable-line

    useEffect(() => {
        if (route_points.length === 0) return;
        mapBuiltRef.current = false;
        mapInstanceRef.current = null;

        const interval = setInterval(() => {
            if (mapRef.current && window.google?.maps) {
                clearInterval(interval);
                buildMap();
            }
        }, 100);

        const timeout = setTimeout(() => clearInterval(interval), 10000);
        return () => {
            clearInterval(interval);
            clearTimeout(timeout);
        };
    }, [route_points, buildMap]);

    const buildInfoContent = (pt) => {
        if (pt.type === "day_start") {
            return `<div style="font-size:13px;padding:4px;min-width:160px"><strong style="color:#198754">Day started</strong><br/>Time: ${pt.time || "—"}<br/><a href="https://www.google.com/maps?q=${pt.lat},${pt.lng}" target="_blank" style="font-size:11px">Open in Maps</a></div>`;
        }
        if (pt.type === "day_end") {
            return `<div style="font-size:13px;padding:4px;min-width:160px"><strong style="color:#dc3545">Day ended</strong><br/>Time: ${pt.time || "—"}<br/><a href="https://www.google.com/maps?q=${pt.lat},${pt.lng}" target="_blank" style="font-size:11px">Open in Maps</a></div>`;
        }
        if (pt.type === "skipped") {
            return `<div style="font-size:13px;padding:4px;min-width:160px"><strong>${pt.label}</strong><br/><span style="color:#6c757d">Skipped</span><br/>Planned: ${pt.time || "—"}</div>`;
        }
        const m = pt.meta || {};
        return `<div style="font-size:13px;padding:4px;min-width:180px;line-height:1.6"><strong>${pt.label}</strong><br/><span style="font-size:11px;color:${pt.type === "walkin_visit" ? "#b45309" : "#1d6fa4"}">${pt.type === "walkin_visit" ? "Walk-in" : "Planned"}</span><br/>In: ${pt.time || "—"} &nbsp; Out: ${pt.checkout || "ongoing"}<br/>${pt.duration ? `Duration: ${formatDuration(pt.duration)}<br/>` : ""}Orders: ${m.orders_count || 0} &nbsp; Stock: ${m.stock_count || 0} &nbsp; Survey: ${m.survey_count || 0}<br/><a href="https://www.google.com/maps?q=${pt.lat},${pt.lng}" target="_blank" style="font-size:11px">Open in Maps</a></div>`;
    };

    const focusMarker = (visitId, lat, lng) => {
        setActiveVisitId(visitId);
        if (!mapInstanceRef.current || !lat || !lng) return;
        mapInstanceRef.current.panTo({
            lat: parseFloat(lat),
            lng: parseFloat(lng),
        });
        mapInstanceRef.current.setZoom(16);
    };

    const formatDuration = (minutes) => {
        if (!minutes) return "—";
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return h > 0 ? `${h}h ${m}m` : `${m}m`;
    };

    const formatTime = (t) => {
        if (!t) return "—";
        return t.substring(0, 5);
    };

    const formatDateLabel = (dateStr) => {
        if (!dateStr) return "—";
        const d = new Date(dateStr);
        const today = new Date().toISOString().split("T")[0];
        const yesterday = new Date(Date.now() - 86400000)
            .toISOString()
            .split("T")[0];
        if (dateStr === today) return "Today";
        if (dateStr === yesterday) return "Yesterday";
        return d.toLocaleDateString("en-IN", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
    };

    const formatDayOfWeek = (dateStr) => {
        if (!dateStr) return "";
        return new Date(dateStr).toLocaleDateString("en-IN", {
            weekday: "long",
        });
    };

    const handleDateChange = (date) => {
        setActiveTab("timeline");
        router.get(
            route("employee-management.show", employee.id),
            { date },
            { preserveState: false },
        );
    };

    const saveRemark = async () => {
        if (!plan) return;
        setRemarkSaving(true);
        try {
            await axios.post(route("employee-management.remark", plan.id), {
                manager_remark: remark,
            });
            setAlert({ show: true, type: "success", message: "Remark saved." });
        } catch {
            setAlert({
                show: true,
                type: "error",
                message: "Failed to save remark.",
            });
        } finally {
            setRemarkSaving(false);
        }
    };

    const visitStatusBadge = (status) => {
        const map = {
            checked_in: "bg-primary",
            completed: "bg-success",
            cancelled: "bg-danger",
        };
        return (
            <span className={`badge ${map[status] || "bg-secondary"}`}>
                {status?.replace("_", " ").toUpperCase() || "—"}
            </span>
        );
    };

    const planStoreBadge = (status) => {
        const map = {
            pending: "bg-warning text-dark",
            visited: "bg-success",
            skipped: "bg-secondary",
        };
        return (
            <span className={`badge ${map[status] || "bg-light text-dark"}`}>
                {status?.toUpperCase() || "—"}
            </span>
        );
    };

    // ── History panel helpers ──
    const isToday = selected_date === new Date().toISOString().split("T")[0];

    const filteredDates = available_dates.filter((d) =>
        historyFilter ? d.includes(historyFilter) : true,
    );

    // Group available_dates by month for history panel
    const groupedByMonth = filteredDates.reduce((acc, d) => {
        const month = d.substring(0, 7); // "2025-01"
        if (!acc[month]) acc[month] = [];
        acc[month].push(d);
        return acc;
    }, {});

    const monthLabel = (m) => {
        const [y, mo] = m.split("-");
        return new Date(y, parseInt(mo) - 1).toLocaleDateString("en-IN", {
            month: "long",
            year: "numeric",
        });
    };

    return (
        <MainLayout auth={auth} title="Employee Management">
            <div className="container-fluid py-4">
                {/* Breadcrumb */}
                <nav className="mb-3">
                    <ol className="breadcrumb mb-0">
                        <li className="breadcrumb-item">
                            <a
                                href={route("employee-management.index")}
                                className="text-decoration-none text-dark"
                            >
                                Employee Management /
                            </a>
                        </li>
                        <style>{`.breadcrumb-item.active::before{content:none!important}`}</style>
                        <li className="breadcrumb-item active">
                            {employee.name}
                        </li>
                    </ol>
                </nav>

                {/* Employee header */}
                <div className="card border-0 shadow-sm mb-4">
                    <div className="card-body">
                        <div className="row align-items-center g-3">
                            <div className="col-auto">
                                <div
                                    className="bg-dark rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                    style={{
                                        width: 56,
                                        height: 56,
                                        fontSize: 20,
                                    }}
                                >
                                    {employee.name?.charAt(0).toUpperCase()}
                                </div>
                            </div>
                            <div className="col">
                                <h5 className="fw-bold mb-1">
                                    {employee.name}
                                </h5>
                                <div className="d-flex flex-wrap gap-2">
                                    <span className="badge bg-dark">
                                        {employee.user?.roles?.[0]?.name || "—"}
                                    </span>
                                    <span className="text-muted small">
                                        {employee.designation || ""}
                                    </span>
                                    <span className="text-muted small">
                                        {[
                                            employee.area?.name,
                                            employee.city?.name,
                                            employee.state?.name,
                                        ]
                                            .filter(Boolean)
                                            .join(", ")}
                                    </span>
                                </div>
                                {employee.manager?.name && (
                                    <div className="text-muted small mt-1">
                                        Reports to: {employee.manager.name}
                                    </div>
                                )}
                            </div>

                            {/* Date info + quick nav */}
                            <div className="col-auto d-flex align-items-center gap-2 flex-wrap">
                                {/* Prev date */}
                                {(() => {
                                    const idx =
                                        available_dates.indexOf(selected_date);
                                    const prevDate =
                                        idx < available_dates.length - 1
                                            ? available_dates[idx + 1]
                                            : null;
                                    return (
                                        <button
                                            className="btn btn-sm btn-outline-secondary"
                                            disabled={!prevDate}
                                            onClick={() =>
                                                prevDate &&
                                                handleDateChange(prevDate)
                                            }
                                            title={
                                                prevDate
                                                    ? `Go to ${prevDate}`
                                                    : "No earlier dates"
                                            }
                                        >
                                            <i className="fas fa-chevron-left"></i>
                                        </button>
                                    );
                                })()}

                                <div
                                    className="text-center"
                                    style={{ minWidth: 130 }}
                                >
                                    <div className="fw-semibold small">
                                        {formatDateLabel(selected_date)}
                                    </div>
                                    <div
                                        className="text-muted"
                                        style={{ fontSize: 11 }}
                                    >
                                        {formatDayOfWeek(selected_date)}
                                    </div>
                                </div>

                                {/* Next date */}
                                {(() => {
                                    const idx =
                                        available_dates.indexOf(selected_date);
                                    const nextDate =
                                        idx > 0
                                            ? available_dates[idx - 1]
                                            : null;
                                    return (
                                        <button
                                            className="btn btn-sm btn-outline-secondary"
                                            disabled={!nextDate}
                                            onClick={() =>
                                                nextDate &&
                                                handleDateChange(nextDate)
                                            }
                                            title={
                                                nextDate
                                                    ? `Go to ${nextDate}`
                                                    : "No later dates"
                                            }
                                        >
                                            <i className="fas fa-chevron-right"></i>
                                        </button>
                                    );
                                })()}

                                {/* Jump to today */}
                                {!isToday && (
                                    <button
                                        className="btn btn-sm btn-dark"
                                        onClick={() =>
                                            handleDateChange(
                                                new Date()
                                                    .toISOString()
                                                    .split("T")[0],
                                            )
                                        }
                                    >
                                        <i className="fas fa-calendar-day me-1"></i>
                                        Today
                                    </button>
                                )}

                                {/* Select dropdown */}
                                <select
                                    className="form-select form-select-sm"
                                    value={selected_date}
                                    onChange={(e) =>
                                        handleDateChange(e.target.value)
                                    }
                                    style={{ minWidth: 150 }}
                                >
                                    {!available_dates.includes(
                                        selected_date,
                                    ) && (
                                        <option value={selected_date}>
                                            {selected_date} (no data)
                                        </option>
                                    )}
                                    {available_dates.map((d) => (
                                        <option key={d} value={d}>
                                            {d}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {/* ── Quick summary bar for selected date ── */}
                {(plan || visits.length > 0) && (
                    <div className="row g-2 mb-3">
                        {[
                            {
                                label: "Stores planned",
                                value: plan?.plan_stores?.length || 0,
                                icon: "fa-list-ul",
                                color: "dark",
                            },
                            {
                                label: "Visited",
                                value:
                                    plan?.plan_stores?.filter(
                                        (s) => s.status === "visited",
                                    ).length || 0,
                                icon: "fa-check-circle",
                                color: "success",
                            },
                            {
                                label: "Skipped",
                                value:
                                    plan?.plan_stores?.filter(
                                        (s) => s.status === "skipped",
                                    ).length || 0,
                                icon: "fa-times-circle",
                                color: "secondary",
                            },
                            {
                                label: "Walk-ins",
                                value: visits.filter((v) => !v.is_planned)
                                    .length,
                                icon: "fa-walking",
                                color: "warning",
                            },
                            {
                                label: "Total visits",
                                value: visits.length,
                                icon: "fa-store",
                                color: "primary",
                            },
                        ].map((stat, i) => (
                            <div className="col" key={i}>
                                <div className="card border-0 shadow-sm h-100">
                                    <div className="card-body py-2 px-3 d-flex align-items-center gap-2">
                                        <i
                                            className={`fas ${stat.icon} text-${stat.color}`}
                                            style={{ fontSize: 14 }}
                                        ></i>
                                        <div>
                                            <div
                                                className="fw-bold"
                                                style={{
                                                    fontSize: 18,
                                                    lineHeight: 1.1,
                                                }}
                                            >
                                                {stat.value}
                                            </div>
                                            <div
                                                className="text-muted"
                                                style={{ fontSize: 10 }}
                                            >
                                                {stat.label}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {!plan && visits.length === 0 && (
                    <div className="card border-0 shadow-sm mb-4">
                        <div className="card-body text-center py-5 text-muted">
                            <i className="fas fa-calendar-times fa-2x mb-3 d-block opacity-25"></i>
                            <p className="mb-0">
                                No plan or visits found for {selected_date}
                            </p>
                            <div className="mt-3">
                                <button
                                    className="btn btn-sm btn-outline-dark"
                                    onClick={() => setActiveTab("history")}
                                >
                                    <i className="fas fa-history me-1"></i>
                                    Browse past records
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* ── Main content tabs ── */}
                <div className="row g-4">
                    {/* LEFT PANEL */}
                    <div className="col-lg-5">
                        {/* Tab switcher */}
                        <div className="card border-0 shadow-sm mb-3">
                            <div className="card-header bg-white border-bottom-0 pt-3 pb-0">
                                <ul className="nav nav-tabs card-header-tabs">
                                    <li className="nav-item">
                                        <button
                                            className={`nav-link ${activeTab === "timeline" ? "active fw-semibold" : "text-muted"}`}
                                            onClick={() =>
                                                setActiveTab("timeline")
                                            }
                                        >
                                            <i className="fas fa-route me-1"></i>
                                            Day timeline
                                        </button>
                                    </li>
                                    <li className="nav-item">
                                        <button
                                            className={`nav-link ${activeTab === "history" ? "active fw-semibold" : "text-muted"}`}
                                            onClick={() =>
                                                setActiveTab("history")
                                            }
                                        >
                                            <i className="fas fa-history me-1"></i>
                                            Visit history
                                            <span
                                                className="badge bg-secondary ms-1"
                                                style={{ fontSize: 10 }}
                                            >
                                                {available_dates.length}
                                            </span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {/* ── TIMELINE TAB ── */}
                        {activeTab === "timeline" && (
                            <>
                                {plan && (
                                    <div className="card border-0 shadow-sm mb-3">
                                        <div className="card-body">
                                            <h6 className="fw-bold mb-3">
                                                Day summary
                                            </h6>
                                            <div className="row g-2 mb-3">
                                                {[
                                                    {
                                                        label: "Start",
                                                        value:
                                                            formatTime(
                                                                plan.day_start_time,
                                                            ) || "Not started",
                                                        icon: "fa-play-circle",
                                                        color: "success",
                                                    },
                                                    {
                                                        label: "End",
                                                        value:
                                                            formatTime(
                                                                plan.day_end_time,
                                                            ) || "Ongoing",
                                                        icon: "fa-stop-circle",
                                                        color: "danger",
                                                    },
                                                    {
                                                        label: "Planned",
                                                        value:
                                                            plan.plan_stores
                                                                ?.length || 0,
                                                        icon: "fa-list",
                                                        color: "dark",
                                                    },
                                                    {
                                                        label: "Visited",
                                                        value:
                                                            plan.plan_stores?.filter(
                                                                (s) =>
                                                                    s.status ===
                                                                    "visited",
                                                            ).length || 0,
                                                        icon: "fa-check",
                                                        color: "primary",
                                                    },
                                                ].map((item, i) => (
                                                    <div
                                                        className="col-6"
                                                        key={i}
                                                    >
                                                        <div className="bg-light rounded p-2 d-flex align-items-center gap-2">
                                                            <i
                                                                className={`fas ${item.icon} text-${item.color}`}
                                                            ></i>
                                                            <div>
                                                                <div className="fw-semibold small">
                                                                    {item.value}
                                                                </div>
                                                                <div
                                                                    className="text-muted"
                                                                    style={{
                                                                        fontSize: 11,
                                                                    }}
                                                                >
                                                                    {item.label}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>

                                            {/* Planned stores list */}
                                            <div className="border-top pt-3">
                                                <div className="small fw-semibold text-muted mb-2">
                                                    PLANNED STORES
                                                </div>
                                                {plan.plan_stores?.map((ps) => (
                                                    <div
                                                        key={ps.id}
                                                        className={`d-flex align-items-center gap-2 py-1 px-2 rounded mb-1 ${ps.visit && activeVisitId === ps.visit.id ? "bg-primary bg-opacity-10" : ""}`}
                                                        style={{
                                                            cursor: ps.visit
                                                                ? "pointer"
                                                                : "default",
                                                        }}
                                                        onClick={() =>
                                                            ps.visit &&
                                                            focusMarker(
                                                                ps.visit.id,
                                                                ps.visit
                                                                    .latitude,
                                                                ps.visit
                                                                    .longitude,
                                                            )
                                                        }
                                                    >
                                                        <span
                                                            className="badge bg-dark rounded-circle d-flex align-items-center justify-content-center"
                                                            style={{
                                                                width: 22,
                                                                height: 22,
                                                                fontSize: 10,
                                                            }}
                                                        >
                                                            {ps.visit_order}
                                                        </span>
                                                        <div className="flex-grow-1 small">
                                                            <div className="fw-semibold">
                                                                {ps.store?.name}
                                                            </div>
                                                            {ps.planned_time && (
                                                                <div
                                                                    className="text-muted"
                                                                    style={{
                                                                        fontSize: 11,
                                                                    }}
                                                                >
                                                                    Planned:{" "}
                                                                    {formatTime(
                                                                        ps.planned_time,
                                                                    )}
                                                                </div>
                                                            )}
                                                        </div>
                                                        {planStoreBadge(
                                                            ps.status,
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Route timeline */}
                                <div className="card border-0 shadow-sm mb-3">
                                    <div className="card-body">
                                        <h6 className="fw-bold mb-3">
                                            Route timeline
                                            <span
                                                className="badge bg-secondary ms-2"
                                                style={{ fontSize: 11 }}
                                            >
                                                {visits.length} visits
                                            </span>
                                        </h6>
                                        {visits.length === 0 ? (
                                            <p className="text-muted small mb-0">
                                                No visits recorded.
                                            </p>
                                        ) : (
                                            <div className="position-relative">
                                                <div
                                                    className="position-absolute bg-secondary bg-opacity-25"
                                                    style={{
                                                        left: 10,
                                                        top: 0,
                                                        bottom: 0,
                                                        width: 2,
                                                    }}
                                                />
                                                {visits.map((visit, idx) => (
                                                    <div
                                                        key={visit.id}
                                                        className={`d-flex gap-3 mb-3 position-relative ${activeVisitId === visit.id ? "opacity-100" : "opacity-75"}`}
                                                        style={{
                                                            cursor: "pointer",
                                                        }}
                                                        onClick={() =>
                                                            focusMarker(
                                                                visit.id,
                                                                visit.latitude,
                                                                visit.longitude,
                                                            )
                                                        }
                                                    >
                                                        <div
                                                            className="rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center fw-bold"
                                                            style={{
                                                                width: 22,
                                                                height: 22,
                                                                fontSize: 10,
                                                                zIndex: 1,
                                                                background:
                                                                    visit.is_planned
                                                                        ? "#0d6efd"
                                                                        : "#e67e00",
                                                                color: "white",
                                                                border: "2px solid white",
                                                                boxShadow:
                                                                    "0 1px 3px rgba(0,0,0,0.3)",
                                                            }}
                                                        >
                                                            {idx + 1}
                                                        </div>
                                                        <div
                                                            className={`card flex-grow-1 border shadow-sm ${activeVisitId === visit.id ? "border-primary" : ""}`}
                                                        >
                                                            <div className="card-body p-2">
                                                                <div className="d-flex justify-content-between align-items-start">
                                                                    <Link
                                                                        href={route(
                                                                            "store-management.show",
                                                                            visit.store_id,
                                                                        )}
                                                                        className="fw-semibold small text-dark text-decoration-none"
                                                                        onClick={(
                                                                            e,
                                                                        ) =>
                                                                            e.stopPropagation()
                                                                        }
                                                                        title="View store details"
                                                                    >
                                                                        {
                                                                            visit
                                                                                .store
                                                                                ?.name
                                                                        }
                                                                        <i
                                                                            className="fas fa-external-link-alt ms-1 text-muted"
                                                                            style={{
                                                                                fontSize: 9,
                                                                            }}
                                                                        ></i>
                                                                    </Link>
                                                                    {visitStatusBadge(
                                                                        visit.status,
                                                                    )}
                                                                </div>
                                                                <div
                                                                    className="text-muted"
                                                                    style={{
                                                                        fontSize: 11,
                                                                    }}
                                                                >
                                                                    {
                                                                        visit
                                                                            .store
                                                                            ?.address
                                                                    }
                                                                </div>
                                                                <div className="d-flex flex-wrap gap-2 mt-1">
                                                                    <span className="text-muted small">
                                                                        <i className="fas fa-sign-in-alt me-1 text-success"></i>
                                                                        {formatTime(
                                                                            visit.check_in_time,
                                                                        )}
                                                                    </span>
                                                                    {visit.check_out_time && (
                                                                        <span className="text-muted small">
                                                                            <i className="fas fa-sign-out-alt me-1 text-danger"></i>
                                                                            {formatTime(
                                                                                visit.check_out_time,
                                                                            )}
                                                                        </span>
                                                                    )}
                                                                    {visit.duration_minutes && (
                                                                        <span className="text-muted small">
                                                                            <i className="fas fa-clock me-1"></i>
                                                                            {formatDuration(
                                                                                visit.duration_minutes,
                                                                            )}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                                <div className="d-flex gap-2 mt-1 flex-wrap">
                                                                    {visit.orders_count >
                                                                        0 && (
                                                                        <span
                                                                            className="badge bg-info bg-opacity-10 text-info"
                                                                            style={{
                                                                                fontSize: 10,
                                                                            }}
                                                                        >
                                                                            {
                                                                                visit.orders_count
                                                                            }{" "}
                                                                            orders
                                                                        </span>
                                                                    )}
                                                                    {visit.stock_count >
                                                                        0 && (
                                                                        <span
                                                                            className="badge bg-warning bg-opacity-10 text-warning"
                                                                            style={{
                                                                                fontSize: 10,
                                                                            }}
                                                                        >
                                                                            {
                                                                                visit.stock_count
                                                                            }{" "}
                                                                            stock
                                                                        </span>
                                                                    )}
                                                                    {visit.survey_count >
                                                                        0 && (
                                                                        <span
                                                                            className="badge bg-success bg-opacity-10 text-success"
                                                                            style={{
                                                                                fontSize: 10,
                                                                            }}
                                                                        >
                                                                            {
                                                                                visit.survey_count
                                                                            }{" "}
                                                                            surveys
                                                                        </span>
                                                                    )}
                                                                    {!visit.is_planned && (
                                                                        <span
                                                                            className="badge bg-warning text-dark"
                                                                            style={{
                                                                                fontSize: 10,
                                                                            }}
                                                                        >
                                                                            Walk-in
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Manager remark */}
                                {plan && (
                                    <div className="card border-0 shadow-sm">
                                        <div className="card-body">
                                            <h6 className="fw-bold mb-2">
                                                Manager remark
                                            </h6>
                                            {plan.remark_by && (
                                                <p className="text-muted small mb-2">
                                                    Last by{" "}
                                                    {plan.remark_by?.name} at{" "}
                                                    {plan.remark_at
                                                        ? new Date(
                                                              plan.remark_at,
                                                          ).toLocaleString()
                                                        : ""}
                                                </p>
                                            )}
                                            <textarea
                                                className="form-control mb-2"
                                                rows={3}
                                                placeholder="Add a remark on this day's plan..."
                                                value={remark}
                                                onChange={(e) =>
                                                    setRemark(e.target.value)
                                                }
                                            />
                                            <button
                                                className="btn btn-dark btn-sm"
                                                onClick={saveRemark}
                                                disabled={
                                                    remarkSaving ||
                                                    !remark.trim()
                                                }
                                            >
                                                {remarkSaving ? (
                                                    <>
                                                        <span className="spinner-border spinner-border-sm me-1"></span>
                                                        Saving...
                                                    </>
                                                ) : (
                                                    <>
                                                        <i className="fas fa-save me-1"></i>
                                                        Save remark
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}

                        {/* ── HISTORY TAB ── */}
                        {activeTab === "history" && (
                            <div className="card border-0 shadow-sm">
                                <div className="card-body">
                                    <div className="d-flex align-items-center justify-content-between mb-3">
                                        <h6 className="fw-bold mb-0">
                                            <i className="fas fa-history me-2 text-primary"></i>
                                            Past visit history
                                        </h6>
                                        <span className="badge bg-secondary">
                                            {available_dates.length} days
                                        </span>
                                    </div>

                                    {/* Search within history */}
                                    <div className="mb-3">
                                        <input
                                            type="month"
                                            className="form-control form-control-sm"
                                            placeholder="Filter by month"
                                            value={historyFilter}
                                            onChange={(e) =>
                                                setHistoryFilter(e.target.value)
                                            }
                                        />
                                    </div>

                                    {available_dates.length === 0 ? (
                                        <div className="text-center py-4 text-muted">
                                            <i className="fas fa-calendar-times fa-2x mb-2 d-block opacity-25"></i>
                                            <p className="small mb-0">
                                                No history available
                                            </p>
                                        </div>
                                    ) : (
                                        <div
                                            style={{
                                                maxHeight: 520,
                                                overflowY: "auto",
                                            }}
                                        >
                                            {Object.entries(groupedByMonth)
                                                .sort(([a], [b]) =>
                                                    b.localeCompare(a),
                                                )
                                                .map(([month, dates]) => (
                                                    <div
                                                        key={month}
                                                        className="mb-3"
                                                    >
                                                        {/* Month header */}
                                                        <div
                                                            className="small fw-bold text-muted mb-2 px-1 d-flex align-items-center gap-2"
                                                            style={{
                                                                fontSize: 11,
                                                                letterSpacing:
                                                                    "0.05em",
                                                            }}
                                                        >
                                                            <i className="fas fa-calendar-alt"></i>
                                                            {monthLabel(
                                                                month,
                                                            ).toUpperCase()}
                                                            <span className="badge bg-light text-dark border">
                                                                {dates.length}{" "}
                                                                days
                                                            </span>
                                                        </div>

                                                        {/* Day rows */}
                                                        {dates.map((d) => {
                                                            const isSelected =
                                                                d ===
                                                                selected_date;
                                                            return (
                                                                <div
                                                                    key={d}
                                                                    className={`d-flex align-items-center gap-2 px-2 py-2 rounded mb-1 border ${isSelected ? "border-primary bg-primary bg-opacity-10" : "border-transparent"}`}
                                                                    style={{
                                                                        cursor: "pointer",
                                                                        transition:
                                                                            "background 0.15s",
                                                                    }}
                                                                    onClick={() =>
                                                                        handleDateChange(
                                                                            d,
                                                                        )
                                                                    }
                                                                    onMouseEnter={(
                                                                        e,
                                                                    ) => {
                                                                        if (
                                                                            !isSelected
                                                                        )
                                                                            e.currentTarget.classList.add(
                                                                                "bg-light",
                                                                            );
                                                                    }}
                                                                    onMouseLeave={(
                                                                        e,
                                                                    ) => {
                                                                        if (
                                                                            !isSelected
                                                                        )
                                                                            e.currentTarget.classList.remove(
                                                                                "bg-light",
                                                                            );
                                                                    }}
                                                                >
                                                                    {/* Day circle */}
                                                                    <div
                                                                        className={`rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold ${isSelected ? "bg-primary text-white" : "bg-light text-dark"}`}
                                                                        style={{
                                                                            width: 36,
                                                                            height: 36,
                                                                            fontSize: 12,
                                                                        }}
                                                                    >
                                                                        {new Date(
                                                                            d,
                                                                        ).getDate()}
                                                                    </div>

                                                                    <div className="flex-grow-1">
                                                                        <div className="small fw-semibold">
                                                                            {formatDayOfWeek(
                                                                                d,
                                                                            )}
                                                                            ,{" "}
                                                                            {new Date(
                                                                                d,
                                                                            ).toLocaleDateString(
                                                                                "en-IN",
                                                                                {
                                                                                    day: "numeric",
                                                                                    month: "short",
                                                                                },
                                                                            )}
                                                                            {d ===
                                                                                new Date()
                                                                                    .toISOString()
                                                                                    .split(
                                                                                        "T",
                                                                                    )[0] && (
                                                                                <span
                                                                                    className="badge bg-success ms-1"
                                                                                    style={{
                                                                                        fontSize: 9,
                                                                                    }}
                                                                                >
                                                                                    Today
                                                                                </span>
                                                                            )}
                                                                        </div>
                                                                        <div
                                                                            className="text-muted"
                                                                            style={{
                                                                                fontSize: 10,
                                                                            }}
                                                                        >
                                                                            {d}
                                                                        </div>
                                                                    </div>

                                                                    {isSelected ? (
                                                                        <span className="badge bg-primary">
                                                                            Viewing
                                                                        </span>
                                                                    ) : (
                                                                        <i
                                                                            className="fas fa-chevron-right text-muted"
                                                                            style={{
                                                                                fontSize: 10,
                                                                            }}
                                                                        ></i>
                                                                    )}
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                ))}
                                        </div>
                                    )}

                                    {available_dates.length > 0 && (
                                        <div className="border-top pt-2 mt-2">
                                            <p className="text-muted small mb-0 text-center">
                                                Showing last{" "}
                                                {available_dates.length} days
                                                with data. Click any date to
                                                view that day's route.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* RIGHT: Map */}
                    <div className="col-lg-7">
                        <div
                            className="card border-0 shadow-sm"
                            style={{ position: "sticky", top: 80 }}
                        >
                            <div className="card-header bg-white border-bottom d-flex align-items-center gap-2 py-2 flex-wrap">
                                <i className="fas fa-route text-primary"></i>
                                <span className="fw-semibold small">
                                    Daily route map —{" "}
                                    <span
                                        className={
                                            isToday
                                                ? "text-success"
                                                : "text-primary"
                                        }
                                    >
                                        {formatDateLabel(selected_date)}
                                    </span>
                                    <span
                                        className="text-muted ms-1"
                                        style={{ fontSize: 11 }}
                                    >
                                        ({selected_date})
                                    </span>
                                </span>
                                <div className="d-flex gap-3 ms-auto flex-wrap">
                                    {[
                                        {
                                            color: "#198754",
                                            label: "Day start",
                                        },
                                        { color: "#0d6efd", label: "Planned" },
                                        { color: "#e67e00", label: "Walk-in" },
                                        { color: "#adb5bd", label: "Skipped" },
                                        { color: "#dc3545", label: "Day end" },
                                    ].map((item) => (
                                        <div
                                            key={item.label}
                                            className="d-flex align-items-center gap-1"
                                            style={{ fontSize: 11 }}
                                        >
                                            <span
                                                className="rounded-circle"
                                                style={{
                                                    width: 10,
                                                    height: 10,
                                                    background: item.color,
                                                    display: "inline-block",
                                                }}
                                            />
                                            <span className="text-muted">
                                                {item.label}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div className="card-body p-0">
                                {route_points.length === 0 ? (
                                    <div
                                        className="d-flex align-items-center justify-content-center text-muted bg-light"
                                        style={{ height: 500 }}
                                    >
                                        <div className="text-center">
                                            <i className="fas fa-map fa-2x mb-3 d-block opacity-25"></i>
                                            <p className="mb-0 small">
                                                No GPS data available for this
                                                date.
                                                <br />
                                                GPS is captured when the
                                                employee starts their day and
                                                checks in to stores.
                                            </p>
                                            <button
                                                className="btn btn-sm btn-outline-dark mt-3"
                                                onClick={() =>
                                                    setActiveTab("history")
                                                }
                                            >
                                                <i className="fas fa-history me-1"></i>
                                                Browse other dates
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    <div
                                        ref={mapRef}
                                        style={{ height: 500, width: "100%" }}
                                    />
                                )}
                            </div>
                            {route_points.length > 0 && (
                                <div className="card-footer bg-white border-top py-2 px-3">
                                    <div className="d-flex gap-4 flex-wrap small text-muted">
                                        <span>
                                            <i className="fas fa-map-marker-alt me-1 text-primary"></i>
                                            {
                                                route_points.filter(
                                                    (p) =>
                                                        p.type ===
                                                        "planned_visit",
                                                ).length
                                            }{" "}
                                            planned
                                        </span>
                                        <span>
                                            <i className="fas fa-walking me-1 text-warning"></i>
                                            {
                                                route_points.filter(
                                                    (p) =>
                                                        p.type ===
                                                        "walkin_visit",
                                                ).length
                                            }{" "}
                                            walk-ins
                                        </span>
                                        <span>
                                            <i className="fas fa-times-circle me-1 text-secondary"></i>
                                            {
                                                route_points.filter(
                                                    (p) => p.type === "skipped",
                                                ).length
                                            }{" "}
                                            skipped
                                        </span>
                                        {plan?.day_start_time &&
                                            plan?.day_end_time && (
                                                <span>
                                                    <i className="fas fa-clock me-1"></i>
                                                    {formatTime(
                                                        plan.day_start_time,
                                                    )}{" "}
                                                    –{" "}
                                                    {formatTime(
                                                        plan.day_end_time,
                                                    )}
                                                </span>
                                            )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <AlertModal
                show={alert.show}
                type={alert.type}
                message={alert.message}
                onClose={() => setAlert({ ...alert, show: false })}
            />
        </MainLayout>
    );
}
