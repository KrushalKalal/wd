import React, { useState } from "react";
import { router, usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";
import axios from "axios";
import AlertModal from "../AlertModel";

export default function Details({ auth, visit }) {
    const { flash } = usePage().props;
    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [reviewModal, setReviewModal] = useState({
        show: false,
        answerId: null,
        currentStatus: null,
    });
    const [reviewStatus, setReviewStatus] = useState("");
    const [reviewRemark, setReviewRemark] = useState("");
    const [imageModal, setImageModal] = useState({
        show: false,
        url: null,
        title: "",
    });
    console.log(visit);

    const formatDuration = (minutes) => {
        if (!minutes) return "—";
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
    };

    const handleReview = (answerId, currentStatus) => {
        setReviewModal({ show: true, answerId, currentStatus });
        setReviewStatus(currentStatus || "pending");
        setReviewRemark("");
    };

    const submitReview = async () => {
        if (!reviewStatus) {
            setAlert({
                show: true,
                type: "error",
                message: "Please select a status",
            });
            return;
        }

        try {
            const response = await axios.post(
                `/store-visits/survey/${reviewModal.answerId}/review`,
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

    const getStatusBadge = (status) => {
        const badges = {
            pending: "bg-warning text-dark",
            approved: "bg-success text-white",
            rejected: "bg-danger text-white",
            needs_review: "bg-info text-white",
        };
        return (
            <span
                className={`badge ${badges[status] || "bg-secondary text-white"}`}
            >
                {status ? status.toUpperCase().replace("_", " ") : "PENDING"}
            </span>
        );
    };

    return (
        <MainLayout user={auth.user} title="Visit Details">
            <div className="container-fluid py-4">
                {/* HEADER */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 className="mb-1 fw-bold text-dark">
                            Visit Details
                        </h2>
                        <p className="text-muted mb-0">
                            Complete information about store visit
                        </p>
                    </div>
                    <button
                        className="btn btn-dark text-white"
                        onClick={() => router.visit("/store-visits")}
                    >
                        <i className="fas fa-arrow-left me-2"></i>
                        Back to List
                    </button>
                </div>

                <div className="row g-4">
                    {/* VISIT INFORMATION */}
                    <div className="col-md-6">
                        <div className="card border-0 shadow-sm h-100">
                            <div className="card-header bg-dark text-white border-0">
                                <h5 className="mb-0 text-white">
                                    <i className="fas fa-info-circle me-2"></i>
                                    Visit Information
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
                                                {visit.employee.name}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Designation:
                                            </td>
                                            <td className="text-dark">
                                                {visit.employee.designation ||
                                                    "—"}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Store:
                                            </td>
                                            <td className="text-dark">
                                                {visit.store.name}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Location:
                                            </td>
                                            <td className="text-dark">
                                                {visit.store.area?.name},{" "}
                                                {visit.store.city?.name},{" "}
                                                {visit.store.state?.name}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Visit Date:
                                            </td>
                                            <td className="text-dark">
                                                {new Date(
                                                    visit.visit_date,
                                                ).toLocaleDateString()}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Check-In:
                                            </td>
                                            <td className="text-dark">
                                                {visit.check_in_time}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Check-Out:
                                            </td>
                                            <td className="text-dark">
                                                {visit.check_out_time || "—"}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Duration:
                                            </td>
                                            <td className="text-dark">
                                                {formatDuration(
                                                    visit.duration_minutes,
                                                )}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="fw-semibold text-dark">
                                                Status:
                                            </td>
                                            <td>
                                                <span
                                                    className={`badge ${visit.status === "completed" ? "bg-success" : "bg-warning text-dark"}`}
                                                >
                                                    {visit.status.toUpperCase()}
                                                </span>
                                            </td>
                                        </tr>
                                        {visit.visit_summary && (
                                            <tr>
                                                <td className="fw-semibold text-dark">
                                                    Summary:
                                                </td>
                                                <td className="text-dark">
                                                    {visit.visit_summary}
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {/* STOCK TRANSACTIONS */}
                    <div className="col-md-6">
                        <div className="card border-0 shadow-sm h-100">
                            <div className="card-header bg-dark text-white border-0">
                                <h5 className="mb-0 text-white">
                                    <i className="fas fa-boxes me-2"></i>
                                    Stock Transactions (
                                    {visit.stock_transactions?.length || 0})
                                </h5>
                            </div>
                            <div className="card-body">
                                {visit.stock_transactions &&
                                visit.stock_transactions.length > 0 ? (
                                    <div className="table-responsive">
                                        <table className="table table-sm table-hover mb-0">
                                            <thead className="table-light">
                                                <tr>
                                                    <th className="text-dark">
                                                        Product
                                                    </th>
                                                    <th className="text-dark">
                                                        Type
                                                    </th>
                                                    <th className="text-dark">
                                                        Qty
                                                    </th>
                                                    <th className="text-dark">
                                                        Status
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {visit.stock_transactions.map(
                                                    (txn) => (
                                                        <tr key={txn.id}>
                                                            <td className="text-dark">
                                                                {
                                                                    txn.product
                                                                        ?.name
                                                                }
                                                            </td>
                                                            <td>
                                                                <span
                                                                    className={`badge ${txn.type === "add" ? "bg-success" : "bg-danger"}`}
                                                                >
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
                                                                <span className="badge bg-warning text-dark">
                                                                    {txn.status.toUpperCase()}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="text-center py-5">
                                        <i className="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p className="text-muted mb-0">
                                            No stock transactions
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* SURVEY ANSWERS */}
                    <div className="col-12">
                        <div className="card border-0 shadow-sm">
                            <div className="card-header bg-dark text-white border-0">
                                <h5 className="mb-0 text-white">
                                    <i className="fas fa-clipboard-list me-2"></i>
                                    Survey Answers (
                                    {visit.question_answers?.length || 0})
                                </h5>
                            </div>
                            <div className="card-body">
                                {visit.question_answers &&
                                visit.question_answers.length > 0 ? (
                                    <div className="row g-4">
                                        {visit.question_answers.map(
                                            (answer) => (
                                                <div
                                                    key={answer.id}
                                                    className="col-md-6"
                                                >
                                                    <div className="card border shadow-sm h-100">
                                                        <div className="card-body">
                                                            <div className="d-flex justify-content-between align-items-start mb-3">
                                                                <h6 className="mb-0 text-dark fw-semibold">
                                                                    {
                                                                        answer
                                                                            .question
                                                                            .question_text
                                                                    }
                                                                </h6>
                                                                {getStatusBadge(
                                                                    answer.admin_status,
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
                                                                        className="btn btn-sm btn-dark text-white"
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
                                                                    handleReview(
                                                                        answer.id,
                                                                        answer.admin_status,
                                                                    )
                                                                }
                                                            >
                                                                <i className="fas fa-check-circle me-2"></i>
                                                                Review Answer
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
                    </div>
                </div>

                {/* REVIEW MODAL */}
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
                                        onClick={submitReview}
                                    >
                                        <i className="fas fa-save me-2"></i>
                                        Save Review
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* IMAGE MODAL */}
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
