import { useForm, router, usePage } from "@inertiajs/react";
import { useState, useEffect } from "react";
import MainLayout from "@/Layouts/MainLayout";
import AlertModal from "../AlertModel";
import Select from "react-select";
import axios from "axios";

export default function Form({
    auth,
    storeProduct = null,
    stores = [],
    products = [],
}) {
    const { flash, errors: serverErrors } = usePage().props;
    const isEdit = !!storeProduct;

    const storeOptions = stores.map((s) => ({ value: s.id, label: s.name }));

    const { data, setData, post, processing } = useForm({
        store_id: storeProduct?.store_id || null,
        product_id: storeProduct?.product_id || null,
        current_stock: storeProduct?.current_stock || 0,
    });

    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [errors, setErrors] = useState({});
    const [productOptions, setProductOptions] = useState(
        products.map((p) => ({ value: p.id, label: p.name })),
    );
    const [loadingProducts, setLoadingProducts] = useState(false);

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

    const handleStoreChange = async (option) => {
        setData("store_id", option?.value || null);
        setData("product_id", null);
        setProductOptions([]);

        if (!option?.value) return;

        setLoadingProducts(true);
        try {
            const res = await axios.get(
                `/store-products/products-by-store/${option.value}`,
            );
            setProductOptions(
                res.data.map((p) => ({ value: p.id, label: p.name })),
            );
        } catch (err) {
            console.error("Failed to load products:", err);
            setAlert({
                show: true,
                type: "error",
                message: "Failed to load products for this store.",
            });
        } finally {
            setLoadingProducts(false);
        }
    };

    const submit = (e) => {
        e.preventDefault();

        if (!data.store_id) {
            setAlert({
                show: true,
                type: "error",
                message: "Please select a store.",
            });
            return;
        }
        if (!data.product_id) {
            setAlert({
                show: true,
                type: "error",
                message: "Please select a product.",
            });
            return;
        }

        const url = isEdit
            ? `/store-products/${storeProduct.id}`
            : "/store-products";

        post(url, { preserveScroll: true });
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
        <MainLayout user={auth.user} title="Store Product Management">
            <div
                className="container-fluid py-4"
                style={{ backgroundColor: "#ffffff", minHeight: "100vh" }}
            >
                <div className="mb-4">
                    <h2 className="mb-1 fw-bold">
                        {isEdit ? "Edit" : "Add New"} Store Product
                    </h2>
                    <p className="text-muted mb-0">
                        Products shown are filtered by store's state
                    </p>
                </div>

                <div className="card border-0 shadow-sm">
                    <div className="card-body p-4">
                        <form onSubmit={submit}>
                            <div className="row">
                                {/* Store */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Store{" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                        </label>
                                        <Select
                                            options={storeOptions}
                                            value={
                                                storeOptions.find(
                                                    (o) =>
                                                        o.value ===
                                                        data.store_id,
                                                ) || null
                                            }
                                            onChange={handleStoreChange}
                                            placeholder="Select store..."
                                            isClearable
                                            menuPortalTarget={document.body}
                                            styles={{
                                                ...selectStyles,
                                                control: (base) => ({
                                                    ...base,
                                                    minHeight: "38px",
                                                    borderColor: errors.store_id
                                                        ? "#dc3545"
                                                        : "#dee2e6",
                                                }),
                                            }}
                                        />
                                        {errors.store_id && (
                                            <div
                                                className="text-danger mt-1"
                                                style={{ fontSize: "0.875em" }}
                                            >
                                                {errors.store_id}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Product — filtered by store state */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Product{" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                            {loadingProducts && (
                                                <span className="spinner-border spinner-border-sm ms-2"></span>
                                            )}
                                        </label>
                                        <Select
                                            options={productOptions}
                                            value={
                                                productOptions.find(
                                                    (o) =>
                                                        o.value ===
                                                        data.product_id,
                                                ) || null
                                            }
                                            onChange={(o) =>
                                                setData(
                                                    "product_id",
                                                    o?.value || null,
                                                )
                                            }
                                            placeholder={
                                                !data.store_id
                                                    ? "Select store first..."
                                                    : loadingProducts
                                                      ? "Loading products..."
                                                      : productOptions.length ===
                                                          0
                                                        ? "No products for this state"
                                                        : "Select product..."
                                            }
                                            isClearable
                                            isDisabled={
                                                !data.store_id ||
                                                loadingProducts
                                            }
                                            menuPortalTarget={document.body}
                                            styles={{
                                                ...selectStyles,
                                                control: (base) => ({
                                                    ...base,
                                                    minHeight: "38px",
                                                    borderColor:
                                                        errors.product_id
                                                            ? "#dc3545"
                                                            : "#dee2e6",
                                                }),
                                            }}
                                        />
                                        {errors.product_id && (
                                            <div
                                                className="text-danger mt-1"
                                                style={{ fontSize: "0.875em" }}
                                            >
                                                {errors.product_id}
                                            </div>
                                        )}
                                        {data.store_id &&
                                            !loadingProducts &&
                                            productOptions.length === 0 && (
                                                <small className="text-warning d-block mt-1">
                                                    <i className="fas fa-exclamation-triangle me-1"></i>
                                                    No products found for this
                                                    store's state. Please add
                                                    products for that state
                                                    first.
                                                </small>
                                            )}
                                    </div>
                                </div>

                                {/* Current Stock */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Current Stock{" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                        </label>
                                        <input
                                            type="number"
                                            step="1"
                                            min="0"
                                            className={`form-control ${errors.current_stock ? "is-invalid" : ""}`}
                                            value={data.current_stock}
                                            onChange={(e) =>
                                                setData(
                                                    "current_stock",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Enter current stock quantity"
                                        />
                                        {errors.current_stock && (
                                            <div className="invalid-feedback">
                                                {errors.current_stock}
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
                                        router.visit("/store-products")
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
