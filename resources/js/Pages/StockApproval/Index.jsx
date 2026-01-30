import React, { useState, useEffect } from "react";
import { router, Link, usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";
import Select from "react-select";
import axios from "axios";
import AlertModal from "../AlertModel";

export default function Index({
    auth,
    records,
    employees,
    stores,
    products,
    statusCounts,
    filters,
}) {
    const { flash } = usePage().props;
    const transactions = records?.data || [];
    const pagination = records;

    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [actionModal, setActionModal] = useState({
        show: false,
        type: "",
        transactionId: null,
    });
    const [remark, setRemark] = useState("");
    const [selectedIds, setSelectedIds] = useState([]);

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
            value: "pending",
            label: "Pending",
            count: statusCounts.pending,
            color: "warning",
        },
        {
            value: "approved",
            label: "Approved",
            count: statusCounts.approved,
            color: "info",
        },
        {
            value: "delivered",
            label: "Delivered",
            count: statusCounts.delivered,
            color: "success",
        },
        {
            value: "returned",
            label: "Returned",
            count: statusCounts.returned,
            color: "danger",
        },
        {
            value: "rejected",
            label: "Rejected",
            count: statusCounts.rejected,
            color: "secondary",
        },
        {
            value: "all",
            label: "All",
            count: Object.values(statusCounts).reduce((a, b) => a + b, 0),
            color: "dark",
        },
    ];

    const handleStatusChange = (status) => {
        router.get(
            "/stock-approvals",
            { ...filters, status, page: 1 },
            { preserveState: true },
        );
    };

    const handleAction = (type, transactionId) => {
        setActionModal({ show: true, type, transactionId });
        setRemark("");
    };

    const confirmAction = async () => {
        try {
            const { type, transactionId } = actionModal;
            let endpoint = "";

            switch (type) {
                case "approve":
                    endpoint = `/stock-approvals/${transactionId}/approve`;
                    break;
                case "reject":
                    endpoint = `/stock-approvals/${transactionId}/reject`;
                    break;
                case "deliver":
                    endpoint = `/stock-approvals/${transactionId}/deliver`;
                    break;
                case "return":
                    endpoint = `/stock-approvals/${transactionId}/return`;
                    break;
            }

            const response = await axios.post(endpoint, {
                admin_remark: remark,
            });

            if (response.data.success) {
                setAlert({
                    show: true,
                    type: "success",
                    message: response.data.message,
                });
                setActionModal({ show: false, type: "", transactionId: null });
                router.reload();
            }
        } catch (error) {
            setAlert({
                show: true,
                type: "error",
                message: error.response?.data?.message || "Action failed",
            });
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            pending: "bg-warning",
            approved: "bg-info",
            delivered: "bg-success",
            returned: "bg-danger",
            rejected: "bg-secondary",
        };
        return (
            <span className={`badge ${badges[status]}`}>
                {status.toUpperCase()}
            </span>
        );
    };

    const getTypeBadge = (type) => {
        return type === "add" ? (
            <span className="badge bg-success">
                <i className="fas fa-plus me-1"></i>ADD
            </span>
        ) : (
            <span className="badge bg-danger">
                <i className="fas fa-minus me-1"></i>RETURN
            </span>
        );
    };

    return (
        <MainLayout user={auth.user} title="Stock Approval">
            <div className="container-fluid py-4">
                {/* HEADER */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 className="mb-1 fw-bold">Stock Approval System</h2>
                        <p className="text-muted mb-0">
                            Approve or reject stock transactions
                        </p>
                    </div>
                </div>

                {/* STATUS TABS */}
                <div className="card border-0 shadow-sm mb-4">
                    <div className="card-body p-3">
                        <div className="btn-group w-100" role="group">
                            {statusTabs.map((tab) => (
                                <button
                                    key={tab.value}
                                    type="button"
                                    className={`btn btn-outline-${tab.color} ${
                                        filters.status === tab.value
                                            ? `active`
                                            : ""
                                    }`}
                                    onClick={() =>
                                        handleStatusChange(tab.value)
                                    }
                                >
                                    {tab.label}
                                    <span className="badge bg-white text-dark ms-2">
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
                                <label className="form-label fw-semibold">
                                    Type
                                </label>
                                <Select
                                    options={[
                                        { value: null, label: "All Types" },
                                        { value: "add", label: "Add Stock" },
                                        {
                                            value: "return",
                                            label: "Return Stock",
                                        },
                                    ]}
                                    value={
                                        filters.type
                                            ? {
                                                  value: filters.type,
                                                  label:
                                                      filters.type === "add"
                                                          ? "Add Stock"
                                                          : "Return Stock",
                                              }
                                            : null
                                    }
                                    onChange={(option) =>
                                        router.get(
                                            "/stock-approvals",
                                            {
                                                ...filters,
                                                type: option?.value,
                                                page: 1,
                                            },
                                            { preserveState: true },
                                        )
                                    }
                                    isClearable
                                />
                            </div>

                            <div className="col-md-3">
                                <label className="form-label fw-semibold">
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
                                            "/stock-approvals",
                                            {
                                                ...filters,
                                                employee_id: option?.value,
                                                page: 1,
                                            },
                                            { preserveState: true },
                                        )
                                    }
                                    isClearable
                                />
                            </div>

                            <div className="col-md-3">
                                <label className="form-label fw-semibold">
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
                                            "/stock-approvals",
                                            {
                                                ...filters,
                                                store_id: option?.value,
                                                page: 1,
                                            },
                                            { preserveState: true },
                                        )
                                    }
                                    isClearable
                                />
                            </div>

                            <div className="col-md-3">
                                <label className="form-label fw-semibold">
                                    Product
                                </label>
                                <Select
                                    options={[
                                        { value: null, label: "All Products" },
                                        ...products.map((p) => ({
                                            value: p.id,
                                            label: p.name,
                                        })),
                                    ]}
                                    onChange={(option) =>
                                        router.get(
                                            "/stock-approvals",
                                            {
                                                ...filters,
                                                product_id: option?.value,
                                                page: 1,
                                            },
                                            { preserveState: true },
                                        )
                                    }
                                    isClearable
                                />
                            </div>

                            <div className="col-md-3">
                                <label className="form-label fw-semibold">
                                    From Date
                                </label>
                                <input
                                    type="date"
                                    className="form-control"
                                    value={filters.from_date || ""}
                                    onChange={(e) =>
                                        router.get(
                                            "/stock-approvals",
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
                                <label className="form-label fw-semibold">
                                    To Date
                                </label>
                                <input
                                    type="date"
                                    className="form-control"
                                    value={filters.to_date || ""}
                                    onChange={(e) =>
                                        router.get(
                                            "/stock-approvals",
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

                {/* TRANSACTIONS TABLE */}
                <div className="card border-0 shadow-sm">
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-hover mb-0">
                                <thead className="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Store</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.map((txn, index) => (
                                        <tr key={txn.id}>
                                            <td>
                                                {(pagination.current_page - 1) *
                                                    pagination.per_page +
                                                    index +
                                                    1}
                                            </td>
                                            <td>
                                                {new Date(
                                                    txn.created_at,
                                                ).toLocaleDateString()}
                                            </td>
                                            <td>
                                                <div className="fw-semibold">
                                                    {txn.employee.name}
                                                </div>
                                                <small className="text-muted">
                                                    {txn.employee.user.email}
                                                </small>
                                            </td>
                                            <td>
                                                <div className="fw-semibold">
                                                    {txn.store.name}
                                                </div>
                                                <small className="text-muted">
                                                    {txn.store.city?.name},{" "}
                                                    {txn.store.state?.name}
                                                </small>
                                            </td>
                                            <td>
                                                <div className="fw-semibold">
                                                    {txn.product.name}
                                                </div>
                                                <small className="text-muted">
                                                    MRP: ₹{txn.product.mrp}
                                                </small>
                                            </td>
                                            <td>{getTypeBadge(txn.type)}</td>
                                            <td>
                                                <span className="badge bg-dark">
                                                    {txn.quantity}
                                                </span>
                                            </td>
                                            <td>
                                                {getStatusBadge(txn.status)}
                                            </td>
                                            <td>
                                                <div className="btn-group btn-group-sm">
                                                    <Link
                                                        href={`/stock-approvals/${txn.id}`}
                                                        className="btn btn-outline-dark"
                                                        title="View Details"
                                                    >
                                                        <i className="fas fa-eye"></i>
                                                    </Link>

                                                    {txn.status ===
                                                        "pending" && (
                                                        <>
                                                            <button
                                                                className="btn btn-outline-success"
                                                                onClick={() =>
                                                                    handleAction(
                                                                        "approve",
                                                                        txn.id,
                                                                    )
                                                                }
                                                                title="Approve"
                                                            >
                                                                <i className="fas fa-check"></i>
                                                            </button>
                                                            <button
                                                                className="btn btn-outline-danger"
                                                                onClick={() =>
                                                                    handleAction(
                                                                        "reject",
                                                                        txn.id,
                                                                    )
                                                                }
                                                                title="Reject"
                                                            >
                                                                <i className="fas fa-times"></i>
                                                            </button>
                                                        </>
                                                    )}

                                                    {txn.status ===
                                                        "approved" &&
                                                        txn.type === "add" && (
                                                            <button
                                                                className="btn btn-outline-primary"
                                                                onClick={() =>
                                                                    handleAction(
                                                                        "deliver",
                                                                        txn.id,
                                                                    )
                                                                }
                                                                title="Mark as Delivered"
                                                            >
                                                                <i className="fas fa-truck"></i>
                                                            </button>
                                                        )}

                                                    {txn.status ===
                                                        "approved" &&
                                                        txn.type ===
                                                            "return" && (
                                                            <button
                                                                className="btn btn-outline-warning"
                                                                onClick={() =>
                                                                    handleAction(
                                                                        "return",
                                                                        txn.id,
                                                                    )
                                                                }
                                                                title="Mark as Returned"
                                                            >
                                                                <i className="fas fa-undo"></i>
                                                            </button>
                                                        )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* PAGINATION */}
                        {pagination && pagination.last_page > 1 && (
                            <div className="p-3 border-top">
                                <nav>
                                    <ul className="pagination mb-0">
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
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                </nav>
                            </div>
                        )}
                    </div>
                </div>

                {/* ACTION MODAL */}
                {actionModal.show && (
                    <div
                        className="modal show d-block"
                        style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
                    >
                        <div className="modal-dialog modal-dialog-centered">
                            <div className="modal-content border-0 shadow-lg">
                                <div className="modal-header border-0 pb-0">
                                    <h5 className="modal-title fw-bold">
                                        {actionModal.type === "approve" &&
                                            "Approve Transaction"}
                                        {actionModal.type === "reject" &&
                                            "Reject Transaction"}
                                        {actionModal.type === "deliver" &&
                                            "Mark as Delivered"}
                                        {actionModal.type === "return" &&
                                            "Mark as Returned"}
                                    </h5>
                                    <button
                                        type="button"
                                        className="btn-close"
                                        onClick={() =>
                                            setActionModal({
                                                show: false,
                                                type: "",
                                                transactionId: null,
                                            })
                                        }
                                    />
                                </div>
                                <div className="modal-body">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Admin Remark{" "}
                                            {actionModal.type === "reject" && (
                                                <span className="text-danger">
                                                    *
                                                </span>
                                            )}
                                        </label>
                                        <textarea
                                            className="form-control"
                                            rows="3"
                                            value={remark}
                                            onChange={(e) =>
                                                setRemark(e.target.value)
                                            }
                                            placeholder="Enter your remark..."
                                        />
                                    </div>

                                    {actionModal.type === "deliver" && (
                                        <div className="alert alert-info">
                                            <i className="fas fa-info-circle me-2"></i>
                                            This will ADD the quantity to
                                            store's current stock.
                                        </div>
                                    )}

                                    {actionModal.type === "return" && (
                                        <div className="alert alert-warning">
                                            <i className="fas fa-exclamation-triangle me-2"></i>
                                            This will SUBTRACT the quantity from
                                            store's current stock.
                                        </div>
                                    )}
                                </div>
                                <div className="modal-footer border-0">
                                    <button
                                        className="btn btn-light"
                                        onClick={() =>
                                            setActionModal({
                                                show: false,
                                                type: "",
                                                transactionId: null,
                                            })
                                        }
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        className={`btn ${
                                            actionModal.type === "reject"
                                                ? "btn-danger"
                                                : actionModal.type === "approve"
                                                  ? "btn-success"
                                                  : "btn-primary"
                                        }`}
                                        onClick={confirmAction}
                                        disabled={
                                            actionModal.type === "reject" &&
                                            !remark
                                        }
                                    >
                                        Confirm
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

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
