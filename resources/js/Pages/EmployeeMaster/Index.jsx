import MasterIndex from "../Masters/MasterIndex";
import { useState } from "react";
import axios from "axios";
import AlertModal from "../AlertModel";

export default function Index({
    auth,
    records,
    filters,
    companies,
    branches,
    departments,
    states,
}) {
    const [assignStoresModal, setAssignStoresModal] = useState({
        show: false,
        employeeId: null,
        employeeName: "",
    });
    const [selectedStores, setSelectedStores] = useState([]);
    const [availableStores, setAvailableStores] = useState([]);
    const [loadingStores, setLoadingStores] = useState(false);
    const [storesError, setStoresError] = useState(null);

    const [imageModal, setImageModal] = useState({
        show: false,
        url: null,
        title: "",
    });

    // New state for your custom alert modal
    const [alertModal, setAlertModal] = useState({
        show: false,
        type: "success", // "success" or "error"
        message: "",
    });

    const columns = [
        { key: "name", label: "Name", width: "180px" },
        { key: "user.email", label: "Email", width: "200px" },
        {
            key: "role_name",
            label: "Role",
            width: "150px",
            type: "badge",
            color: "dark",
        },
        { key: "designation", label: "Designation", width: "150px" },
        { key: "contact_number_1", label: "Contact", width: "130px" },
        { key: "manager_name", label: "Reporting", width: "150px" },
    ];

    const customActions = (row) => {
        const isSalesEmployee =
            row.role_name?.toLowerCase()?.includes("sales") || false;

        return (
            <>
                {row.employee_image && (
                    <button
                        className="btn btn-sm btn-dark text-white me-2"
                        onClick={() =>
                            setImageModal({
                                show: true,
                                url: `/storage/${row.employee_image}`,
                                title: "Employee Photo",
                            })
                        }
                        title="View Photo"
                    >
                        <i className="fas fa-image"></i>
                    </button>
                )}

                {row.aadhar_image && (
                    <button
                        className="btn btn-sm btn-dark text-white me-2"
                        onClick={() => {
                            const url = `/storage/${row.aadhar_image}`;
                            if (row.aadhar_image?.endsWith(".pdf")) {
                                window.open(url, "_blank");
                            } else {
                                setImageModal({
                                    show: true,
                                    url,
                                    title: "Aadhar Document",
                                });
                            }
                        }}
                        title="View Aadhar"
                    >
                        <i className="fas fa-id-card"></i>
                    </button>
                )}

                {isSalesEmployee && (
                    <button
                        className="btn btn-sm btn-outline-dark me-2"
                        onClick={() => openAssignStoresModal(row.id, row.name)}
                        disabled={loadingStores}
                        title="Assign Stores"
                    >
                        <i className="fas fa-store me-1"></i>
                        {row.stores_count || 0}
                        {loadingStores && (
                            <span
                                className="spinner-border spinner-border-sm ms-2"
                                role="status"
                            />
                        )}
                    </button>
                )}
            </>
        );
    };

    const openAssignStoresModal = async (employeeId, employeeName) => {
        setLoadingStores(true);
        setStoresError(null);
        setAvailableStores([]);
        setSelectedStores([]);

        try {
            const storesRes = await axios.get("/stores/all-active");
            const stores = Array.isArray(storesRes.data) ? storesRes.data : [];
            setAvailableStores(stores);

            const assignedRes = await axios.get(
                `/employee-masters/${employeeId}/stores`,
            );

            const assignedIds = Array.isArray(assignedRes.data)
                ? assignedRes.data
                      .map((a) => a?.store_id)
                      .filter((id) => id != null)
                : [];

            setSelectedStores(assignedIds);

            setAssignStoresModal({ show: true, employeeId, employeeName });
        } catch (err) {
            console.error("Failed to load stores:", err);
            const msg =
                err.response?.data?.message ||
                err.message ||
                "Could not load store list. Please check network or server.";
            setStoresError(msg);
        } finally {
            setLoadingStores(false);
        }
    };

    const handleAssignStores = async () => {
        if (selectedStores.length === 0) {
            setAlertModal({
                show: true,
                type: "error",
                message: "Please select at least one store",
            });
            return;
        }

        try {
            const response = await axios.post(
                `/employee-masters/${assignStoresModal.employeeId}/assign-stores`,
                { store_ids: selectedStores },
            );

            if (response.data?.success) {
                // Success → show your custom AlertModal
                setAlertModal({
                    show: true,
                    type: "success",
                    message:
                        response.data.message || "Stores assigned successfully",
                });

                setAssignStoresModal({
                    show: false,
                    employeeId: null,
                    employeeName: "",
                });
                setSelectedStores([]);
                window.location.reload(); // ← keep or optimize later
            }
        } catch (err) {
            console.error("Assign stores failed:", err);

            // Error → show your custom AlertModal
            setAlertModal({
                show: true,
                type: "error",
                message:
                    err.response?.data?.message ||
                    err.message ||
                    "Failed to save store assignment. Please try again.",
            });
        }
    };

    return (
        <>
            <MasterIndex
                auth={auth}
                masterName="Employee Master"
                viewBase="/employee-masters"
                columns={columns}
                data={records}
                filters={filters}
                excelTemplateRoute="employee-master.download-template"
                excelImportRoute="/employee-masters/upload"
                hasToggle={true}
                hasCompanyFilter={true}
                hasBranchFilter={true}
                hasDepartmentFilter={true}
                hasStateFilter={true}
                hasCityFilter={true}
                hasAreaFilter={true}
                companies={companies}
                branches={branches}
                departments={departments}
                states={states}
                title="Employee Management"
                customActions={customActions}
            />

            {/* Assign Stores Modal */}
            {assignStoresModal.show && (
                <div
                    className="modal show d-block"
                    tabIndex="-1"
                    style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
                >
                    <div className="modal-dialog modal-lg modal-dialog-centered">
                        <div className="modal-content border-0 shadow-lg">
                            <div className="modal-header border-0">
                                <h5 className="modal-title fw-bold">
                                    Assign Stores to{" "}
                                    {assignStoresModal.employeeName}
                                </h5>
                                <button
                                    type="button"
                                    className="btn-close"
                                    onClick={() =>
                                        setAssignStoresModal({
                                            show: false,
                                            employeeId: null,
                                            employeeName: "",
                                        })
                                    }
                                />
                            </div>

                            <div className="modal-body">
                                {loadingStores ? (
                                    <div className="text-center py-5">
                                        <div
                                            className="spinner-border text-primary"
                                            role="status"
                                        >
                                            <span className="visually-hidden">
                                                Loading...
                                            </span>
                                        </div>
                                        <p className="mt-3">
                                            Loading stores...
                                        </p>
                                    </div>
                                ) : storesError ? (
                                    <div className="alert alert-danger">
                                        {storesError}
                                    </div>
                                ) : availableStores.length === 0 ? (
                                    <div className="alert alert-info">
                                        No stores found in the system.
                                    </div>
                                ) : (
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Select Stores (
                                            {selectedStores.length} selected)
                                        </label>

                                        <input
                                            type="text"
                                            className="form-control mb-3"
                                            placeholder="Search stores by name, city, state..."
                                            onChange={(e) => {
                                                const term = e.target.value
                                                    .toLowerCase()
                                                    .trim();
                                                document
                                                    .querySelectorAll(
                                                        ".store-list-item",
                                                    )
                                                    .forEach((item) => {
                                                        const text =
                                                            item.textContent.toLowerCase();
                                                        item.style.display =
                                                            text.includes(term)
                                                                ? ""
                                                                : "none";
                                                    });
                                            }}
                                        />

                                        <div
                                            className="list-group shadow-sm"
                                            style={{
                                                maxHeight: "420px",
                                                overflowY: "auto",
                                            }}
                                        >
                                            {availableStores.map((store) => (
                                                <label
                                                    key={store.id}
                                                    className="list-group-item list-group-item-action store-list-item"
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                >
                                                    <div className="d-flex align-items-center">
                                                        <input
                                                            type="checkbox"
                                                            className="form-check-input me-3"
                                                            checked={selectedStores.includes(
                                                                store.id,
                                                            )}
                                                            onChange={(e) => {
                                                                if (
                                                                    e.target
                                                                        .checked
                                                                ) {
                                                                    setSelectedStores(
                                                                        (
                                                                            prev,
                                                                        ) => [
                                                                            ...prev,
                                                                            store.id,
                                                                        ],
                                                                    );
                                                                } else {
                                                                    setSelectedStores(
                                                                        (
                                                                            prev,
                                                                        ) =>
                                                                            prev.filter(
                                                                                (
                                                                                    id,
                                                                                ) =>
                                                                                    id !==
                                                                                    store.id,
                                                                            ),
                                                                    );
                                                                }
                                                            }}
                                                        />
                                                        <div className="flex-grow-1">
                                                            <div className="fw-semibold">
                                                                {store.name ||
                                                                    "—"}
                                                            </div>
                                                            <small className="text-muted">
                                                                {store.city
                                                                    ?.name ||
                                                                    "N/A"}
                                                                ,{" "}
                                                                {store.state
                                                                    ?.name ||
                                                                    "N/A"}
                                                                {store.area
                                                                    ?.name &&
                                                                    ` - ${store.area.name}`}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="modal-footer border-0">
                                <button
                                    className="btn btn-light"
                                    onClick={() =>
                                        setAssignStoresModal({
                                            show: false,
                                            employeeId: null,
                                            employeeName: "",
                                        })
                                    }
                                >
                                    Cancel
                                </button>
                                <button
                                    className="btn btn-dark text-white"
                                    onClick={handleAssignStores}
                                    disabled={
                                        loadingStores ||
                                        selectedStores.length === 0
                                    }
                                >
                                    <i className="fas fa-save me-2"></i>
                                    Save ({selectedStores.length})
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
                >
                    <div className="modal-dialog modal-lg modal-dialog-centered">
                        <div className="modal-content border-0 shadow-lg">
                            <div className="modal-header border-0">
                                <h5 className="modal-title fw-bold">
                                    {imageModal.title}
                                </h5>
                                <button
                                    type="button"
                                    className="btn-close"
                                    onClick={() =>
                                        setImageModal({
                                            show: false,
                                            url: null,
                                            title: "",
                                        })
                                    }
                                />
                            </div>
                            <div className="modal-body text-center">
                                <img
                                    src={imageModal.url}
                                    alt={imageModal.title}
                                    style={{ maxWidth: "100%", height: "auto" }}
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

            {/* Your custom Alert Modal – used for success & error messages */}
            <AlertModal
                show={alertModal.show}
                type={alertModal.type}
                message={alertModal.message}
                onClose={() =>
                    setAlertModal({
                        show: false,
                        type: "success",
                        message: "",
                    })
                }
            />
        </>
    );
}
