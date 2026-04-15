import { useForm, router, usePage } from "@inertiajs/react";
import { useState, useEffect } from "react";
import MainLayout from "@/Layouts/MainLayout";
import AlertModal from "../AlertModel";
import AddressSection from "@/Components/AddressSection";
import Select from "react-select";

export default function Form({
    auth,
    branch = null,
    company = null,
    userLocation = {},
    locationLocks = {},
    areas = [],
}) {
    const { flash, errors: serverErrors } = usePage().props;
    const isEdit = !!branch;

    const { data, setData, post, processing } = useForm({
        company_id: company?.id || null,
        name: branch?.name || "",
        address: branch?.address || "",
        state_id: branch?.state_id || userLocation.state_id || null,
        city_id: branch?.city_id || userLocation.city_id || null,
        area_id: branch?.area_id || null,
        pin_code: branch?.pin_code || "",
        contact_number_1: branch?.contact_number_1 || "",
        contact_number_2: branch?.contact_number_2 || "",
        email: branch?.email || "",
        latitude: branch?.latitude || null,
        longitude: branch?.longitude || null,
        country: "India",
    });

    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [errors, setErrors] = useState({});

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
        console.log("handleAddressChange received:", values);

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

    const submit = (e) => {
        e.preventDefault();
        const url = isEdit ? `/branch-masters/${branch.id}` : "/branch-masters";
        post(url, { preserveScroll: true, forceFormData: true });
    };

    return (
        <MainLayout user={auth.user} title="Branch Master">
            <div
                className="container-fluid py-4"
                style={{ backgroundColor: "#ffffff", minHeight: "100vh" }}
            >
                <div className="mb-4">
                    <h2 className="mb-1 fw-bold">
                        {isEdit ? "Edit" : "Add New"} Branch
                    </h2>
                    <p className="text-muted mb-0">Fill in the form below</p>
                </div>

                <div className="card border-0 shadow-sm">
                    <div className="card-body p-4">
                        <form onSubmit={submit}>
                            <div className="row">
                                {/* Company — read only label */}
                                {/* <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Company
                                        </label>
                                        <div className="form-control bg-light d-flex align-items-center">
                                            <i className="fas fa-building me-2 text-muted"></i>
                                            <span>{company?.name || "—"}</span>
                                        </div>
                                    </div>
                                </div> */}

                                {/* Branch Name */}
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Branch Name{" "}
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
                                            placeholder="Enter branch name"
                                        />
                                        {errors.name && (
                                            <div className="invalid-feedback">
                                                {errors.name}
                                            </div>
                                        )}
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

                                {/* Address Section */}
                                <AddressSection
                                    address={data.address}
                                    areaId={data.area_id}
                                    pinCode={data.pin_code}
                                    userLocation={userLocation}
                                    locationLocks={locationLocks}
                                    onChange={handleAddressChange}
                                    errors={errors}
                                />
                            </div>

                            <div className="d-flex gap-2 justify-content-end pt-3 mt-3 border-top">
                                <button
                                    type="button"
                                    className="btn btn-light px-4"
                                    onClick={() =>
                                        router.visit("/branch-masters")
                                    }
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="btn btn-dark text-white px-4"
                                    disabled={processing}
                                >
                                    {processing
                                        ? "Saving..."
                                        : isEdit
                                          ? "Update"
                                          : "Save"}
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
