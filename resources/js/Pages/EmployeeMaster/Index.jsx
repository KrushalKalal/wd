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

    const [imageModal, setImageModal] = useState({
        show: false,
        url: null,
        title: "",
    });

    const [alertModal, setAlertModal] = useState({
        show: false,
        type: "success",
        message: "",
    });

    // Enhanced columns - move images/assign to table columns
    const columns = [
        { key: "name", label: "Name", width: "180px" },
        { key: "user.email", label: "Email", width: "200px" },
        {
            key: "role_name",
            label: "Role",
            width: "130px",
            type: "badge",
            color: "dark",
        },
        { key: "designation", label: "Designation", width: "140px" },
        { key: "contact_number_1", label: "Contact", width: "120px" },
        { key: "manager_name", label: "Reporting", width: "130px" },
        // NEW: Images column
        {
            key: "images",
            label: "Images",
            width: "100px",
            type: "custom",
        },
        // NEW: Stores column (for Sales only)
        {
            key: "stores_assigned",
            label: "Stores",
            width: "100px",
            type: "custom",
        },
    ];

    // Custom render for each row - handle images and stores columns
    const customRender = (row, column) => {
        // Images column
        if (column.key === "images") {
            return (
                <div className="d-flex gap-1">
                    {row.employee_image && (
                        <button
                            className="btn btn-sm btn-outline-dark"
                            onClick={(e) => {
                                e.stopPropagation();
                                setImageModal({
                                    show: true,
                                    url: `/storage/${row.employee_image}`,
                                    title: "Employee Photo",
                                });
                            }}
                            title="View Photo"
                        >
                            <i className="fas fa-image"></i>
                        </button>
                    )}
                    {row.aadhar_image && (
                        <button
                            className="btn btn-sm btn-outline-dark"
                            onClick={(e) => {
                                e.stopPropagation();
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
                </div>
            );
        }

        // Stores column (only for Sales employees)
        if (column.key === "stores_assigned") {
            const isSalesEmployee =
                row.role_name?.toLowerCase()?.includes("sales") || false;

            if (!isSalesEmployee) {
                return <span className="text-muted">—</span>;
            }

            return (
                <button
                    className="btn btn-sm btn-outline-dark"
                    onClick={(e) => {
                        e.stopPropagation();
                        openAssignStoresModal(row.id, row.name);
                    }}
                    disabled={loadingStores}
                    title="Assign Stores"
                >
                    <i className="fas fa-store me-1"></i>
                    {row.stores_count || 0}
                </button>
            );
        }

        return null;
    };

    const openAssignStoresModal = async (employeeId, employeeName) => {
        setLoadingStores(true);
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
            setAlertModal({
                show: true,
                type: "error",
                message: "Failed to load stores. Please try again.",
            });
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

                // Reload to update counts
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } catch (err) {
            console.error("Assign stores failed:", err);
            setAlertModal({
                show: true,
                type: "error",
                message: "Failed to save store assignment. Please try again.",
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
                customRender={customRender}
            />

            {/* BLACK & WHITE THEME - Assign Stores Modal */}
            {assignStoresModal.show && (
                <div
                    className="modal show d-block"
                    tabIndex="-1"
                    style={{
                        backgroundColor: "rgba(0,0,0,0.5)",
                        zIndex: 1050,
                    }}
                    onClick={() =>
                        setAssignStoresModal({
                            show: false,
                            employeeId: null,
                            employeeName: "",
                        })
                    }
                >
                    <div
                        className="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div
                            className="modal-content border-0 shadow-lg"
                            style={{
                                borderRadius: 0,
                                border: "none",
                                background: "transparent",
                            }}
                        >
                            {/* BLACK HEADER */}
                            <div className="modal-header bg-dark text-white border-0">
                                <div>
                                    <h5 className="modal-title fw-bold mb-0 text-white">
                                        Assign Stores
                                    </h5>
                                    <small className="opacity-75">
                                        {assignStoresModal.employeeName}
                                    </small>
                                </div>
                                <button
                                    type="button"
                                    className="btn-close btn-close-white"
                                    onClick={() =>
                                        setAssignStoresModal({
                                            show: false,
                                            employeeId: null,
                                            employeeName: "",
                                        })
                                    }
                                />
                            </div>

                            <div className="modal-body p-0">
                                {loadingStores ? (
                                    <div className="text-center py-5">
                                        <div
                                            className="spinner-border text-dark mb-3"
                                            role="status"
                                        >
                                            <span className="visually-hidden">
                                                Loading...
                                            </span>
                                        </div>
                                        <p className="text-muted">
                                            Loading stores...
                                        </p>
                                    </div>
                                ) : availableStores.length === 0 ? (
                                    <div className="p-4">
                                        <div className="alert alert-secondary mb-0">
                                            <i className="fas fa-info-circle me-2"></i>
                                            No stores found in the system.
                                        </div>
                                    </div>
                                ) : (
                                    <>
                                        {/* Search and Counter - WHITE BACKGROUND */}
                                        <div className="p-3 bg-white border-bottom">
                                            <div className="row g-2 align-items-center">
                                                <div className="col-md-8">
                                                    <div className="input-group">
                                                        <span className="input-group-text bg-light border">
                                                            <i className="fas fa-search text-muted"></i>
                                                        </span>
                                                        <input
                                                            type="text"
                                                            className="form-control border"
                                                            placeholder="Search stores by name, city, state..."
                                                            onChange={(e) => {
                                                                const term =
                                                                    e.target.value
                                                                        .toLowerCase()
                                                                        .trim();
                                                                document
                                                                    .querySelectorAll(
                                                                        ".store-list-item",
                                                                    )
                                                                    .forEach(
                                                                        (
                                                                            item,
                                                                        ) => {
                                                                            const text =
                                                                                item.textContent.toLowerCase();
                                                                            item.style.display =
                                                                                text.includes(
                                                                                    term,
                                                                                )
                                                                                    ? ""
                                                                                    : "none";
                                                                        },
                                                                    );
                                                            }}
                                                        />
                                                    </div>
                                                </div>
                                                <div className="col-md-4 text-end">
                                                    {/* BLACK BADGE */}
                                                    <span className="badge bg-dark fs-6">
                                                        <i className="fas fa-check-circle me-1"></i>
                                                        {selectedStores.length}{" "}
                                                        Selected
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Stores List */}
                                        <div
                                            style={{
                                                maxHeight: "450px",
                                                overflowY: "auto",
                                            }}
                                        >
                                            <div className="list-group list-group-flush">
                                                {availableStores.map(
                                                    (store) => (
                                                        <label
                                                            key={store.id}
                                                            className="list-group-item list-group-item-action store-list-item border-bottom"
                                                            style={{
                                                                cursor: "pointer",
                                                            }}
                                                        >
                                                            <div className="d-flex align-items-center">
                                                                <div className="form-check">
                                                                    <input
                                                                        type="checkbox"
                                                                        className="form-check-input"
                                                                        checked={selectedStores.includes(
                                                                            store.id,
                                                                        )}
                                                                        onChange={(
                                                                            e,
                                                                        ) => {
                                                                            e.stopPropagation();
                                                                            if (
                                                                                e
                                                                                    .target
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
                                                                        style={
                                                                            selectedStores.includes(
                                                                                store.id,
                                                                            )
                                                                                ? {
                                                                                      backgroundColor:
                                                                                          "#000",
                                                                                      borderColor:
                                                                                          "#000",
                                                                                  }
                                                                                : {}
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="flex-grow-1 ms-3">
                                                                    <div className="d-flex align-items-center justify-content-between">
                                                                        <div>
                                                                            <div className="fw-semibold text-dark">
                                                                                {store.name ||
                                                                                    "—"}
                                                                            </div>
                                                                            <small className="text-muted">
                                                                                <i className="fas fa-map-marker-alt me-1"></i>
                                                                                {store
                                                                                    .city
                                                                                    ?.name ||
                                                                                    "N/A"}

                                                                                ,{" "}
                                                                                {store
                                                                                    .state
                                                                                    ?.name ||
                                                                                    "N/A"}
                                                                                {store
                                                                                    .area
                                                                                    ?.name &&
                                                                                    ` - ${store.area.name}`}
                                                                            </small>
                                                                        </div>
                                                                        {selectedStores.includes(
                                                                            store.id,
                                                                        ) && (
                                                                            <i className="fas fa-check-circle text-dark"></i>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    </>
                                )}
                            </div>

                            {/* LIGHT FOOTER WITH DARK BUTTONS */}
                            <div className="modal-footer border-0 bg-light">
                                <button
                                    className="btn btn-light border"
                                    onClick={() =>
                                        setAssignStoresModal({
                                            show: false,
                                            employeeId: null,
                                            employeeName: "",
                                        })
                                    }
                                >
                                    <i className="fas fa-times me-2"></i>
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

            {/* BLACK & WHITE THEME - Image Modal */}
            {imageModal.show && (
                <div
                    className="modal show d-block"
                    style={{
                        backgroundColor: "rgba(0,0,0,0.5)",
                        zIndex: 1050,
                    }}
                    onClick={() =>
                        setImageModal({
                            show: false,
                            url: null,
                            title: "",
                        })
                    }
                >
                    <div
                        className="modal-dialog modal-lg modal-dialog-centered"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="modal-content border-0 shadow-lg">
                            <div className="modal-header bg-dark text-white border-0">
                                <h5 className="modal-title fw-bold">
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

            {/* Alert Modal */}
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
