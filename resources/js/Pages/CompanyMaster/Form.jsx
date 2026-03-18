import { useForm, router, usePage } from "@inertiajs/react";
import { useState, useEffect } from "react";
import MainLayout from "@/Layouts/MainLayout";
import AlertModal from "../AlertModel";
import AddressSection from "@/Components/AddressSection";

export default function Form({ auth, company = null, areas = [] }) {
    const { flash, errors: serverErrors } = usePage().props;

    const { data, setData, post, processing } = useForm({
        name: company?.name || "",
        address: company?.address || "",
        state_id: company?.state_id || null,
        city_id: company?.city_id || null,
        area_id: company?.area_id || null,
        pin_code: company?.pin_code || "",
        contact_number_1: company?.contact_number_1 || "",
        email_1: company?.email_1 || "",
        latitude: company?.latitude || null,
        longitude: company?.longitude || null,
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
        setData((prev) => ({ ...prev, ...values }));
    };

    const submit = (e) => {
        e.preventDefault();
        post(`/company-masters/${company.id}`, { preserveScroll: true });
    };

    return (
        <MainLayout user={auth.user} title="Company Master">
            <div
                className="container-fluid py-4"
                style={{ backgroundColor: "#ffffff", minHeight: "100vh" }}
            >
                <div className="mb-4">
                    <h2 className="mb-1 fw-bold">Edit Company</h2>
                    <p className="text-muted mb-0">
                        Update company information
                    </p>
                </div>

                <div className="card border-0 shadow-sm">
                    <div className="card-body p-4">
                        <form onSubmit={submit}>
                            <div className="row">
                                {/* Company Name */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Company Name{" "}
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
                                            placeholder="Enter company name"
                                        />
                                        {errors.name && (
                                            <div className="invalid-feedback">
                                                {errors.name}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Contact */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Contact Number
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
                                            placeholder="Enter contact number"
                                        />
                                    </div>
                                </div>

                                {/* Email */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Email
                                        </label>
                                        <input
                                            type="email"
                                            className="form-control"
                                            value={data.email_1}
                                            onChange={(e) =>
                                                setData(
                                                    "email_1",
                                                    e.target.value,
                                                )
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
                                    userLocation={{}}
                                    locationLocks={{
                                        zone_id: false,
                                        state_id: false,
                                        city_id: false,
                                        area_id: false,
                                    }}
                                    onChange={handleAddressChange}
                                    errors={errors}
                                />
                            </div>

                            <div className="d-flex gap-2 justify-content-end pt-3 mt-3 border-top">
                                <button
                                    type="button"
                                    className="btn btn-light px-4"
                                    onClick={() =>
                                        router.visit("/company-masters")
                                    }
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="btn btn-dark text-white px-4"
                                    disabled={processing}
                                >
                                    {processing ? "Saving..." : "Update"}
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
