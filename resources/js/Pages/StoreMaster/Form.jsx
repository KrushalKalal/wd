import { useForm, router, usePage } from "@inertiajs/react";
import { useState, useEffect } from "react";
import MainLayout from "@/Layouts/MainLayout";
import AlertModal from "../AlertModel";
import AddressSection from "@/Components/AddressSection";
import Select from "react-select";

export default function Form({
    auth,
    store = null,
    userLocation = {},
    locationLocks = {},
    categoryOnes = [],
    categoryTwos = [],
    categoryThrees = [],
    areas = [],
}) {
    const { flash, errors: serverErrors } = usePage().props;
    const isEdit = !!store;

    const cat1Options = categoryOnes.map((c) => ({
        value: c.id,
        label: c.name,
    }));
    const cat2Options = categoryTwos.map((c) => ({
        value: c.id,
        label: c.name,
    }));
    const cat3Options = categoryThrees.map((c) => ({
        value: c.id,
        label: c.name,
    }));

    // const existingBilling = store?.billing_details || null;
    // const existingShipping = store?.shipping_details || null;
    const existingBilling = null;
    const existingShipping = null;

    const { data, setData, post, processing } = useForm({
        name: store?.name || "",
        store_legal_name: store?.store_legal_name || "",
        store_incharge: store?.store_incharge || "",
        address: store?.address || "",
        state_id: store?.state_id || userLocation.state_id || null,
        city_id: store?.city_id || userLocation.city_id || null,
        area_id: store?.area_id || null,
        pin_code: store?.pin_code || "",
        latitude: null,
        longitude: null,
        zone_id: store?.zone_id || userLocation.zone_id || null,
        category_one_id: store?.category_one_id || null,
        category_two_id: store?.category_two_id || null,
        category_three_id: store?.category_three_id || null,
        contact_number_1: store?.contact_number_1 || "",
        contact_number_2: store?.contact_number_2 || "",
        email: store?.email || "",
        // billing_address: existingBilling?.address || "",
        // billing_latitude: existingBilling?.latitude || null,
        // billing_longitude: existingBilling?.longitude || null,
        // shipping_address: existingShipping?.address || "",
        // shipping_latitude: existingShipping?.latitude || null,
        // shipping_longitude: existingShipping?.longitude || null,
        country: "India",
        manual_stock_entry: true,
    });

    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [errors, setErrors] = useState({});
    // const [sameAsBilling, setSameAsBilling] = useState(false);

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

    const handleAddressChange = (values) => {
        if (values.address !== undefined) setData("address", values.address);
        if (values.state_id !== undefined) setData("state_id", values.state_id);
        if (values.city_id !== undefined) setData("city_id", values.city_id);
        if (values.area_id !== undefined) setData("area_id", values.area_id);
        if (values.zone_id !== undefined) setData("zone_id", values.zone_id);
        if (values.pin_code !== undefined) setData("pin_code", values.pin_code);
        if (values.latitude !== undefined) setData("latitude", values.latitude);
        if (values.longitude !== undefined)
            setData("longitude", values.longitude);
    };

    // const handleBillingChange = (values) => { ... };
    // const handleShippingChange = (values) => { ... };
    // const handleSameAsBilling = (checked) => { ... };

    const submit = (e) => {
        e.preventDefault();
        const url = isEdit ? `/store-masters/${store.id}` : "/store-masters";
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
        <MainLayout user={auth.user} title="Store Master">
            <div
                className="container-fluid py-4"
                style={{ backgroundColor: "#ffffff", minHeight: "100vh" }}
            >
                <div className="mb-4">
                    <h2 className="mb-1 fw-bold">
                        {isEdit ? "Edit" : "Add New"} Store
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

                                {/* Store Name */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Store Name{" "}
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
                                            placeholder="Enter store name"
                                        />
                                        {errors.name && (
                                            <div className="invalid-feedback">
                                                {errors.name}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Legal Name */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Legal / Official Name
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={data.store_legal_name}
                                            onChange={(e) =>
                                                setData(
                                                    "store_legal_name",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="GST / company name"
                                        />
                                    </div>
                                </div>

                                {/* Store Incharge */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Store Incharge
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={data.store_incharge}
                                            onChange={(e) =>
                                                setData(
                                                    "store_incharge",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Enter incharge name"
                                        />
                                    </div>
                                </div>

                                {/* Contact 1 */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Contact Number 1
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={data.contact_number_1}
                                            onChange={(e) =>
                                                setData(
                                                    "contact_number_1",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Primary contact"
                                        />
                                    </div>
                                </div>

                                {/* Contact 2 */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Contact Number 2
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={data.contact_number_2}
                                            onChange={(e) =>
                                                setData(
                                                    "contact_number_2",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Secondary contact"
                                        />
                                    </div>
                                </div>

                                {/* Email */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Email
                                        </label>
                                        <input
                                            type="email"
                                            className="form-control"
                                            value={data.email}
                                            onChange={(e) =>
                                                setData("email", e.target.value)
                                            }
                                            placeholder="Enter email"
                                        />
                                    </div>
                                </div>

                                {/* ── CATEGORIES ── */}
                                <div className="col-12 mt-2 mb-2">
                                    <h6 className="fw-bold text-dark border-bottom pb-2">
                                        Categories
                                    </h6>
                                </div>

                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Group/Party wise
                                        </label>
                                        <Select
                                            options={cat1Options}
                                            value={
                                                cat1Options.find(
                                                    (o) =>
                                                        o.value ===
                                                        data.category_one_id,
                                                ) || null
                                            }
                                            onChange={(o) =>
                                                setData(
                                                    "category_one_id",
                                                    o?.value || null,
                                                )
                                            }
                                            placeholder="Select..."
                                            isClearable
                                            menuPortalTarget={document.body}
                                            styles={selectStyles}
                                        />
                                    </div>
                                </div>

                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Off/On Trade
                                        </label>
                                        <Select
                                            options={cat2Options}
                                            value={
                                                cat2Options.find(
                                                    (o) =>
                                                        o.value ===
                                                        data.category_two_id,
                                                ) || null
                                            }
                                            onChange={(o) =>
                                                setData(
                                                    "category_two_id",
                                                    o?.value || null,
                                                )
                                            }
                                            placeholder="Select..."
                                            isClearable
                                            menuPortalTarget={document.body}
                                            styles={selectStyles}
                                        />
                                    </div>
                                </div>

                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Bar/Club/Restaurant -
                                            Group/Individual
                                        </label>
                                        <Select
                                            options={cat3Options}
                                            value={
                                                cat3Options.find(
                                                    (o) =>
                                                        o.value ===
                                                        data.category_three_id,
                                                ) || null
                                            }
                                            onChange={(o) =>
                                                setData(
                                                    "category_three_id",
                                                    o?.value || null,
                                                )
                                            }
                                            placeholder="Select..."
                                            isClearable
                                            menuPortalTarget={document.body}
                                            styles={selectStyles}
                                        />
                                    </div>
                                </div>

                                {/* ── STORE ADDRESS ── */}
                                <div className="col-12 mt-2 mb-2">
                                    <h6 className="fw-bold text-dark border-bottom pb-2">
                                        Store Address
                                    </h6>
                                </div>

                                <AddressSection
                                    address={data.address}
                                    areaId={data.area_id}
                                    pinCode={data.pin_code}
                                    userLocation={userLocation}
                                    locationLocks={locationLocks}
                                    onChange={handleAddressChange}
                                    errors={errors}
                                    label="Store Address"
                                />

                                {/* ── BILLING ADDRESS — hidden for now ── */}
                                {/* <div className="col-12 mt-2 mb-2">
                                    <h6 className="fw-bold text-dark border-bottom pb-2">Billing Address</h6>
                                </div>
                                <AddressSection
                                    address={data.billing_address}
                                    userLocation={{}}
                                    locationLocks={{}}
                                    onChange={handleBillingChange}
                                    errors={{}}
                                    simple={true}
                                    label="Billing Address"
                                /> */}

                                {/* ── SHIPPING ADDRESS — hidden for now ── */}
                                {/* <div className="col-12 mt-2 mb-2">
                                    <div className="d-flex align-items-center gap-3 border-bottom pb-2">
                                        <h6 className="fw-bold text-dark mb-0">Shipping Address</h6>
                                        <div className="form-check mb-0">
                                            <input
                                                type="checkbox"
                                                className="form-check-input"
                                                id="sameAsBilling"
                                                checked={sameAsBilling}
                                                onChange={(e) => handleSameAsBilling(e.target.checked)}
                                            />
                                            <label className="form-check-label small" htmlFor="sameAsBilling">
                                                Same as billing
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                {!sameAsBilling && (
                                    <AddressSection
                                        address={data.shipping_address}
                                        userLocation={{}}
                                        locationLocks={{}}
                                        onChange={handleShippingChange}
                                        errors={{}}
                                        simple={true}
                                        label="Shipping Address"
                                    />
                                )} */}
                            </div>

                            {/* ── BUTTONS ── */}
                            <div className="d-flex gap-2 justify-content-end pt-3 mt-3 border-top">
                                <button
                                    type="button"
                                    className="btn btn-light px-4"
                                    onClick={() =>
                                        router.visit("/store-masters")
                                    }
                                >
                                    <i className="fas fa-times me-2"></i>Cancel
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
