import React, { useState, useEffect } from "react";
import { router, Link, usePage } from "@inertiajs/react";
import DataTable from "react-data-table-component";
import MainLayout from "@/Layouts/MainLayout";
import AlertModal from "../AlertModel";

export default function EmployeeManagementIndex({
    auth,
    records,
    states,
    statistics,
    filters,
}) {
    const { flash } = usePage().props;
    const employees = records?.data || [];

    const [search, setSearch] = useState(filters.search || "");
    const [stateId, setStateId] = useState(filters.state_id || "");
    const [perPage, setPerPage] = useState(filters.per_page || 15);
    const [alert, setAlert] = useState({ show: false, type: "", message: "" });

    useEffect(() => {
        if (flash?.success)
            setAlert({ show: true, type: "success", message: flash.success });
        if (flash?.error)
            setAlert({ show: true, type: "error", message: flash.error });
    }, [flash]);

    const applyFilters = (overrides = {}) => {
        router.get(
            route("employee-management.index"),
            {
                search: search,
                state_id: stateId,
                per_page: perPage,
                ...overrides,
            },
            { preserveState: true, replace: true },
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        applyFilters();
    };

    // Plan status badge
    const planBadge = (plan) => {
        if (!plan) return <span className="badge bg-secondary">No plan</span>;
        if (plan.day_ended)
            return <span className="badge bg-success">Day ended</span>;
        if (plan.day_started)
            return <span className="badge bg-primary">Active</span>;
        return <span className="badge bg-warning text-dark">Plan set</span>;
    };

    const columns = [
        {
            name: "Employee",
            sortable: true,
            minWidth: "200px",
            cell: (row) => (
                <div className="py-2">
                    <div className="fw-semibold">{row.name}</div>
                    <div className="text-muted small">
                        {row.designation || "—"}
                    </div>
                    <div className="text-muted small">
                        <span className="badge bg-dark me-1">
                            {row.role_name}
                        </span>
                    </div>
                </div>
            ),
        },
        {
            name: "Location",
            minWidth: "160px",
            cell: (row) => (
                <div className="py-2 small text-muted">
                    {[row.area?.name, row.city?.name, row.state?.name]
                        .filter(Boolean)
                        .join(", ") || "—"}
                </div>
            ),
        },
        {
            name: "Manager",
            minWidth: "140px",
            cell: (row) => <div className="small">{row.manager_name}</div>,
        },
        {
            name: "Today's status",
            minWidth: "130px",
            cell: (row) => planBadge(row.today_plan),
        },
        {
            name: "Day time",
            minWidth: "170px",
            cell: (row) => {
                const p = row.today_plan;
                if (!p || !p.day_started)
                    return <span className="text-muted small">—</span>;
                return (
                    <div className="small">
                        <div>
                            <i className="fas fa-play-circle text-success me-1"></i>
                            {p.day_start_time || "—"}
                        </div>
                        {p.day_ended && (
                            <div>
                                <i className="fas fa-stop-circle text-danger me-1"></i>
                                {p.day_end_time || "—"}
                            </div>
                        )}
                    </div>
                );
            },
        },
        {
            name: "Plan progress",
            minWidth: "180px",
            cell: (row) => {
                const p = row.today_plan;
                if (!p) return <span className="text-muted small">—</span>;
                const total = p.stores_planned || 0;
                const visited = p.stores_visited || 0;
                const pct = total > 0 ? Math.round((visited / total) * 100) : 0;
                return (
                    <div className="w-100 py-1">
                        <div className="d-flex justify-content-between small mb-1">
                            <span>
                                {visited}/{total} stores
                            </span>
                            <span className="text-muted">{pct}%</span>
                        </div>
                        <div className="progress" style={{ height: "6px" }}>
                            <div
                                className="progress-bar bg-success"
                                style={{ width: `${pct}%` }}
                            />
                        </div>
                        {p.stores_skipped > 0 && (
                            <div className="text-muted small mt-1">
                                {p.stores_skipped} skipped
                            </div>
                        )}
                    </div>
                );
            },
        },
        {
            name: "Visits today",
            minWidth: "120px",
            center: true,
            cell: (row) => (
                <div className="text-center small">
                    <div className="fw-semibold">{row.today_visits_total}</div>
                    <div className="text-muted">
                        {row.today_visits_completed} done
                    </div>
                </div>
            ),
        },
        {
            name: "Actions",
            minWidth: "120px",
            right: true,
            cell: (row) => (
                <Link
                    href={route("employee-management.show", row.id)}
                    className="btn btn-sm btn-dark text-white"
                >
                    <i className="fas fa-eye me-1"></i>
                    View
                </Link>
            ),
        },
    ];

    const customStyles = {
        headCells: {
            style: {
                fontWeight: "600",
                fontSize: "13px",
                backgroundColor: "#f8f9fa",
                borderBottom: "2px solid #dee2e6",
                paddingTop: "12px",
                paddingBottom: "12px",
            },
        },
        rows: {
            style: {
                "&:hover": { backgroundColor: "#f8f9fa" },
            },
        },
    };

    return (
        <MainLayout auth={auth} title="Employee Management">
            <div className="container-fluid py-4">
                {/* Header */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 className="fw-bold mb-1">Employee Management</h4>
                        <p className="text-muted mb-0 small">
                            Monitor daily plans, route progress and field
                            activity
                        </p>
                    </div>
                </div>

                {/* Stats cards */}
                <div className="row g-3 mb-4">
                    {[
                        {
                            label: "Total employees",
                            value: statistics.total_employees,
                            icon: "fa-users",
                            color: "dark",
                        },
                        {
                            label: "Active today",
                            value: statistics.active_today,
                            icon: "fa-walking",
                            color: "primary",
                        },
                        {
                            label: "Store visits today",
                            value: statistics.visits_today,
                            icon: "fa-store",
                            color: "success",
                        },
                        {
                            label: "Completed visits",
                            value: statistics.completed_today,
                            icon: "fa-check-circle",
                            color: "info",
                        },
                    ].map((stat, i) => (
                        <div className="col-6 col-md-3" key={i}>
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-body d-flex align-items-center gap-3">
                                    <div
                                        className={`bg-${stat.color} bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center`}
                                        style={{
                                            width: 48,
                                            height: 48,
                                            flexShrink: 0,
                                        }}
                                    >
                                        <i
                                            className={`fas ${stat.icon} text-${stat.color}`}
                                        ></i>
                                    </div>
                                    <div>
                                        <div className="h4 fw-bold mb-0">
                                            {stat.value}
                                        </div>
                                        <div className="text-muted small">
                                            {stat.label}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Filters */}
                <div className="card border-0 shadow-sm mb-4">
                    <div className="card-body">
                        <form onSubmit={handleSearch}>
                            <div className="row g-3 align-items-end">
                                <div className="col-md-4">
                                    <label className="form-label small fw-semibold">
                                        Search
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        placeholder="Name, designation, contact..."
                                        value={search}
                                        onChange={(e) =>
                                            setSearch(e.target.value)
                                        }
                                    />
                                </div>
                                <div className="col-md-3">
                                    <label className="form-label small fw-semibold">
                                        State
                                    </label>
                                    <select
                                        className="form-select"
                                        value={stateId}
                                        onChange={(e) => {
                                            setStateId(e.target.value);
                                            applyFilters({
                                                state_id: e.target.value,
                                            });
                                        }}
                                    >
                                        <option value="">All states</option>
                                        {states.map((s) => (
                                            <option key={s.id} value={s.id}>
                                                {s.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="col-md-2">
                                    <label className="form-label small fw-semibold">
                                        Per page
                                    </label>
                                    <select
                                        className="form-select"
                                        value={perPage}
                                        onChange={(e) => {
                                            setPerPage(e.target.value);
                                            applyFilters({
                                                per_page: e.target.value,
                                            });
                                        }}
                                    >
                                        {[10, 15, 25, 50].map((n) => (
                                            <option key={n} value={n}>
                                                {n}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="col-md-2">
                                    <button
                                        type="submit"
                                        className="btn btn-dark w-100"
                                    >
                                        <i className="fas fa-search me-2"></i>
                                        Search
                                    </button>
                                </div>
                                <div className="col-md-1">
                                    <button
                                        type="button"
                                        className="btn btn-outline-secondary w-100"
                                        onClick={() => {
                                            setSearch("");
                                            setStateId("");
                                            applyFilters({
                                                search: "",
                                                state_id: "",
                                            });
                                        }}
                                    >
                                        <i className="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {/* Table */}
                <div className="card border-0 shadow-sm">
                    <div className="card-body p-0">
                        <DataTable
                            columns={columns}
                            data={employees}
                            customStyles={customStyles}
                            pagination
                            paginationServer
                            paginationTotalRows={records?.total || 0}
                            paginationPerPage={parseInt(perPage)}
                            paginationDefaultPage={records?.current_page || 1}
                            onChangePage={(page) =>
                                router.get(
                                    route("employee-management.index"),
                                    { ...filters, page },
                                    { preserveState: true },
                                )
                            }
                            onChangeRowsPerPage={(newPerPage) => {
                                setPerPage(newPerPage);
                                applyFilters({ per_page: newPerPage });
                            }}
                            noDataComponent={
                                <div className="py-5 text-center text-muted">
                                    <i className="fas fa-users fa-2x mb-3 d-block opacity-25"></i>
                                    No employees found
                                </div>
                            }
                            highlightOnHover
                            responsive
                        />
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
