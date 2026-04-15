import React, { useState, useEffect } from "react";
import { router, Link, usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";
import Select from "react-select";
import AlertModal from "../AlertModel";
import axios from "axios";

export default function Index({ auth, records, states, filters }) {
    const { flash } = usePage().props;
    const flags = records?.data || [];
    const pagination = records;

    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [resolveModal, setResolveModal] = useState({
        show: false,
        flagId: null,
        storeName: "",
    });
    const [resolveNote, setResolveNote] = useState("");
    const [resolving, setResolving] = useState(false);

    useEffect(() => {
        if (flash?.success)
            setAlert({ show: true, type: "success", message: flash.success });
        if (flash?.error)
            setAlert({ show: true, type: "error", message: flash.error });
    }, [flash]);

    // Auto-reload the page data every 60s so new flags appear without manual refresh
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ["records"] });
        }, 60000);
        return () => clearInterval(interval);
    }, []);

    const openResolve = (flag) => {
        setResolveModal({
            show: true,
            flagId: flag.id,
            storeName: flag.store?.name,
        });
        setResolveNote("");
    };

    const submitResolve = async () => {
        setResolving(true);
        try {
            const res = await axios.post(
                `/flagged-stores/${resolveModal.flagId}/resolve`,
                {
                    resolved_note: resolveNote,
                },
            );
            if (res.data.success) {
                setAlert({
                    show: true,
                    type: "success",
                    message: res.data.message,
                });
                setResolveModal({ show: false, flagId: null, storeName: "" });
                router.reload();
            }
        } catch (e) {
            setAlert({
                show: true,
                type: "error",
                message: e.response?.data?.message || "Failed to resolve.",
            });
        } finally {
            setResolving(false);
        }
    };

    const formatDate = (d) =>
        d
            ? new Date(d).toLocaleDateString("en-IN", {
                  day: "numeric",
                  month: "short",
                  year: "numeric",
              })
            : "—";
    const formatDateTime = (d) =>
        d
            ? new Date(d).toLocaleString("en-IN", {
                  day: "numeric",
                  month: "short",
                  year: "numeric",
                  hour: "2-digit",
                  minute: "2-digit",
              })
            : "—";

    return (
        <MainLayout user={auth.user} title="Flagged Stores">
            <div className="container-fluid py-4">
                {/* HEADER */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 className="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                            <i className="fas fa-flag text-danger"></i>
                            Flagged Stores
                        </h2>
                        <p className="text-muted mb-0">
                            Stores flagged by field employees that need
                            attention
                        </p>
                    </div>
                    <div className="d-flex align-items-center gap-2">
                        <span className="badge bg-danger px-3 py-2">
                            {pagination?.total || 0} Unresolved
                        </span>
                    </div>
                </div>

                {/* FILTERS */}
                <div className="card border-0 shadow-sm mb-4">
                    <div className="card-body p-3">
                        <div className="row g-2">
                            <div className="col-md-5">
                                <div className="input-group">
                                    <span className="input-group-text bg-white border-end-0">
                                        <i className="fas fa-search text-muted"></i>
                                    </span>
                                    <input
                                        type="text"
                                        className="form-control border-start-0"
                                        placeholder="Search store, employee, note..."
                                        value={filters.search || ""}
                                        onChange={(e) =>
                                            router.get(
                                                "/flagged-stores",
                                                {
                                                    ...filters,
                                                    search: e.target.value,
                                                    page: 1,
                                                },
                                                { preserveState: true },
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <div className="col-md-3">
                                <Select
                                    options={[
                                        { value: null, label: "All States" },
                                        ...states.map((s) => ({
                                            value: s.id,
                                            label: s.name,
                                        })),
                                    ]}
                                    value={
                                        filters.state_id
                                            ? {
                                                  value: filters.state_id,
                                                  label: states.find(
                                                      (s) =>
                                                          s.id ===
                                                          filters.state_id,
                                                  )?.name,
                                              }
                                            : null
                                    }
                                    onChange={(o) =>
                                        router.get(
                                            "/flagged-stores",
                                            {
                                                ...filters,
                                                state_id: o?.value,
                                                page: 1,
                                            },
                                            { preserveState: true },
                                        )
                                    }
                                    isClearable
                                    placeholder="Filter by State"
                                    styles={{
                                        control: (b) => ({
                                            ...b,
                                            borderColor: "#dee2e6",
                                            minHeight: 38,
                                        }),
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* TABLE */}
                <div className="card border-0 shadow-sm">
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        {[
                                            "#",
                                            "Store",
                                            "Location",
                                            "Flagged By",
                                            "Flag Note",
                                            "Flagged At",
                                            "Visit",
                                            "Actions",
                                        ].map((h) => (
                                            <th
                                                key={h}
                                                style={{
                                                    backgroundColor: "#111",
                                                    color: "#fff",
                                                    whiteSpace: "nowrap",
                                                }}
                                            >
                                                {h}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {flags.length > 0 ? (
                                        flags.map((flag, idx) => (
                                            <tr
                                                key={flag.id}
                                                style={{
                                                    borderLeft:
                                                        "3px solid #dc3545",
                                                }}
                                            >
                                                <td className="text-dark">
                                                    {(pagination.current_page -
                                                        1) *
                                                        pagination.per_page +
                                                        idx +
                                                        1}
                                                </td>

                                                <td>
                                                    <div className="fw-semibold text-dark d-flex align-items-center gap-2">
                                                        <i
                                                            className="fas fa-flag text-danger"
                                                            style={{
                                                                fontSize: 11,
                                                            }}
                                                        ></i>
                                                        {flag.store?.name}
                                                    </div>
                                                </td>

                                                <td>
                                                    <div className="text-dark small">
                                                        {flag.store?.city?.name}
                                                    </div>
                                                    <div className="text-muted small">
                                                        {
                                                            flag.store?.state
                                                                ?.name
                                                        }
                                                    </div>
                                                </td>

                                                <td>
                                                    <div className="text-dark small fw-semibold">
                                                        {flag.employee?.name}
                                                    </div>
                                                    <div className="text-muted small">
                                                        {flag.employee
                                                            ?.designation ||
                                                            "—"}
                                                    </div>
                                                </td>

                                                <td style={{ maxWidth: 220 }}>
                                                    {flag.flag_note ? (
                                                        <span className="text-dark small">
                                                            {flag.flag_note}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted small fst-italic">
                                                            No note
                                                        </span>
                                                    )}
                                                </td>

                                                <td
                                                    className="text-dark small"
                                                    style={{
                                                        whiteSpace: "nowrap",
                                                    }}
                                                >
                                                    {formatDateTime(
                                                        flag.created_at,
                                                    )}
                                                </td>

                                                <td>
                                                    {flag.visit ? (
                                                        <span className="text-muted small">
                                                            {formatDate(
                                                                flag.visit
                                                                    .visit_date,
                                                            )}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted small">
                                                            —
                                                        </span>
                                                    )}
                                                </td>

                                                <td>
                                                    <div className="d-flex gap-1">
                                                        <Link
                                                            href={`/store-management/${flag.store_id}`}
                                                            className="btn btn-sm btn-dark text-white"
                                                            title="View Store"
                                                        >
                                                            <i className="fas fa-eye"></i>
                                                        </Link>
                                                        <button
                                                            className="btn btn-sm btn-success"
                                                            title="Mark Resolved"
                                                            onClick={() =>
                                                                openResolve(
                                                                    flag,
                                                                )
                                                            }
                                                        >
                                                            <i className="fas fa-check"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan="8"
                                                className="text-center py-5"
                                            >
                                                <i className="fas fa-flag fa-3x text-muted mb-3 d-block"></i>
                                                <p className="text-muted mb-0">
                                                    No flagged stores
                                                </p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* PAGINATION */}
                        {pagination && pagination.last_page > 1 && (
                            <div className="p-3 border-top bg-light">
                                <nav>
                                    <ul className="pagination mb-0 justify-content-center">
                                        {pagination.links.map((link, i) => (
                                            <li
                                                key={i}
                                                className={`page-item ${link.active ? "active" : ""} ${!link.url ? "disabled" : ""}`}
                                            >
                                                <button
                                                    className="page-link"
                                                    onClick={() =>
                                                        link.url &&
                                                        router.visit(link.url)
                                                    }
                                                    dangerouslySetInnerHTML={{
                                                        __html: link.label,
                                                    }}
                                                    style={
                                                        link.active
                                                            ? {
                                                                  backgroundColor:
                                                                      "#000",
                                                                  borderColor:
                                                                      "#000",
                                                                  color: "#fff",
                                                              }
                                                            : { color: "#000" }
                                                    }
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                </nav>
                            </div>
                        )}
                    </div>
                </div>

                {/* RESOLVE MODAL */}
                {resolveModal.show && (
                    <div
                        className="modal show d-block"
                        style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
                    >
                        <div className="modal-dialog modal-dialog-centered">
                            <div className="modal-content border-0 shadow-lg">
                                <div className="modal-header bg-dark text-white border-0">
                                    <h5 className="modal-title text-white fw-bold">
                                        <i className="fas fa-check-circle me-2"></i>
                                        Resolve Flag
                                    </h5>
                                    <button
                                        type="button"
                                        className="btn-close btn-close-white"
                                        onClick={() =>
                                            setResolveModal({
                                                show: false,
                                                flagId: null,
                                                storeName: "",
                                            })
                                        }
                                    />
                                </div>
                                <div className="modal-body">
                                    <p className="text-dark mb-3">
                                        Resolving flag for{" "}
                                        <strong>
                                            {resolveModal.storeName}
                                        </strong>
                                    </p>
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold text-dark">
                                            Resolution Note
                                        </label>
                                        <textarea
                                            className="form-control"
                                            rows="3"
                                            value={resolveNote}
                                            onChange={(e) =>
                                                setResolveNote(e.target.value)
                                            }
                                            placeholder="What action was taken? (optional)"
                                        />
                                    </div>
                                </div>
                                <div className="modal-footer bg-light border-0">
                                    <button
                                        className="btn btn-light border"
                                        onClick={() =>
                                            setResolveModal({
                                                show: false,
                                                flagId: null,
                                                storeName: "",
                                            })
                                        }
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        className="btn btn-success"
                                        onClick={submitResolve}
                                        disabled={resolving}
                                    >
                                        {resolving ? (
                                            <span className="spinner-border spinner-border-sm me-2"></span>
                                        ) : (
                                            <i className="fas fa-check me-2"></i>
                                        )}
                                        Mark Resolved
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                <AlertModal
                    show={alert.show}
                    type={alert.type}
                    message={alert.message}
                    onClose={() => setAlert({ ...alert, show: false })}
                />
            </div>
        </MainLayout>
    );
}
