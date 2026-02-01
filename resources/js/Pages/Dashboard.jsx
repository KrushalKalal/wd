import React from "react";
import { Link, usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";

export default function Dashboard() {
    const { auth, role, statistics, recentActivities, hierarchyBreakdown } =
        usePage().props;

    const getStatusBadge = (status) => {
        const badges = {
            checked_in: "bg-warning text-dark",
            completed: "bg-success text-white",
            cancelled: "bg-danger text-white",
        };
        return badges[status] || "bg-secondary text-white";
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
    };

    const formatTime = (timeString) => {
        if (!timeString) return "—";
        return new Date(`2000-01-01 ${timeString}`).toLocaleTimeString(
            "en-US",
            {
                hour: "2-digit",
                minute: "2-digit",
            },
        );
    };

    return (
        <MainLayout title="Dashboard" auth={auth}>
            <div className="container-fluid py-4">
                {/* HEADER */}
                <div className="mb-4">
                    <h2 className="mb-1 fw-bold" style={{ color: "#111111" }}>
                        Welcome back, {auth.user.name}!
                    </h2>
                    <div className="d-flex align-items-center gap-2">
                        <span
                            className="badge"
                            style={{
                                backgroundColor: "#111111",
                                color: "#ffffff",
                                fontSize: "0.9rem",
                                padding: "0.5rem 1rem",
                            }}
                        >
                            <i className="fas fa-user-shield me-2"></i>
                            {role}
                        </span>
                        <span className="text-muted">
                            {new Date().toLocaleDateString("en-US", {
                                weekday: "long",
                                year: "numeric",
                                month: "long",
                                day: "numeric",
                            })}
                        </span>
                    </div>
                </div>

                {/* LOCATION INFO (for region-specific roles) */}
                {(statistics.zone_name ||
                    statistics.state_name ||
                    statistics.city_name) && (
                    <div
                        className="alert mb-4"
                        style={{
                            backgroundColor: "#111111",
                            color: "#ffffff",
                            border: "none",
                        }}
                    >
                        <div className="d-flex align-items-center">
                            <i className="fas fa-map-marker-alt fa-2x me-3"></i>
                            <div>
                                <h5 className="mb-0 text-white">Your Region</h5>
                                <p className="mb-0 text-white">
                                    {statistics.zone_name &&
                                        `Zone: ${statistics.zone_name}`}
                                    {statistics.state_name &&
                                        `State: ${statistics.state_name}`}
                                    {statistics.city_name &&
                                        `City: ${statistics.city_name}`}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* STATISTICS CARDS */}
                <div className="row g-3 mb-4">
                    {/* Master Admin / Country Head Cards */}
                    {role === "Master Admin" || role === "Country Head" ? (
                        <>
                            <div className="col-md-3">
                                <div
                                    className="card border-0 shadow-sm h-100"
                                    style={{
                                        backgroundColor: "#111111",
                                    }}
                                >
                                    <div className="card-body">
                                        <div className="d-flex align-items-center">
                                            <div className="flex-grow-1">
                                                <p className="mb-1 text-white">
                                                    Total Zones
                                                </p>
                                                <h3 className="mb-0 fw-bold text-white">
                                                    {statistics.zones || 0}
                                                </h3>
                                            </div>
                                            <div className="ms-3">
                                                <i className="fas fa-globe fa-2x text-white"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="col-md-3">
                                <div
                                    className="card border-0 shadow-sm h-100"
                                    style={{
                                        backgroundColor: "#111111",
                                    }}
                                >
                                    <div className="card-body">
                                        <div className="d-flex align-items-center">
                                            <div className="flex-grow-1">
                                                <p className="mb-1 text-white">
                                                    Total States
                                                </p>
                                                <h3 className="mb-0 fw-bold text-white">
                                                    {statistics.states || 0}
                                                </h3>
                                            </div>
                                            <div className="ms-3">
                                                <i className="fas fa-map fa-2x text-white"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="col-md-3">
                                <div
                                    className="card border-0 shadow-sm h-100"
                                    style={{
                                        backgroundColor: "#111111",
                                    }}
                                >
                                    <div className="card-body">
                                        <div className="d-flex align-items-center">
                                            <div className="flex-grow-1">
                                                <p className="mb-1 text-white">
                                                    Total Cities
                                                </p>
                                                <h3 className="mb-0 fw-bold text-white">
                                                    {statistics.cities || 0}
                                                </h3>
                                            </div>
                                            <div className="ms-3">
                                                <i className="fas fa-city fa-2x text-white"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="col-md-3">
                                <div
                                    className="card border-0 shadow-sm h-100"
                                    style={{
                                        backgroundColor: "#111111",
                                    }}
                                >
                                    <div className="card-body">
                                        <div className="d-flex align-items-center">
                                            <div className="flex-grow-1">
                                                <p className="mb-1 text-white">
                                                    Total Areas
                                                </p>
                                                <h3 className="mb-0 fw-bold text-white">
                                                    {statistics.areas || 0}
                                                </h3>
                                            </div>
                                            <div className="ms-3">
                                                <i className="fas fa-map-marked-alt fa-2x text-white"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </>
                    ) : null}

                    {/* Regional Head Cards (Zonal/State/City) */}
                    {(role === "Zonal Head" ||
                        role === "State Head" ||
                        role === "City Head") && (
                        <>
                            {statistics.states && (
                                <div className="col-md-3">
                                    <div
                                        className="card border-0 shadow-sm h-100"
                                        style={{
                                            backgroundColor: "#111111",
                                        }}
                                    >
                                        <div className="card-body">
                                            <div className="d-flex align-items-center">
                                                <div className="flex-grow-1">
                                                    <p className="mb-1 text-white">
                                                        States
                                                    </p>
                                                    <h3 className="mb-0 fw-bold text-white">
                                                        {statistics.states}
                                                    </h3>
                                                </div>
                                                <div className="ms-3">
                                                    <i className="fas fa-map fa-2x text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {statistics.cities !== undefined && (
                                <div className="col-md-3">
                                    <div
                                        className="card border-0 shadow-sm h-100"
                                        style={{
                                            backgroundColor: "#111111",
                                        }}
                                    >
                                        <div className="card-body">
                                            <div className="d-flex align-items-center">
                                                <div className="flex-grow-1">
                                                    <p className="mb-1 text-white">
                                                        Cities
                                                    </p>
                                                    <h3 className="mb-0 fw-bold text-white">
                                                        {statistics.cities}
                                                    </h3>
                                                </div>
                                                <div className="ms-3">
                                                    <i className="fas fa-city fa-2x text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {statistics.areas !== undefined && (
                                <div className="col-md-3">
                                    <div
                                        className="card border-0 shadow-sm h-100"
                                        style={{
                                            backgroundColor: "#111111",
                                        }}
                                    >
                                        <div className="card-body">
                                            <div className="d-flex align-items-center">
                                                <div className="flex-grow-1">
                                                    <p className="mb-1 text-white">
                                                        Areas
                                                    </p>
                                                    <h3 className="mb-0 fw-bold text-white">
                                                        {statistics.areas}
                                                    </h3>
                                                </div>
                                                <div className="ms-3">
                                                    <i className="fas fa-map-marked-alt fa-2x text-white"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </>
                    )}

                    {/* Common Cards for All Roles */}
                    {statistics.stores !== undefined && (
                        <div className="col-md-3">
                            <div
                                className="card border-0 shadow-sm h-100"
                                style={{
                                    backgroundColor: "#111111",
                                }}
                            >
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="flex-grow-1">
                                            <p className="mb-1 text-white">
                                                {role === "Sales Employee"
                                                    ? "Assigned Stores"
                                                    : "Total Stores"}
                                            </p>
                                            <h3 className="mb-0 fw-bold text-white">
                                                {statistics.stores ||
                                                    statistics.assigned_stores ||
                                                    0}
                                            </h3>
                                        </div>
                                        <div className="ms-3">
                                            <i className="fas fa-store fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {statistics.employees !== undefined && (
                        <div className="col-md-3">
                            <div
                                className="card border-0 shadow-sm h-100"
                                style={{
                                    backgroundColor: "#111111",
                                }}
                            >
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="flex-grow-1">
                                            <p className="mb-1 text-white">
                                                Employees
                                            </p>
                                            <h3 className="mb-0 fw-bold text-white">
                                                {statistics.employees}
                                            </h3>
                                        </div>
                                        <div className="ms-3">
                                            <i className="fas fa-users fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {statistics.products !== undefined && (
                        <div className="col-md-3">
                            <div
                                className="card border-0 shadow-sm h-100"
                                style={{
                                    backgroundColor: "#111111",
                                }}
                            >
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="flex-grow-1">
                                            <p className="mb-1 text-white">
                                                Products
                                            </p>
                                            <h3 className="mb-0 fw-bold text-white">
                                                {statistics.products}
                                            </h3>
                                        </div>
                                        <div className="ms-3">
                                            <i className="fas fa-box fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {statistics.branches !== undefined && (
                        <div className="col-md-3">
                            <div
                                className="card border-0 shadow-sm h-100"
                                style={{
                                    backgroundColor: "#111111",
                                }}
                            >
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="flex-grow-1">
                                            <p className="mb-1 text-white">
                                                Branches
                                            </p>
                                            <h3 className="mb-0 fw-bold text-white">
                                                {statistics.branches}
                                            </h3>
                                        </div>
                                        <div className="ms-3">
                                            <i className="fas fa-building fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* VISIT STATISTICS */}
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <div
                            className="card border-0 shadow-sm h-100"
                            style={{
                                backgroundColor: "#111111",
                            }}
                        >
                            <div className="card-body">
                                <div className="d-flex align-items-center">
                                    <div className="flex-grow-1">
                                        <p className="mb-1 text-white">
                                            Total Visits
                                        </p>
                                        <h3 className="mb-0 fw-bold text-white">
                                            {statistics.total_visits ||
                                                statistics.completed_visits ||
                                                0}
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
                            className="card border-0 shadow-sm h-100"
                            style={{
                                backgroundColor: "#111111",
                            }}
                        >
                            <div className="card-body">
                                <div className="d-flex align-items-center">
                                    <div className="flex-grow-1">
                                        <p className="mb-1 text-white">
                                            Visits Today
                                        </p>
                                        <h3 className="mb-0 fw-bold text-white">
                                            {statistics.visits_today ||
                                                statistics.completed_visits_today ||
                                                0}
                                        </h3>
                                    </div>
                                    <div className="ms-3">
                                        <i className="fas fa-clock fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {statistics.active_visits !== undefined && (
                        <div className="col-md-3">
                            <div
                                className="card border-0 shadow-sm h-100"
                                style={{
                                    backgroundColor: "#111111",
                                }}
                            >
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="flex-grow-1">
                                            <p className="mb-1 text-white">
                                                Active Visits
                                            </p>
                                            <h3 className="mb-0 fw-bold text-white">
                                                {statistics.active_visits}
                                            </h3>
                                        </div>
                                        <div className="ms-3">
                                            <i className="fas fa-user-clock fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="col-md-3">
                        <div
                            className="card border-0 shadow-sm h-100"
                            style={{
                                backgroundColor: "#111111",
                            }}
                        >
                            <div className="card-body">
                                <div className="d-flex align-items-center">
                                    <div className="flex-grow-1">
                                        <p className="mb-1 text-white">
                                            Pending Surveys
                                        </p>
                                        <h3 className="mb-0 fw-bold text-white">
                                            {statistics.pending_surveys || 0}
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
                            className="card border-0 shadow-sm h-100"
                            style={{
                                backgroundColor: "#111111",
                            }}
                        >
                            <div className="card-body">
                                <div className="d-flex align-items-center">
                                    <div className="flex-grow-1">
                                        <p className="mb-1 text-white">
                                            Pending Stock
                                        </p>
                                        <h3 className="mb-0 fw-bold text-white">
                                            {statistics.pending_stock || 0}
                                        </h3>
                                    </div>
                                    <div className="ms-3">
                                        <i className="fas fa-boxes fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {statistics.approved_surveys !== undefined && (
                        <div className="col-md-3">
                            <div
                                className="card border-0 shadow-sm h-100"
                                style={{
                                    backgroundColor: "#111111",
                                }}
                            >
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="flex-grow-1">
                                            <p className="mb-1 text-white">
                                                Approved Surveys
                                            </p>
                                            <h3 className="mb-0 fw-bold text-white">
                                                {statistics.approved_surveys}
                                            </h3>
                                        </div>
                                        <div className="ms-3">
                                            <i className="fas fa-check-circle fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {statistics.approved_stock !== undefined && (
                        <div className="col-md-3">
                            <div
                                className="card border-0 shadow-sm h-100"
                                style={{
                                    backgroundColor: "#111111",
                                }}
                            >
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="flex-grow-1">
                                            <p className="mb-1 text-white">
                                                Approved Stock
                                            </p>
                                            <h3 className="mb-0 fw-bold text-white">
                                                {statistics.approved_stock}
                                            </h3>
                                        </div>
                                        <div className="ms-3">
                                            <i className="fas fa-check-double fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* RECENT ACTIVITIES & HIERARCHY */}
                <div className="row g-4">
                    {/* RECENT STORE VISITS */}
                    {recentActivities?.recent_visits &&
                        recentActivities.recent_visits.length > 0 && (
                            <div className="col-md-6">
                                <div className="card border-0 shadow-sm">
                                    <div
                                        className="card-header border-0"
                                        style={{
                                            backgroundColor: "#111111",
                                        }}
                                    >
                                        <h5 className="mb-0 text-white">
                                            <i className="fas fa-history me-2"></i>
                                            Recent Store Visits
                                        </h5>
                                    </div>
                                    <div className="card-body p-0">
                                        <div className="list-group list-group-flush">
                                            {recentActivities.recent_visits.map(
                                                (visit, index) => (
                                                    <Link
                                                        key={index}
                                                        href={`/store-management/${visit.id}`}
                                                        className="list-group-item list-group-item-action"
                                                    >
                                                        <div className="d-flex justify-content-between align-items-start">
                                                            <div className="flex-grow-1">
                                                                <h6 className="mb-1 text-dark fw-semibold">
                                                                    {
                                                                        visit.store_name
                                                                    }
                                                                </h6>
                                                                {visit.employee_name && (
                                                                    <p className="mb-1 text-muted small">
                                                                        <i className="fas fa-user me-1"></i>
                                                                        {
                                                                            visit.employee_name
                                                                        }
                                                                    </p>
                                                                )}
                                                                <p className="mb-0 text-muted small">
                                                                    <i className="fas fa-calendar me-1"></i>
                                                                    {formatDate(
                                                                        visit.date,
                                                                    )}{" "}
                                                                    at{" "}
                                                                    {formatTime(
                                                                        visit.time,
                                                                    )}
                                                                </p>
                                                            </div>
                                                            <span
                                                                className={`badge ${getStatusBadge(visit.status)}`}
                                                            >
                                                                {visit.status
                                                                    .toUpperCase()
                                                                    .replace(
                                                                        "_",
                                                                        " ",
                                                                    )}
                                                            </span>
                                                        </div>
                                                    </Link>
                                                ),
                                            )}
                                        </div>
                                        <div className="card-footer bg-light border-0 text-center">
                                            <Link
                                                href="/store-management"
                                                className="btn btn-sm"
                                                style={{
                                                    backgroundColor: "#111111",
                                                    color: "#ffffff",
                                                }}
                                            >
                                                View All Store Management
                                                <i className="fas fa-arrow-right ms-2"></i>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                    {/* HIERARCHY BREAKDOWN */}
                    {hierarchyBreakdown &&
                        (hierarchyBreakdown.zones ||
                            hierarchyBreakdown.states ||
                            hierarchyBreakdown.cities ||
                            hierarchyBreakdown.areas) && (
                            <div className="col-md-6">
                                <div className="card border-0 shadow-sm">
                                    <div
                                        className="card-header border-0"
                                        style={{
                                            backgroundColor: "#111111",
                                        }}
                                    >
                                        <h5 className="mb-0 text-white">
                                            <i className="fas fa-sitemap me-2"></i>
                                            Hierarchy Breakdown
                                        </h5>
                                    </div>
                                    <div className="card-body">
                                        <div className="table-responsive">
                                            <table className="table table-sm table-hover mb-0">
                                                <thead className="table-light">
                                                    <tr>
                                                        <th className="text-dark">
                                                            Name
                                                        </th>
                                                        {hierarchyBreakdown.zones && (
                                                            <th className="text-dark">
                                                                States
                                                            </th>
                                                        )}
                                                        {hierarchyBreakdown.states && (
                                                            <th className="text-dark">
                                                                Cities
                                                            </th>
                                                        )}
                                                        {hierarchyBreakdown.cities && (
                                                            <th className="text-dark">
                                                                Areas
                                                            </th>
                                                        )}
                                                        <th className="text-dark">
                                                            Stores
                                                        </th>
                                                        <th className="text-dark">
                                                            Employees
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {(
                                                        hierarchyBreakdown.zones ||
                                                        hierarchyBreakdown.states ||
                                                        hierarchyBreakdown.cities ||
                                                        hierarchyBreakdown.areas ||
                                                        []
                                                    ).map((item, index) => (
                                                        <tr key={index}>
                                                            <td className="fw-semibold text-dark">
                                                                {item.name}
                                                            </td>
                                                            {item.states_count !==
                                                                undefined && (
                                                                <td>
                                                                    <span className="badge bg-dark">
                                                                        {
                                                                            item.states_count
                                                                        }
                                                                    </span>
                                                                </td>
                                                            )}
                                                            {item.cities_count !==
                                                                undefined && (
                                                                <td>
                                                                    <span className="badge bg-dark">
                                                                        {
                                                                            item.cities_count
                                                                        }
                                                                    </span>
                                                                </td>
                                                            )}
                                                            {item.areas_count !==
                                                                undefined && (
                                                                <td>
                                                                    <span className="badge bg-dark">
                                                                        {
                                                                            item.areas_count
                                                                        }
                                                                    </span>
                                                                </td>
                                                            )}
                                                            <td>
                                                                <span className="badge bg-dark">
                                                                    {item.stores_count ||
                                                                        0}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span className="badge bg-dark">
                                                                    {item.employees_count ||
                                                                        0}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                </div>
            </div>
        </MainLayout>
    );
}
