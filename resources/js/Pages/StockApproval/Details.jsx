import React, { useState } from "react";
import { router, usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";
import axios from "axios";
import AlertModal from "../AlertModel";

export default function Details({ auth, transaction }) {
    const { flash } = usePage().props;
    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [actionModal, setActionModal] = useState({
        show: false,
        type: "",
    });
    const [remark, setRemark] = useState("");

    const handleAction = (type) => {
        setActionModal({ show: true, type });
        setRemark(transaction.admin_remark || "");
    };

    const confirmAction = async () => {
        try {
            const { type } = actionModal;
            let endpoint = "";

            switch (type) {
                case "approve":
                    endpoint = `/stock-approvals/${transaction.id}/approve`;
                    break;
                case "reject":
                    endpoint = `/stock-approvals/${transaction.id}/reject`;
                    break;
                case "deliver":
                    endpoint = `/stock-approvals/${transaction.id}/deliver`;
                    break;
                case "return":
                    endpoint = `/stock-approvals/${transaction.id}/return`;
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
                setActionModal({ show: false, type: "" });
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
            pending: "bg-warning text-dark",
            approved: "bg-info text-white",
            delivered: "bg-success text-white",
            returned: "bg-danger text-white",
            rejected: "bg-secondary text-white",
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
        <MainLayout user={auth.user} title="Transaction Details">
            <div className="container-fluid py-4">
                {/* HEADER */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 className="mb-1 fw-bold text-dark">
                            Transaction Details
                        </h2>
                        <p className="text-muted mb-0">
                            Complete information about stock transaction
                        </p>
                    </div>
                    <button
                        className="btn btn-dark text-white"
                        onClick={() => router.visit("/stock-approvals")}
                    >
                        <i className="fas fa-arrow-left me-2"></i>
                        Back to List
                    </button>
                </div>

                <div className="row g-4">
                    {/* TRANSACTION INFO */}
                    <div className="col-md-6">
                        <div className="card border-0 shadow-sm h-100">
                            <div className="card-header bg-dark text-white border-0">
                                <h5 className="mb-0 text-white">
                                    <i className="fas fa-info-circle me-2"></i>
                                    Transaction Information
                                </h5>
                            </div>
                            <div className="card-body">
                                <table className="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Transaction ID:
                                            </td>
                                            <td className="text-dark">
                                                #{transaction.id}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Date:
                                            </td>
                                            <td className="text-dark">
                                                {new Date(
                                                    transaction.created_at,
                                                ).toLocaleString()}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Type:
                                            </td>
                                            <td>
                                                {getTypeBadge(transaction.type)}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Quantity:
                                            </td>
                                            <td>
                                                <span className="badge bg-dark fs-6">
                                                    {transaction.quantity}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Status:
                                            </td>
                                            <td>
                                                {getStatusBadge(
                                                    transaction.status,
                                                )}
                                            </td>
                                        </tr>
                                        {transaction.remark && (
                                            <tr>
                                                <td className="fw-semibold text-dark">
                                                    Remark:
                                                </td>
                                                <td className="text-dark">
                                                    {transaction.remark}
                                                </td>
                                            </tr>
                                        )}
                                        {transaction.admin_remark && (
                                            <tr>
                                                <td className="fw-semibold text-dark">
                                                    Admin Remark:
                                                </td>
                                                <td className="text-dark">
                                                    {transaction.admin_remark}
                                                </td>
                                            </tr>
                                        )}
                                        {transaction.approved_by && (
                                            <tr>
                                                <td className="fw-semibold text-dark">
                                                    Approved By:
                                                </td>
                                                <td className="text-dark">
                                                    {
                                                        transaction.approved_by
                                                            ?.name
                                                    }
                                                </td>
                                            </tr>
                                        )}
                                        {transaction.approved_at && (
                                            <tr>
                                                <td className="fw-semibold text-dark">
                                                    Approved At:
                                                </td>
                                                <td className="text-dark">
                                                    {new Date(
                                                        transaction.approved_at,
                                                    ).toLocaleString()}
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>

                                {/* ACTION BUTTONS */}
                                <div className="mt-4 d-flex gap-2">
                                    {transaction.status === "pending" && (
                                        <>
                                            <button
                                                className="btn btn-success flex-fill"
                                                onClick={() =>
                                                    handleAction("approve")
                                                }
                                            >
                                                <i className="fas fa-check me-2"></i>
                                                Approve
                                            </button>
                                            <button
                                                className="btn btn-danger flex-fill"
                                                onClick={() =>
                                                    handleAction("reject")
                                                }
                                            >
                                                <i className="fas fa-times me-2"></i>
                                                Reject
                                            </button>
                                        </>
                                    )}

                                    {transaction.status === "approved" &&
                                        transaction.type === "add" && (
                                            <button
                                                className="btn btn-primary w-100"
                                                onClick={() =>
                                                    handleAction("deliver")
                                                }
                                            >
                                                <i className="fas fa-truck me-2"></i>
                                                Mark as Delivered
                                            </button>
                                        )}

                                    {transaction.status === "approved" &&
                                        transaction.type === "return" && (
                                            <button
                                                className="btn btn-warning w-100"
                                                onClick={() =>
                                                    handleAction("return")
                                                }
                                            >
                                                <i className="fas fa-undo me-2"></i>
                                                Mark as Returned
                                            </button>
                                        )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* EMPLOYEE INFO */}
                    <div className="col-md-6">
                        <div className="card border-0 shadow-sm h-100">
                            <div className="card-header bg-dark text-white border-0">
                                <h5 className="mb-0 text-white">
                                    <i className="fas fa-user me-2"></i>
                                    Employee Information
                                </h5>
                            </div>
                            <div className="card-body">
                                <table className="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Name:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.employee.name}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Email:
                                            </td>
                                            <td className="text-dark">
                                                {
                                                    transaction.employee.user
                                                        .email
                                                }
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Designation:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.employee
                                                    .designation || "—"}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Contact:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.employee
                                                    .contact_number_1 || "—"}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {/* PRODUCT INFO */}
                    <div className="col-md-6">
                        <div className="card border-0 shadow-sm h-100">
                            <div className="card-header bg-dark text-white border-0">
                                <h5 className="mb-0 text-white">
                                    <i className="fas fa-box me-2"></i>
                                    Product Information
                                </h5>
                            </div>
                            <div className="card-body">
                                <table className="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Product:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.product.name}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                MRP:
                                            </td>
                                            <td className="text-dark">
                                                ₹{transaction.product.mrp}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Category 1:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.product
                                                    .category_one?.name || "—"}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Category 2:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.product
                                                    .category_two?.name || "—"}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Category 3:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.product
                                                    .category_three?.name ||
                                                    "—"}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {/* STORE INFO */}
                    <div className="col-md-6">
                        <div className="card border-0 shadow-sm h-100">
                            <div className="card-header bg-dark text-white border-0">
                                <h5 className="mb-0 text-white">
                                    <i className="fas fa-store me-2"></i>
                                    Store Information
                                </h5>
                            </div>
                            <div className="card-body">
                                <table className="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Store:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.store.name}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Location:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.store.area?.name},{" "}
                                                {transaction.store.city?.name},{" "}
                                                {transaction.store.state?.name}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Contact:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.store
                                                    .contact_number_1 || "—"}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Email:
                                            </td>
                                            <td className="text-dark">
                                                {transaction.store.email || "—"}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
                                <div className="modal-header bg-dark text-white border-0">
                                    <h5 className="modal-title text-white">
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
                                        className="btn-close btn-close-white"
                                        onClick={() =>
                                            setActionModal({
                                                show: false,
                                                type: "",
                                            })
                                        }
                                    />
                                </div>
                                <div className="modal-body">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold text-dark">
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
                                <div className="modal-footer bg-light border-0">
                                    <button
                                        className="btn btn-light border"
                                        onClick={() =>
                                            setActionModal({
                                                show: false,
                                                type: "",
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
