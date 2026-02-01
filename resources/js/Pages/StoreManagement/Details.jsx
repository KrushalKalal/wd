import React, { useState } from "react";
import { router, usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";
import axios from "axios";
import AlertModal from "../AlertModel";

export default function Details({ auth, store, visits }) {
    const { flash } = usePage().props;
    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [activeVisitId, setActiveVisitId] = useState(visits[0]?.id || null);

    const [reviewModal, setReviewModal] = useState({
        show: false,
        answerId: null,
        currentStatus: null,
    });
    const [reviewStatus, setReviewStatus] = useState("");
    const [reviewRemark, setReviewRemark] = useState("");

    const [stockModal, setStockModal] = useState({
        show: false,
        action: "",
        transactionId: null,
    });
    const [stockRemark, setStockRemark] = useState("");

    const [imageModal, setImageModal] = useState({
        show: false,
        url: null,
        title: "",
    });

    const activeVisit = visits.find((v) => v.id === activeVisitId);

    const formatDuration = (minutes) => {
        if (!minutes) return "—";
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
    };

    const getStatusBadge = (status, type = "survey") => {
        const badges = {
            survey: {
                pending: "bg-warning text-dark",
                approved: "bg-success text-white",
                rejected: "bg-danger text-white",
                needs_review: "bg-info text-white",
            },
            stock: {
                pending: "bg-warning text-dark",
                approved: "bg-info text-white",
                delivered: "bg-success text-white",
                returned: "bg-danger text-white",
                rejected: "bg-secondary text-white",
            },
        };
        return (
            <span className={`badge ${badges[type][status] || "bg-secondary"}`}>
                {status ? status.toUpperCase().replace("_", " ") : "PENDING"}
            </span>
        );
    };

    const handleBulkApproveVisit = async () => {
        if (!activeVisitId) return;

        if (
            !confirm(
                "Approve ALL pending surveys and stock transactions for this visit?",
            )
        ) {
            return;
        }

        try {
            const response = await axios.post(
                `/store-management/visits/${activeVisitId}/bulk-approve`,
                { admin_remark: "Bulk approved" },
            );

            if (response.data.success) {
                setAlert({
                    show: true,
                    type: "success",
                    message: response.data.message,
                });
                router.reload();
            }
        } catch (error) {
            setAlert({
                show: true,
                type: "error",
                message:
                    error.response?.data?.message || "Bulk approval failed",
            });
        }
    };

    const handleReviewSurvey = (answerId, currentStatus) => {
        setReviewModal({ show: true, answerId, currentStatus });
        setReviewStatus(currentStatus || "pending");
        setReviewRemark("");
    };

    const submitSurveyReview = async () => {
        try {
            const response = await axios.post(
                `/store-management/surveys/${reviewModal.answerId}/review`,
                {
                    admin_status: reviewStatus,
                    admin_remark: reviewRemark,
                },
            );

            if (response.data.success) {
                setAlert({
                    show: true,
                    type: "success",
                    message: response.data.message,
                });
                setReviewModal({
                    show: false,
                    answerId: null,
                    currentStatus: null,
                });
                router.reload();
            }
        } catch (error) {
            setAlert({
                show: true,
                type: "error",
                message: error.response?.data?.message || "Review failed",
            });
        }
    };

    const handleStockAction = (action, transactionId) => {
        setStockModal({ show: true, action, transactionId });
        setStockRemark("");
    };

    const confirmStockAction = async () => {
        try {
            const { action, transactionId } = stockModal;
            let endpoint = "";

            switch (action) {
                case "approve":
                    endpoint = `/store-management/stock/${transactionId}/approve`;
                    break;
                case "reject":
                    endpoint = `/store-management/stock/${transactionId}/reject`;
                    break;
                case "deliver":
                    endpoint = `/store-management/stock/${transactionId}/deliver`;
                    break;
                case "return":
                    endpoint = `/store-management/stock/${transactionId}/return`;
                    break;
            }

            const response = await axios.post(endpoint, {
                admin_remark: stockRemark,
            });

            if (response.data.success) {
                setAlert({
                    show: true,
                    type: "success",
                    message: response.data.message,
                });
                setStockModal({ show: false, action: "", transactionId: null });
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

    const pendingSurveys =
        activeVisit?.question_answers?.filter(
            (a) =>
                a.admin_status === "pending" ||
                a.admin_status === "needs_review",
        ) || [];

    const pendingStock =
        activeVisit?.stock_transactions?.filter(
            (t) => t.status === "pending",
        ) || [];

    return (
        <MainLayout user={auth.user} title="Store Management Details">
            <div className="container-fluid py-4">
                {/* HEADER */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 className="mb-1 fw-bold text-dark">{store.name}</h2>
                        <p className="text-muted mb-0">
                            {store.city?.name}, {store.state?.name}
                        </p>
                    </div>
                    <div className="d-flex gap-2">
                        {(pendingSurveys.length > 0 ||
                            pendingStock.length > 0) && (
                            <button
                                className="btn btn-success"
                                onClick={handleBulkApproveVisit}
                            >
                                <i className="fas fa-check-double me-2"></i>
                                Bulk Approve (
                                {pendingSurveys.length + pendingStock.length})
                            </button>
                        )}
                        <button
                            className="btn btn-dark text-white"
                            onClick={() => router.visit("/store-management")}
                        >
                            <i className="fas fa-arrow-left me-2"></i>
                            Back
                        </button>
                    </div>
                </div>

                {/* STORE INFO CARD */}
                <div className="card border-0 shadow-sm mb-4">
                    <div className="card-header bg-dark text-white border-0">
                        <h5 className="mb-0 text-white">
                            <i className="fas fa-info-circle me-2"></i>
                            Store Information
                        </h5>
                    </div>
                    <div className="card-body">
                        <div className="row">
                            <div className="col-md-6">
                                <table className="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Address:
                                            </td>
                                            <td className="text-dark">
                                                {store.address || "—"}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Location:
                                            </td>
                                            <td className="text-dark">
                                                {store.area?.name},{" "}
                                                {store.city?.name},{" "}
                                                {store.state?.name}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Contact:
                                            </td>
                                            <td className="text-dark">
                                                {store.contact_number_1 || "—"}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div className="col-md-6">
                                <table className="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Email:
                                            </td>
                                            <td className="text-dark">
                                                {store.email || "—"}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Categories:
                                            </td>
                                            <td className="text-dark">
                                                {[
                                                    store.category_one?.name,
                                                    store.category_two?.name,
                                                    store.category_three?.name,
                                                ]
                                                    .filter(Boolean)
                                                    .join(", ") || "—"}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {/* VISITS NAVIGATION */}
                {visits.length > 0 && (
                    <div className="card border-0 shadow-sm mb-4">
                        <div className="card-header bg-light border-0">
                            <h5 className="mb-0 text-dark">
                                <i className="fas fa-calendar-alt me-2"></i>
                                Visit History ({visits.length})
                            </h5>
                        </div>
                        <div className="card-body p-0">
                            <div
                                className="btn-group-vertical w-100"
                                role="group"
                            >
                                {visits.map((visit) => (
                                    <button
                                        key={visit.id}
                                        type="button"
                                        className={`btn text-start ${
                                            activeVisitId === visit.id
                                                ? "btn-dark text-white"
                                                : "btn-outline-dark"
                                        }`}
                                        onClick={() =>
                                            setActiveVisitId(visit.id)
                                        }
                                    >
                                        <div className="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>
                                                    {new Date(
                                                        visit.visit_date,
                                                    ).toLocaleDateString()}
                                                </strong>
                                                <span className="mx-2">•</span>
                                                <span>
                                                    {visit.employee.name}
                                                </span>
                                            </div>
                                            <div>
                                                <span
                                                    className={`badge me-2 ${visit.status === "completed" ? "bg-success" : "bg-warning text-dark"}`}
                                                >
                                                    {visit.status.toUpperCase()}
                                                </span>
                                                <span className="badge bg-secondary me-2">
                                                    {visit.question_answers
                                                        ?.length || 0}{" "}
                                                    Surveys
                                                </span>
                                                <span className="badge bg-dark">
                                                    {visit.stock_transactions
                                                        ?.length || 0}{" "}
                                                    Stock
                                                </span>
                                            </div>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* ACTIVE VISIT DETAILS */}
                {activeVisit && (
                    <>
                        {/* VISIT INFO */}
                        <div className="row g-4 mb-4">
                            <div className="col-md-6">
                                <div className="card border-0 shadow-sm h-100">
                                    <div className="card-header bg-dark text-white border-0">
                                        <h5 className="mb-0 text-white">
                                            <i className="fas fa-user-clock me-2"></i>
                                            Visit Details
                                        </h5>
                                    </div>
                                    <div className="card-body">
                                        <table className="table table-borderless mb-0">
                                            <tbody>
                                                <tr>
                                                    <td className="fw-semibold text-dark">
                                                        Employee:
                                                    </td>
                                                    <td className="text-dark">
                                                        {
                                                            activeVisit.employee
                                                                .name
                                                        }
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td className="fw-semibold text-dark">
                                                        Check-In:
                                                    </td>
                                                    <td className="text-dark">
                                                        {
                                                            activeVisit.check_in_time
                                                        }
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td className="fw-semibold text-dark">
                                                        Check-Out:
                                                    </td>
                                                    <td className="text-dark">
                                                        {activeVisit.check_out_time ||
                                                            "—"}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td className="fw-semibold text-dark">
                                                        Duration:
                                                    </td>
                                                    <td className="text-dark">
                                                        {formatDuration(
                                                            activeVisit.duration_minutes,
                                                        )}
                                                    </td>
                                                </tr>
                                                {activeVisit.visit_summary && (
                                                    <tr>
                                                        <td className="fw-semibold text-dark">
                                                            Summary:
                                                        </td>
                                                        <td className="text-dark">
                                                            {
                                                                activeVisit.visit_summary
                                                            }
                                                        </td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div className="col-md-6">
                                <div className="card border-0 shadow-sm h-100">
                                    <div className="card-header bg-dark text-white border-0">
                                        <h5 className="mb-0 text-white">
                                            <i className="fas fa-chart-pie me-2"></i>
                                            Summary
                                        </h5>
                                    </div>
                                    <div className="card-body">
                                        <div className="mb-3">
                                            <h6 className="text-dark fw-semibold mb-2">
                                                Survey Answers:{" "}
                                                {activeVisit.question_answers
                                                    ?.length || 0}
                                            </h6>
                                            <div className="d-flex gap-2 flex-wrap">
                                                {[
                                                    "pending",
                                                    "approved",
                                                    "rejected",
                                                    "needs_review",
                                                ].map((status) => {
                                                    const count =
                                                        activeVisit.question_answers?.filter(
                                                            (a) =>
                                                                a.admin_status ===
                                                                status,
                                                        ).length || 0;
                                                    return count > 0 ? (
                                                        <span
                                                            key={status}
                                                            className={`badge ${getStatusBadge(status, "survey").props.className}`}
                                                        >
                                                            {status.toUpperCase()}
                                                            : {count}
                                                        </span>
                                                    ) : null;
                                                })}
                                            </div>
                                        </div>
                                        <div>
                                            <h6 className="text-dark fw-semibold mb-2">
                                                Stock Transactions:{" "}
                                                {activeVisit.stock_transactions
                                                    ?.length || 0}
                                            </h6>
                                            <div className="d-flex gap-2 flex-wrap">
                                                {[
                                                    "pending",
                                                    "approved",
                                                    "delivered",
                                                    "returned",
                                                    "rejected",
                                                ].map((status) => {
                                                    const count =
                                                        activeVisit.stock_transactions?.filter(
                                                            (t) =>
                                                                t.status ===
                                                                status,
                                                        ).length || 0;
                                                    return count > 0 ? (
                                                        <span
                                                            key={status}
                                                            className={`badge ${getStatusBadge(status, "stock").props.className}`}
                                                        >
                                                            {status.toUpperCase()}
                                                            : {count}
                                                        </span>
                                                    ) : null;
                                                })}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* SURVEY ANSWERS */}
                        <div className="card border-0 shadow-sm mb-4">
                            <div className="card-header bg-dark text-white border-0">
                                <h5 className="mb-0 text-white">
                                    <i className="fas fa-clipboard-list me-2"></i>
                                    Survey Answers (
                                    {activeVisit.question_answers?.length || 0})
                                </h5>
                            </div>
                            <div className="card-body">
                                {activeVisit.question_answers &&
                                activeVisit.question_answers.length > 0 ? (
                                    <div className="row g-4">
                                        {activeVisit.question_answers.map(
                                            (answer) => (
                                                <div
                                                    key={answer.id}
                                                    className="col-md-6"
                                                >
                                                    <div className="card border shadow-sm h-100">
                                                        <div className="card-body">
                                                            <div className="d-flex justify-content-between align-items-start mb-3">
                                                                <h6 className="mb-0 text-dark fw-semibold flex-grow-1">
                                                                    {
                                                                        answer
                                                                            .question
                                                                            .question_text
                                                                    }
                                                                </h6>
                                                                {getStatusBadge(
                                                                    answer.admin_status,
                                                                    "survey",
                                                                )}
                                                            </div>

                                                            {answer.answer_text && (
                                                                <div className="mb-3">
                                                                    <div className="bg-light p-3 rounded">
                                                                        <strong className="text-dark d-block mb-1">
                                                                            Answer:
                                                                        </strong>
                                                                        <p className="mb-0 text-dark">
                                                                            {
                                                                                answer.answer_text
                                                                            }
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            )}

                                                            {answer.answer_image && (
                                                                <div className="mb-3">
                                                                    <button
                                                                        className="btn btn-sm btn-outline-dark"
                                                                        onClick={() =>
                                                                            setImageModal(
                                                                                {
                                                                                    show: true,
                                                                                    url: `/storage/${answer.answer_image}`,
                                                                                    title: answer
                                                                                        .question
                                                                                        .question_text,
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        <i className="fas fa-image me-2"></i>
                                                                        View
                                                                        Image
                                                                    </button>
                                                                </div>
                                                            )}

                                                            {answer.admin_remark && (
                                                                <div className="alert alert-secondary mb-3">
                                                                    <small>
                                                                        <strong className="text-dark">
                                                                            Admin
                                                                            Remark:
                                                                        </strong>{" "}
                                                                        <span className="text-dark">
                                                                            {
                                                                                answer.admin_remark
                                                                            }
                                                                        </span>
                                                                    </small>
                                                                </div>
                                                            )}

                                                            <button
                                                                className="btn btn-sm btn-dark text-white w-100"
                                                                onClick={() =>
                                                                    handleReviewSurvey(
                                                                        answer.id,
                                                                        answer.admin_status,
                                                                    )
                                                                }
                                                            >
                                                                <i className="fas fa-check-circle me-2"></i>
                                                                Review
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <div className="text-center py-5">
                                        <i className="fas fa-clipboard fa-3x text-muted mb-3"></i>
                                        <p className="text-muted mb-0">
                                            No survey answers submitted
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* STOCK TRANSACTIONS */}
                        <div className="card border-0 shadow-sm">
                            <div className="card-header bg-dark text-white border-0">
                                <h5 className="mb-0 text-white">
                                    <i className="fas fa-boxes me-2"></i>
                                    Stock Transactions (
                                    {activeVisit.stock_transactions?.length ||
                                        0}
                                    )
                                </h5>
                            </div>
                            <div className="card-body">
                                {activeVisit.stock_transactions &&
                                activeVisit.stock_transactions.length > 0 ? (
                                    <div className="table-responsive">
                                        <table className="table table-hover mb-0">
                                            <thead className="table-light">
                                                <tr>
                                                    <th className="text-dark">
                                                        Product
                                                    </th>
                                                    <th className="text-dark">
                                                        Type
                                                    </th>
                                                    <th className="text-dark">
                                                        Quantity
                                                    </th>
                                                    <th className="text-dark">
                                                        Status
                                                    </th>
                                                    <th className="text-dark">
                                                        Remark
                                                    </th>
                                                    <th className="text-dark">
                                                        Actions
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {activeVisit.stock_transactions.map(
                                                    (txn) => (
                                                        <tr key={txn.id}>
                                                            <td>
                                                                <div className="fw-semibold text-dark">
                                                                    {
                                                                        txn
                                                                            .product
                                                                            ?.name
                                                                    }
                                                                </div>
                                                                <small className="text-muted">
                                                                    MRP: ₹
                                                                    {
                                                                        txn
                                                                            .product
                                                                            ?.mrp
                                                                    }
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <span
                                                                    className={`badge ${txn.type === "add" ? "bg-success" : "bg-danger"}`}
                                                                >
                                                                    <i
                                                                        className={`fas fa-${txn.type === "add" ? "plus" : "minus"} me-1`}
                                                                    ></i>
                                                                    {txn.type.toUpperCase()}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span className="badge bg-dark">
                                                                    {
                                                                        txn.quantity
                                                                    }
                                                                </span>
                                                            </td>
                                                            <td>
                                                                {getStatusBadge(
                                                                    txn.status,
                                                                    "stock",
                                                                )}
                                                            </td>
                                                            <td className="text-dark small">
                                                                {txn.remark ||
                                                                    "—"}
                                                                {txn.admin_remark && (
                                                                    <div className="text-muted">
                                                                        Admin:{" "}
                                                                        {
                                                                            txn.admin_remark
                                                                        }
                                                                    </div>
                                                                )}
                                                            </td>
                                                            <td>
                                                                <div className="btn-group btn-group-sm">
                                                                    {txn.status ===
                                                                        "pending" && (
                                                                        <>
                                                                            <button
                                                                                className="btn btn-success"
                                                                                onClick={() =>
                                                                                    handleStockAction(
                                                                                        "approve",
                                                                                        txn.id,
                                                                                    )
                                                                                }
                                                                                title="Approve"
                                                                            >
                                                                                <i className="fas fa-check"></i>
                                                                            </button>
                                                                            <button
                                                                                className="btn btn-danger"
                                                                                onClick={() =>
                                                                                    handleStockAction(
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
                                                                        txn.type ===
                                                                            "add" && (
                                                                            <button
                                                                                className="btn btn-primary"
                                                                                onClick={() =>
                                                                                    handleStockAction(
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
                                                                                className="btn btn-warning"
                                                                                onClick={() =>
                                                                                    handleStockAction(
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
                                                    ),
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="text-center py-5">
                                        <i className="fas fa-box fa-3x text-muted mb-3"></i>
                                        <p className="text-muted mb-0">
                                            No stock transactions
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </>
                )}

                {visits.length === 0 && (
                    <div className="card border-0 shadow-sm">
                        <div className="card-body text-center py-5">
                            <i className="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 className="text-dark">
                                No visits recorded yet
                            </h5>
                            <p className="text-muted">
                                This store hasn't been visited by any employee.
                            </p>
                        </div>
                    </div>
                )}

                {/* MODALS */}
                {/* Review Survey Modal */}
                {reviewModal.show && (
                    <div
                        className="modal show d-block"
                        style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
                    >
                        <div className="modal-dialog modal-dialog-centered">
                            <div className="modal-content border-0 shadow-lg">
                                <div className="modal-header bg-dark text-white border-0">
                                    <h5 className="modal-title text-white">
                                        Review Survey Answer
                                    </h5>
                                    <button
                                        type="button"
                                        className="btn-close btn-close-white"
                                        onClick={() =>
                                            setReviewModal({
                                                show: false,
                                                answerId: null,
                                                currentStatus: null,
                                            })
                                        }
                                    />
                                </div>
                                <div className="modal-body">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold text-dark">
                                            Status{" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                        </label>
                                        <select
                                            className="form-select"
                                            value={reviewStatus}
                                            onChange={(e) =>
                                                setReviewStatus(e.target.value)
                                            }
                                        >
                                            <option value="pending">
                                                Pending
                                            </option>
                                            <option value="approved">
                                                Approved
                                            </option>
                                            <option value="rejected">
                                                Rejected
                                            </option>
                                            <option value="needs_review">
                                                Needs Review
                                            </option>
                                        </select>
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold text-dark">
                                            Admin Remark
                                        </label>
                                        <textarea
                                            className="form-control"
                                            rows="3"
                                            value={reviewRemark}
                                            onChange={(e) =>
                                                setReviewRemark(e.target.value)
                                            }
                                            placeholder="Enter your remarks..."
                                        />
                                    </div>
                                </div>
                                <div className="modal-footer bg-light border-0">
                                    <button
                                        className="btn btn-light border"
                                        onClick={() =>
                                            setReviewModal({
                                                show: false,
                                                answerId: null,
                                                currentStatus: null,
                                            })
                                        }
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        className="btn btn-dark text-white"
                                        onClick={submitSurveyReview}
                                    >
                                        <i className="fas fa-save me-2"></i>
                                        Save Review
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Stock Action Modal */}
                {stockModal.show && (
                    <div
                        className="modal show d-block"
                        style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
                    >
                        <div className="modal-dialog modal-dialog-centered">
                            <div className="modal-content border-0 shadow-lg">
                                <div className="modal-header bg-dark text-white border-0">
                                    <h5 className="modal-title text-white fw-bold">
                                        {stockModal.action === "approve" &&
                                            "Approve Transaction"}
                                        {stockModal.action === "reject" &&
                                            "Reject Transaction"}
                                        {stockModal.action === "deliver" &&
                                            "Mark as Delivered"}
                                        {stockModal.action === "return" &&
                                            "Mark as Returned"}
                                    </h5>
                                    <button
                                        type="button"
                                        className="btn-close btn-close-white"
                                        onClick={() =>
                                            setStockModal({
                                                show: false,
                                                action: "",
                                                transactionId: null,
                                            })
                                        }
                                    />
                                </div>
                                <div className="modal-body">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold text-dark">
                                            Admin Remark{" "}
                                            {stockModal.action === "reject" && (
                                                <span className="text-danger">
                                                    *
                                                </span>
                                            )}
                                        </label>
                                        <textarea
                                            className="form-control"
                                            rows="3"
                                            value={stockRemark}
                                            onChange={(e) =>
                                                setStockRemark(e.target.value)
                                            }
                                            placeholder="Enter your remark..."
                                        />
                                    </div>
                                    {stockModal.action === "deliver" && (
                                        <div className="alert alert-info border">
                                            <i className="fas fa-info-circle me-2"></i>
                                            This will ADD the quantity to
                                            store's current stock.
                                        </div>
                                    )}
                                    {stockModal.action === "return" && (
                                        <div className="alert alert-warning border">
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
                                            setStockModal({
                                                show: false,
                                                action: "",
                                                transactionId: null,
                                            })
                                        }
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        className="btn btn-dark text-white"
                                        onClick={confirmStockAction}
                                        disabled={
                                            stockModal.action === "reject" &&
                                            !stockRemark
                                        }
                                    >
                                        Confirm
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Image Modal */}
                {imageModal.show && (
                    <div
                        className="modal show d-block"
                        style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
                        onClick={() =>
                            setImageModal({ show: false, url: null, title: "" })
                        }
                    >
                        <div
                            className="modal-dialog modal-lg modal-dialog-centered"
                            onClick={(e) => e.stopPropagation()}
                        >
                            <div className="modal-content border-0 shadow-lg">
                                <div className="modal-header bg-dark text-white border-0">
                                    <h5 className="modal-title fw-bold text-white">
                                        {imageModal.title}
                                    </h5>
                                    <button
                                        type="button"
                                        className="btn-close btn-close-white"
                                        onClick={() =>
                                            setImageModal({
                                                show: false,
                                                url: null,
                                                title: "",
                                            })
                                        }
                                    />
                                </div>
                                <div className="modal-body text-center p-0">
                                    <img
                                        src={imageModal.url}
                                        alt={imageModal.title}
                                        style={{
                                            maxWidth: "100%",
                                            height: "auto",
                                            maxHeight: "70vh",
                                        }}
                                        onError={(e) => {
                                            e.target.src =
                                                "/images/placeholder-image.png";
                                        }}
                                    />
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
