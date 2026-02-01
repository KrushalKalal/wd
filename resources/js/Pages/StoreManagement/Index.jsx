import React, { useState, useEffect } from "react";
import { router, Link, usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";
import Select from "react-select";
import AlertModal from "../AlertModel";

export default function Index({ auth, records, states, statistics, filters }) {
    const { flash } = usePage().props;
    const stores = records?.data || [];
    const pagination = records;

    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [expandedStore, setExpandedStore] = useState(null);

    useEffect(() => {
        if (flash?.success) {
            setAlert({ show: true, type: "success", message: flash.success });
        }
        if (flash?.error) {
            setAlert({ show: true, type: "error", message: flash.error });
        }
    }, [flash]);

    const getSurveyStatusColor = (stats) => {
        if (stats.total === 0) return "bg-secondary";
        if (stats.pending > 0 || stats.needs_review > 0)
            return "bg-warning text-dark";
        if (stats.rejected > 0) return "bg-danger text-white";
        return "bg-success text-white";
    };

    const getStockStatusColor = (stats) => {
        if (stats.total === 0) return "bg-secondary";
        if (stats.pending > 0) return "bg-warning text-dark";
        if (stats.rejected > 0) return "bg-danger text-white";
        return "bg-success text-white";
    };

    const getVisitStatusBadge = (status) => {
        const badges = {
            checked_in: "bg-dark text-white",
            completed: "bg-secondary text-white",
            cancelled: "bg-danger text-white",
        };
        return (
            <span className={`badge ${badges[status] || "bg-secondary"}`}>
                {status?.toUpperCase().replace("_", " ")}
            </span>
        );
    };

    return (
        <MainLayout user={auth.user} title="Store Management">
            <div className="container-fluid py-4">
                {/* HEADER */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 className="mb-1 fw-bold text-dark">
                            Store Management Stock & Surveys
                        </h2>
                        <p className="text-muted mb-0">
                            Manage store visits, surveys, and stock approvals in
                            one place
                        </p>
                    </div>
                </div>

                {/* STATISTICS CARDS */}
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <div
                            className="card border-0 shadow-sm"
                            style={{ backgroundColor: "#111111" }}
                        >
                            <div className="card-body">
                                <div className="d-flex align-items-center">
                                    <div className="flex-grow-1">
                                        <p className="mb-1 text-white">
                                            Total Stores
                                        </p>
                                        <h3 className="mb-0 fw-bold text-white">
                                            {statistics.total_stores}
                                        </h3>
                                    </div>
                                    <div className="ms-3">
                                        <i className="fas fa-store fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="col-md-3">
                        <div
                            className="card border-0 shadow-sm"
                            style={{ backgroundColor: "#111111" }}
                        >
                            <div className="card-body">
                                <div className="d-flex align-items-center">
                                    <div className="flex-grow-1">
                                        <p className="mb-1 text-white">
                                            Visited Today
                                        </p>
                                        <h3 className="mb-0 fw-bold text-white">
                                            {statistics.visited_today}
                                        </h3>
                                    </div>
                                    <div className="ms-3">
                                        <i className="fas fa-calendar-check fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="col-md-3">
                        <div
                            className="card border-0 shadow-sm"
                            style={{ backgroundColor: "#111111" }}
                        >
                            <div className="card-body">
                                <div className="d-flex align-items-center">
                                    <div className="flex-grow-1">
                                        <p className="mb-1 text-white">
                                            Pending Surveys
                                        </p>
                                        <h3 className="mb-0 fw-bold text-white">
                                            {statistics.pending_surveys}
                                        </h3>
                                    </div>
                                    <div className="ms-3">
                                        <i className="fas fa-clipboard-list fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="col-md-3">
                        <div
                            className="card border-0 shadow-sm"
                            style={{ backgroundColor: "#111111" }}
                        >
                            <div className="card-body">
                                <div className="d-flex align-items-center">
                                    <div className="flex-grow-1">
                                        <p className="mb-1 text-white">
                                            Pending Stock
                                        </p>
                                        <h3 className="mb-0 fw-bold text-white">
                                            {statistics.pending_stock}
                                        </h3>
                                    </div>
                                    <div className="ms-3">
                                        <i className="fas fa-boxes fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* FILTERS */}
                <div className="card border-0 shadow-sm mb-4">
                    <div className="card-body p-4">
                        <div className="row g-3">
                            <div className="col-md-4">
                                <label className="form-label fw-semibold text-dark">
                                    <i className="fas fa-search me-2"></i>
                                    Search Store
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Store name, contact..."
                                    value={filters.search || ""}
                                    onChange={(e) =>
                                        router.get(
                                            "/store-management",
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

                            <div className="col-md-4">
                                <label className="form-label fw-semibold text-dark">
                                    <i className="fas fa-map-marker-alt me-2"></i>
                                    State
                                </label>
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
                                            ? states.find(
                                                  (s) =>
                                                      s.id === filters.state_id,
                                              )
                                                ? {
                                                      value: filters.state_id,
                                                      label: states.find(
                                                          (s) =>
                                                              s.id ===
                                                              filters.state_id,
                                                      ).name,
                                                  }
                                                : null
                                            : null
                                    }
                                    onChange={(option) =>
                                        router.get(
                                            "/store-management",
                                            {
                                                ...filters,
                                                state_id: option?.value,
                                                page: 1,
                                            },
                                            { preserveState: true },
                                        )
                                    }
                                    isClearable
                                    styles={{
                                        control: (base) => ({
                                            ...base,
                                            borderColor: "#dee2e6",
                                        }),
                                    }}
                                />
                            </div>

                            <div className="col-md-4">
                                <label className="form-label fw-semibold text-dark">
                                    <i className="fas fa-filter me-2"></i>
                                    Visit Status
                                </label>
                                <Select
                                    options={[
                                        { value: "all", label: "All Visits" },
                                        {
                                            value: "checked_in",
                                            label: "Checked In",
                                        },
                                        {
                                            value: "completed",
                                            label: "Completed",
                                        },
                                    ]}
                                    value={{
                                        value: filters.visit_status,
                                        label:
                                            filters.visit_status === "all"
                                                ? "All Visits"
                                                : filters.visit_status ===
                                                    "checked_in"
                                                  ? "Checked In"
                                                  : "Completed",
                                    }}
                                    onChange={(option) =>
                                        router.get(
                                            "/store-management",
                                            {
                                                ...filters,
                                                visit_status: option?.value,
                                                page: 1,
                                            },
                                            { preserveState: true },
                                        )
                                    }
                                    styles={{
                                        control: (base) => ({
                                            ...base,
                                            borderColor: "#dee2e6",
                                        }),
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* STORES TABLE */}
                <div className="card border-0 shadow-sm">
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-hover mb-0">
                                <thead className="">
                                    <tr>
                                        <th
                                            style={{
                                                backgroundColor: "#111111",
                                                color: "#fff",
                                            }}
                                        >
                                            #
                                        </th>
                                        <th
                                            style={{
                                                backgroundColor: "#111111",
                                                color: "#fff",
                                            }}
                                        >
                                            Store Details
                                        </th>
                                        <th
                                            style={{
                                                backgroundColor: "#111111",
                                                color: "#fff",
                                            }}
                                        >
                                            Location
                                        </th>
                                        <th
                                            style={{
                                                backgroundColor: "#111111",
                                                color: "#fff",
                                            }}
                                        >
                                            Last Visit
                                        </th>
                                        <th
                                            style={{
                                                backgroundColor: "#111111",
                                                color: "#fff",
                                            }}
                                        >
                                            Survey Status
                                        </th>
                                        <th
                                            style={{
                                                backgroundColor: "#111111",
                                                color: "#fff",
                                            }}
                                        >
                                            Stock Status
                                        </th>
                                        <th
                                            style={{
                                                backgroundColor: "#111111",
                                                color: "#fff",
                                            }}
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {stores.length > 0 ? (
                                        stores.map((store, index) => (
                                            <tr key={store.id}>
                                                <td className="text-dark">
                                                    {(pagination.current_page -
                                                        1) *
                                                        pagination.per_page +
                                                        index +
                                                        1}
                                                </td>
                                                <td>
                                                    <div className="fw-bold text-dark">
                                                        {store.name}
                                                    </div>
                                                    <small className="text-muted">
                                                        {store.contact_number_1 ||
                                                            "No contact"}
                                                    </small>
                                                </td>
                                                <td>
                                                    <div className="text-dark">
                                                        {store.city?.name}
                                                    </div>
                                                    <small className="text-muted">
                                                        {store.state?.name}
                                                    </small>
                                                </td>
                                                <td>
                                                    {store.latest_visit ? (
                                                        <div>
                                                            <div className="text-dark small">
                                                                {new Date(
                                                                    store
                                                                        .latest_visit
                                                                        .date,
                                                                ).toLocaleDateString()}
                                                            </div>
                                                            <small className="text-muted">
                                                                {
                                                                    store
                                                                        .latest_visit
                                                                        .employee_name
                                                                }
                                                            </small>
                                                            <div className="mt-1">
                                                                {getVisitStatusBadge(
                                                                    store
                                                                        .latest_visit
                                                                        .status,
                                                                )}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted">
                                                            Never visited
                                                        </span>
                                                    )}
                                                </td>
                                                <td>
                                                    {store.survey_stats.total >
                                                    0 ? (
                                                        <div>
                                                            <span
                                                                className={`badge ${getSurveyStatusColor(store.survey_stats)} mb-1`}
                                                            >
                                                                {
                                                                    store
                                                                        .survey_stats
                                                                        .total
                                                                }{" "}
                                                                Answers
                                                            </span>
                                                            <div className="small text-muted">
                                                                {store
                                                                    .survey_stats
                                                                    .pending >
                                                                    0 && (
                                                                    <span className="me-2">
                                                                        <i className="fas fa-clock text-warning"></i>{" "}
                                                                        {
                                                                            store
                                                                                .survey_stats
                                                                                .pending
                                                                        }
                                                                    </span>
                                                                )}
                                                                {store
                                                                    .survey_stats
                                                                    .approved >
                                                                    0 && (
                                                                    <span>
                                                                        <i className="fas fa-check text-success"></i>{" "}
                                                                        {
                                                                            store
                                                                                .survey_stats
                                                                                .approved
                                                                        }
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="badge bg-secondary">
                                                            No surveys
                                                        </span>
                                                    )}
                                                </td>
                                                <td>
                                                    {store.stock_stats.total >
                                                    0 ? (
                                                        <div>
                                                            <span
                                                                className={`badge ${getStockStatusColor(store.stock_stats)} mb-1`}
                                                            >
                                                                {
                                                                    store
                                                                        .stock_stats
                                                                        .total
                                                                }{" "}
                                                                Transactions
                                                            </span>
                                                            <div className="small text-muted">
                                                                {store
                                                                    .stock_stats
                                                                    .pending >
                                                                    0 && (
                                                                    <span className="me-2">
                                                                        <i className="fas fa-clock text-warning"></i>{" "}
                                                                        {
                                                                            store
                                                                                .stock_stats
                                                                                .pending
                                                                        }
                                                                    </span>
                                                                )}
                                                                {store
                                                                    .stock_stats
                                                                    .delivered >
                                                                    0 && (
                                                                    <span>
                                                                        <i className="fas fa-truck text-success"></i>{" "}
                                                                        {
                                                                            store
                                                                                .stock_stats
                                                                                .delivered
                                                                        }
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="badge bg-secondary">
                                                            No stock
                                                        </span>
                                                    )}
                                                </td>
                                                <td>
                                                    <Link
                                                        href={`/store-management/${store.id}`}
                                                        className="btn btn-sm btn-dark text-white"
                                                        title="View Details"
                                                    >
                                                        <i className="fas fa-eye me-1"></i>
                                                        Details
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan="7"
                                                className="text-center py-5"
                                            >
                                                <i className="fas fa-store fa-3x text-muted mb-3 d-block"></i>
                                                <p className="text-muted mb-0">
                                                    No stores found
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
                                        {pagination.links.map((link, index) => (
                                            <li
                                                key={index}
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

                {/* ALERT MODAL */}
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
