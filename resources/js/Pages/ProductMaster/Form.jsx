import { useForm, router, usePage } from "@inertiajs/react";
import { useState, useEffect } from "react";
import MainLayout from "@/Layouts/MainLayout";
import AlertModal from "../AlertModel";
import Select from "react-select";

export default function Form({
    auth,
    product = null,
    productCategories = [],
    states = [],
    userLocation = {},
    locationLocks = {},
}) {
    const { flash, errors: serverErrors } = usePage().props;
    const isEdit = !!product;

    const pCatOptions = productCategories.map((c) => ({
        value: c.id,
        label: c.name,
    }));
    const stateOptions = states.map((s) => ({ value: s.id, label: s.name }));

    // Pre-select state based on role lock
    const defaultStateId = product?.state_id || userLocation.state_id || null;

    const { data, setData, post, processing } = useForm({
        name: product?.name || "",
        sku: product?.sku || "",
        p_category_id: product?.p_category_id || null,
        mrp: product?.mrp || "",
        pack_size: product?.pack_size || "",
        volume: product?.volume || "",
        state_id: defaultStateId,
        edd: product?.edd || "",
        total_stock: product?.total_stock || "",
        image: null,
        catalogue_pdf: null,
    });

    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [errors, setErrors] = useState({});

    // Is state locked for this role
    const stateIsLocked = locationLocks.state_id === true;

    useEffect(() => {
        if (flash?.success)
            setAlert({ show: true, type: "success", message: flash.success });
        if (flash?.error)
            setAlert({ show: true, type: "error", message: flash.error });
    }, [flash]);

    useEffect(() => {
        if (serverErrors && Object.keys(serverErrors).length > 0) {
            setErrors(serverErrors);
            const first = Object.values(serverErrors)[0];
            setAlert({
                show: true,
                type: "error",
                message: Array.isArray(first) ? first[0] : first,
            });
        }
    }, [serverErrors]);

    const submit = (e) => {
        e.preventDefault();
        const url = isEdit
            ? `/product-masters/${product.id}`
            : "/product-masters";
        post(url, { preserveScroll: true, forceFormData: true });
    };

    const selectStyles = {
        control: (base) => ({
            ...base,
            minHeight: "38px",
            borderColor: "#dee2e6",
        }),
        menuPortal: (base) => ({ ...base, zIndex: 9999 }),
    };

    return (
        <MainLayout user={auth.user} title="Product Master">
            <div
                className="container-fluid py-4"
                style={{ backgroundColor: "#ffffff", minHeight: "100vh" }}
            >
                <div className="mb-4">
                    <h2 className="mb-1 fw-bold">
                        {isEdit ? "Edit" : "Add New"} Product
                    </h2>
                    <p className="text-muted mb-0">Fill in the form below</p>
                </div>

                <div className="card border-0 shadow-sm">
                    <div className="card-body p-4">
                        <form onSubmit={submit}>
                            <div className="row">
                                {/* ── BASIC INFO ── */}
                                <div className="col-12 mb-2">
                                    <h6 className="fw-bold text-dark border-bottom pb-2">
                                        Basic Information
                                    </h6>
                                </div>

                                {/* State */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            State
                                            {stateIsLocked && (
                                                <i
                                                    className="fas fa-lock ms-2 text-warning"
                                                    title="Auto-filled from your profile"
                                                ></i>
                                            )}
                                        </label>
                                        {stateIsLocked ? (
                                            <input
                                                type="text"
                                                className="form-control bg-light"
                                                value={
                                                    userLocation.state_name ||
                                                    ""
                                                }
                                                readOnly
                                            />
                                        ) : (
                                            <Select
                                                options={stateOptions}
                                                value={
                                                    stateOptions.find(
                                                        (o) =>
                                                            o.value ===
                                                            data.state_id,
                                                    ) || null
                                                }
                                                onChange={(o) =>
                                                    setData(
                                                        "state_id",
                                                        o?.value || null,
                                                    )
                                                }
                                                placeholder="Select state..."
                                                isClearable
                                                menuPortalTarget={document.body}
                                                styles={{
                                                    ...selectStyles,
                                                    control: (base) => ({
                                                        ...base,
                                                        minHeight: "38px",
                                                        borderColor:
                                                            errors.state_id
                                                                ? "#dc3545"
                                                                : "#dee2e6",
                                                    }),
                                                }}
                                            />
                                        )}
                                        {errors.state_id && (
                                            <div
                                                className="text-danger mt-1"
                                                style={{ fontSize: "0.875em" }}
                                            >
                                                {errors.state_id}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Product Category */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Product Category{" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                        </label>
                                        <Select
                                            options={pCatOptions}
                                            value={
                                                pCatOptions.find(
                                                    (o) =>
                                                        o.value ===
                                                        data.p_category_id,
                                                ) || null
                                            }
                                            onChange={(o) =>
                                                setData(
                                                    "p_category_id",
                                                    o?.value || null,
                                                )
                                            }
                                            placeholder="Select category..."
                                            isClearable
                                            menuPortalTarget={document.body}
                                            styles={{
                                                ...selectStyles,
                                                control: (base) => ({
                                                    ...base,
                                                    minHeight: "38px",
                                                    borderColor:
                                                        errors.p_category_id
                                                            ? "#dc3545"
                                                            : "#dee2e6",
                                                }),
                                            }}
                                        />
                                        {errors.p_category_id && (
                                            <div
                                                className="text-danger mt-1"
                                                style={{ fontSize: "0.875em" }}
                                            >
                                                {errors.p_category_id}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* SKU */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            SKU{" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                        </label>
                                        <input
                                            type="text"
                                            className={`form-control ${errors.sku ? "is-invalid" : ""}`}
                                            value={data.sku}
                                            onChange={(e) =>
                                                setData("sku", e.target.value)
                                            }
                                            placeholder="Enter unique SKU code"
                                        />
                                        {errors.sku && (
                                            <div className="invalid-feedback">
                                                {errors.sku}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Product Name */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Product Name{" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                        </label>
                                        <input
                                            type="text"
                                            className={`form-control ${errors.name ? "is-invalid" : ""}`}
                                            value={data.name}
                                            onChange={(e) =>
                                                setData("name", e.target.value)
                                            }
                                            placeholder="Enter product name"
                                        />
                                        {errors.name && (
                                            <div className="invalid-feedback">
                                                {errors.name}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Price */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Price (₹){" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            className={`form-control ${errors.mrp ? "is-invalid" : ""}`}
                                            value={data.mrp}
                                            onChange={(e) =>
                                                setData("mrp", e.target.value)
                                            }
                                            placeholder="Enter price"
                                        />
                                        {errors.mrp && (
                                            <div className="invalid-feedback">
                                                {errors.mrp}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Pack Size */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Pack Size
                                        </label>
                                        <input
                                            type="number"
                                            step="1"
                                            className={`form-control ${errors.pack_size ? "is-invalid" : ""}`}
                                            value={data.pack_size}
                                            onChange={(e) =>
                                                setData(
                                                    "pack_size",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="e.g. 24, 18"
                                        />
                                        {errors.pack_size && (
                                            <div className="invalid-feedback">
                                                {errors.pack_size}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Volume */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Volume (ml/ltr)
                                        </label>
                                        <input
                                            type="number"
                                            step="1"
                                            className={`form-control ${errors.volume ? "is-invalid" : ""}`}
                                            value={data.volume}
                                            onChange={(e) =>
                                                setData(
                                                    "volume",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="e.g. 500, 1000"
                                        />
                                        {errors.volume && (
                                            <div className="invalid-feedback">
                                                {errors.volume}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* EDD */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            EDD
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            className="form-control"
                                            value={data.edd}
                                            onChange={(e) =>
                                                setData("edd", e.target.value)
                                            }
                                            placeholder="Enter EDD"
                                        />
                                    </div>
                                </div>

                                {/* Total Stock */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Total Stock
                                        </label>
                                        <input
                                            type="number"
                                            step="1"
                                            className="form-control"
                                            value={data.total_stock}
                                            onChange={(e) =>
                                                setData(
                                                    "total_stock",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Enter total stock"
                                        />
                                    </div>
                                </div>

                                {/* ── FILES ── */}
                                <div className="col-12 mt-2 mb-2">
                                    <h6 className="fw-bold text-dark border-bottom pb-2">
                                        Files
                                    </h6>
                                </div>

                                {/* Product Image */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Product Image
                                        </label>
                                        <input
                                            type="file"
                                            className="form-control"
                                            accept=".jpg,.jpeg,.png"
                                            onChange={(e) =>
                                                setData(
                                                    "image",
                                                    e.target.files[0],
                                                )
                                            }
                                        />
                                        <small className="text-muted">
                                            JPG, PNG — max 2MB
                                        </small>
                                        {isEdit && product?.image && (
                                            <div className="mt-2">
                                                <a
                                                    href={`/storage/${product.image}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="btn btn-sm btn-outline-dark"
                                                >
                                                    <i className="fas fa-image me-1"></i>
                                                    View Current Image
                                                </a>
                                            </div>
                                        )}
                                        {errors.image && (
                                            <div
                                                className="text-danger mt-1"
                                                style={{ fontSize: "0.875em" }}
                                            >
                                                {errors.image}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Catalogue PDF */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Product Catalogue PDF
                                        </label>
                                        <input
                                            type="file"
                                            className="form-control"
                                            accept=".pdf"
                                            onChange={(e) =>
                                                setData(
                                                    "catalogue_pdf",
                                                    e.target.files[0],
                                                )
                                            }
                                        />
                                        <small className="text-muted">
                                            PDF only — max 10MB
                                        </small>
                                        {isEdit && product?.catalogue_pdf && (
                                            <div className="mt-2">
                                                <a
                                                    href={`/storage/${product.catalogue_pdf}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="btn btn-sm btn-outline-dark"
                                                >
                                                    <i className="fas fa-file-pdf me-1"></i>
                                                    View Current PDF
                                                </a>
                                            </div>
                                        )}
                                        {errors.catalogue_pdf && (
                                            <div
                                                className="text-danger mt-1"
                                                style={{ fontSize: "0.875em" }}
                                            >
                                                {errors.catalogue_pdf}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="d-flex gap-2 justify-content-end pt-3 mt-3 border-top">
                                <button
                                    type="button"
                                    className="btn btn-light px-4"
                                    onClick={() =>
                                        router.visit("/product-masters")
                                    }
                                >
                                    <i className="fas fa-times me-2"></i>
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="btn btn-dark text-white px-4"
                                    disabled={processing}
                                >
                                    {processing ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2"></span>
                                            Saving...
                                        </>
                                    ) : (
                                        <>
                                            <i className="fas fa-save me-2"></i>
                                            {isEdit ? "Update" : "Save"}
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
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
