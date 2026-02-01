import React, { useState, useEffect } from "react";
import { router, Link, usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";
import Select from "react-select";
import AlertModal from "../AlertModel";

export default function Index({
    auth,
    records,
    employees,
    stores,
    statusCounts,
    filters,
}) {
    const { flash } = usePage().props;
    const visits = records?.data || [];
    const pagination = records;

    const [alert, setAlert] = useState({ show: false, type: "", message: "" });

    // Show flash messages
    useEffect(() => {
        if (flash?.success) {
            setAlert({ show: true, type: "success", message: flash.success });
        }
        if (flash?.error) {
            setAlert({ show: true, type: "error", message: flash.error });
        }
    }, [flash]);

    const statusTabs = [
        {
            value: "all",
            label: "All Visits",
            count: statusCounts.all,
        },
        {
            value: "checked_in",
            label: "Active",
            count: statusCounts.checked_in,
        },
        {
            value: "completed",
            label: "Completed",
            count: statusCounts.completed,
        },
    ];

    const handleStatusChange = (status) => {
        router.get(
            "/store-visits",
            { ...filters, status, page: 1 },
            { preserveState: true },
        );
    };

    const getStatusBadge = (status) => {
        return (
            <span
                className={`badge ${status === "checked_in" ? "bg-dark" : "bg-secondary"}`}
            >
                {status === "checked_in" ? "ACTIVE" : "COMPLETED"}
            </span>
        );
    };

    const formatDuration = (minutes) => {
        if (!minutes) return "—";
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
    };

    return (
        <MainLayout user={auth.user} title="Store Visits">
            <div className="container-fluid py-4">
                {/* HEADER */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 className="mb-1 fw-bold text-dark">Store Visits</h2>
                        <p className="text-muted mb-0">
                            Monitor employee store visits and activities
                        </p>
                    </div>
                </div>

                {/* STATUS TABS - BLACK & WHITE */}
                <div className="card border-0 shadow-sm mb-4">
                    <div className="card-body p-3">
                        <div className="btn-group w-100" role="group">
                            {statusTabs.map((tab) => (
                                <button
                                    key={tab.value}
                                    type="button"
                                    className={`btn ${
                                        filters.status === tab.value
                                            ? "btn-dark text-white"
                                            : "btn-outline-dark"
                                    }`}
                                    onClick={() =>
                                        handleStatusChange(tab.value)
                                    }
                                >
                                    {tab.label}
                                    <span
                                        className={`badge ms-2 ${
                                            filters.status === tab.value
                                                ? "bg-white text-dark"
                                                : "bg-dark text-white"
                                        }`}
                                    >
                                        {tab.count}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* FILTERS */}
                <div className="card border-0 shadow-sm mb-4">
                    <div className="card-body p-4">
                        <div className="row g-3">
                            <div className="col-md-3">
                                <label className="form-label fw-semibold text-dark">
                                    Employee
                                </label>
                                <Select
                                    options={[
                                        { value: null, label: "All Employees" },
                                        ...employees.map((e) => ({
                                            value: e.id,
                                            label: e.name,
                                        })),
                                    ]}
                                    onChange={(option) =>
                                        router.get(
                                            "/store-visits",
                                            {
                                                ...filters,
                                                employee_id: option?.value,
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

                            <div className="col-md-3">
                                <label className="form-label fw-semibold text-dark">
                                    Store
                                </label>
                                <Select
                                    options={[
                                        { value: null, label: "All Stores" },
                                        ...stores.map((s) => ({
                                            value: s.id,
                                            label: s.name,
                                        })),
                                    ]}
                                    onChange={(option) =>
                                        router.get(
                                            "/store-visits",
                                            {
                                                ...filters,
                                                store_id: option?.value,
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

                            <div className="col-md-3">
                                <label className="form-label fw-semibold text-dark">
                                    From Date
                                </label>
                                <input
                                    type="date"
                                    className="form-control"
                                    value={filters.from_date || ""}
                                    onChange={(e) =>
                                        router.get(
                                            "/store-visits",
                                            {
                                                ...filters,
                                                from_date: e.target.value,
                                                page: 1,
                                            },
                                            { preserveState: true },
                                        )
                                    }
                                />
                            </div>

                            <div className="col-md-3">
                                <label className="form-label fw-semibold text-dark">
                                    To Date
                                </label>
                                <input
                                    type="date"
                                    className="form-control"
                                    value={filters.to_date || ""}
                                    onChange={(e) =>
                                        router.get(
                                            "/store-visits",
                                            {
                                                ...filters,
                                                to_date: e.target.value,
                                                page: 1,
                                            },
                                            { preserveState: true },
                                        )
                                    }
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* VISITS TABLE */}
                <div className="card border-0 shadow-sm">
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-hover mb-0">
                                <thead className="table-light">
                                    <tr>
                                        <th className="text-dark">#</th>
                                        <th className="text-dark">Date</th>
                                        <th className="text-dark">Employee</th>
                                        <th className="text-dark">Store</th>
                                        <th className="text-dark">Check-In</th>
                                        <th className="text-dark">Check-Out</th>
                                        <th className="text-dark">Duration</th>
                                        <th className="text-dark">Status</th>
                                        <th className="text-dark">Survey</th>
                                        <th className="text-dark">
                                            Stock Txns
                                        </th>
                                        <th className="text-dark">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {visits.length > 0 ? (
                                        visits.map((visit, index) => (
                                            <tr key={visit.id}>
                                                <td className="text-dark">
                                                    {(pagination.current_page -
                                                        1) *
                                                        pagination.per_page +
                                                        index +
                                                        1}
                                                </td>
                                                <td className="text-dark">
                                                    {new Date(
                                                        visit.visit_date,
                                                    ).toLocaleDateString()}
                                                </td>
                                                <td>
                                                    <div className="fw-semibold text-dark">
                                                        {visit.employee.name}
                                                    </div>
                                                    <small className="text-muted">
                                                        {
                                                            visit.employee.user
                                                                .email
                                                        }
                                                    </small>
                                                </td>
                                                <td>
                                                    <div className="fw-semibold text-dark">
                                                        {visit.store.name}
                                                    </div>
                                                    <small className="text-muted">
                                                        {visit.store.city?.name}
                                                        ,{" "}
                                                        {
                                                            visit.store.state
                                                                ?.name
                                                        }
                                                    </small>
                                                </td>
                                                <td className="text-dark">
                                                    {visit.check_in_time}
                                                </td>
                                                <td className="text-dark">
                                                    {visit.check_out_time ||
                                                        "—"}
                                                </td>
                                                <td className="text-dark">
                                                    {formatDuration(
                                                        visit.duration_minutes,
                                                    )}
                                                </td>
                                                <td>
                                                    {getStatusBadge(
                                                        visit.status,
                                                    )}
                                                </td>
                                                <td>
                                                    <span className="badge bg-dark">
                                                        {visit.survey_count ||
                                                            0}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span className="badge bg-dark">
                                                        {visit.stock_transactions_count ||
                                                            0}
                                                    </span>
                                                </td>
                                                <td>
                                                    <Link
                                                        href={`/store-visits/${visit.id}`}
                                                        className="btn btn-sm btn-dark text-white"
                                                        title="View Details"
                                                    >
                                                        <i className="fas fa-eye"></i>
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan="11"
                                                className="text-center py-5"
                                            >
                                                <i className="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                                <p className="text-muted mb-0">
                                                    No visits found
                                                </p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* PAGINATION */}
                        {pagination && pagination.last_page > 1 && (
                            <div className="p-3 border-top">
                                <nav>
                                    <ul className="pagination mb-0 justify-content-center">
                                        {pagination.links.map((link, index) => (
                                            <li
                                                key={index}
                                                className={`page-item ${link.active ? "active" : ""} ${!link.url ? "disabled" : ""}`}
                                            >
                                                <button
                                                    className="page-link text-dark"
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
                                                            : {}
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
