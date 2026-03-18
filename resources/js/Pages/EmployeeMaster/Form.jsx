import { useForm, router, usePage } from "@inertiajs/react";
import { useState, useEffect } from "react";
import MainLayout from "@/Layouts/MainLayout";
import AlertModal from "../AlertModel";
import AddressSection from "@/Components/AddressSection";
import Select from "react-select";
import axios from "axios";

export default function Form({
    auth,
    employee = null,
    company = null,
    branches = [],
    departments = [],
    roles = [],
    managers = [],
    userLocation = {},
    locationLocks = {},
    areas = [],
}) {
    const { flash, errors: serverErrors } = usePage().props;
    const isEdit = !!employee;

    const roleOptions = roles.map((r) => ({ value: r.id, label: r.name }));
    const branchOptions = branches.map((b) => ({ value: b.id, label: b.name }));
    const departmentOptions = departments.map((d) => ({
        value: d.id,
        label: d.name,
    }));

    const { data, setData, post, processing } = useForm({
        name: employee?.name || "",
        email: employee?.email || "",
        password: "",
        role_id: employee?.role_id || null,
        designation: employee?.designation || "",
        company_id: employee?.company_id || company?.id || null,
        branch_id: employee?.branch_id || null,
        dept_id: employee?.dept_id || null,
        state_id: employee?.state_id || userLocation.state_id || null,
        city_id: employee?.city_id || userLocation.city_id || null,
        area_id: employee?.area_id || null,
        zone_id: employee?.zone_id || userLocation.zone_id || null,
        pin_code: employee?.pin_code || "",
        address: employee?.address || "",
        contact_number_1: employee?.contact_number_1 || "",
        contact_number_2: employee?.contact_number_2 || "",
        email_1: employee?.email_1 || "",
        aadhar_number: employee?.aadhar_number || "",
        aadhar_image: null,
        employee_image: null,
        dob: employee?.dob || "",
        doj: employee?.doj || "",
        reporting_to: employee?.reporting_to || null,
        country: "India",
    });

    const [alert, setAlert] = useState({ show: false, type: "", message: "" });
    const [errors, setErrors] = useState({});
    const [managerList, setManagerList] = useState(managers);
    const [loadingManagers, setLoadingManagers] = useState(false);

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

    const handleRoleChange = async (option) => {
        setData("role_id", option?.value || null);
        setData("reporting_to", null);
        setManagerList([]);

        if (!option?.value) return;

        setLoadingManagers(true);
        try {
            const res = await axios.get("/api/managers-by-role", {
                params: { role_id: option.value },
            });
            setManagerList(res.data);
        } catch (err) {
            console.error("Failed to load managers:", err);
        } finally {
            setLoadingManagers(false);
        }
    };
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
        const url = isEdit
            ? `/employee-masters/${employee.id}`
            : "/employee-masters";
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

    const managerOptions = managerList.map((m) => ({
        value: m.id,
        label: m.name,
    }));

    return (
        <MainLayout user={auth.user} title="Employee Management">
            <div
                className="container-fluid py-4"
                style={{ backgroundColor: "#ffffff", minHeight: "100vh" }}
            >
                <div className="mb-4">
                    <h2 className="mb-1 fw-bold">
                        {isEdit ? "Edit" : "Add New"} Employee
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

                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Employee Name{" "}
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
                                            placeholder="Enter employee name"
                                        />
                                        {errors.name && (
                                            <div className="invalid-feedback">
                                                {errors.name}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Email (Login){" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                        </label>
                                        <input
                                            type="email"
                                            className={`form-control ${errors.email ? "is-invalid" : ""}`}
                                            value={data.email}
                                            onChange={(e) =>
                                                setData("email", e.target.value)
                                            }
                                            placeholder="Enter login email"
                                        />
                                        {errors.email && (
                                            <div className="invalid-feedback">
                                                {errors.email}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Password{" "}
                                            {!isEdit && (
                                                <span className="text-danger">
                                                    *
                                                </span>
                                            )}
                                        </label>
                                        <input
                                            type="password"
                                            className={`form-control ${errors.password ? "is-invalid" : ""}`}
                                            value={data.password}
                                            onChange={(e) =>
                                                setData(
                                                    "password",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder={
                                                isEdit
                                                    ? "Leave blank to keep current"
                                                    : "Enter password"
                                            }
                                        />
                                        {errors.password && (
                                            <div className="invalid-feedback">
                                                {errors.password}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Role */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Role{" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                        </label>
                                        <Select
                                            options={roleOptions}
                                            value={
                                                roleOptions.find(
                                                    (o) =>
                                                        o.value ===
                                                        data.role_id,
                                                ) || null
                                            }
                                            onChange={handleRoleChange}
                                            placeholder="Select role..."
                                            isClearable
                                            menuPortalTarget={document.body}
                                            styles={{
                                                ...selectStyles,
                                                control: (base) => ({
                                                    ...base,
                                                    minHeight: "38px",
                                                    borderColor: errors.role_id
                                                        ? "#dc3545"
                                                        : "#dee2e6",
                                                }),
                                            }}
                                        />
                                        {errors.role_id && (
                                            <div
                                                className="text-danger mt-1"
                                                style={{ fontSize: "0.875em" }}
                                            >
                                                {errors.role_id}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Reporting To — shown after role selected */}
                                {data.role_id && (
                                    <div className="col-md-6">
                                        <div className="mb-3">
                                            <label className="form-label fw-semibold">
                                                Reporting To
                                                {loadingManagers && (
                                                    <span className="spinner-border spinner-border-sm ms-2"></span>
                                                )}
                                            </label>
                                            <Select
                                                options={managerOptions}
                                                value={
                                                    managerOptions.find(
                                                        (o) =>
                                                            o.value ===
                                                            data.reporting_to,
                                                    ) || null
                                                }
                                                onChange={(o) =>
                                                    setData(
                                                        "reporting_to",
                                                        o?.value || null,
                                                    )
                                                }
                                                placeholder={
                                                    loadingManagers
                                                        ? "Loading managers..."
                                                        : managerOptions.length ===
                                                            0
                                                          ? "No managers available"
                                                          : "Select manager..."
                                                }
                                                isClearable
                                                isDisabled={loadingManagers}
                                                menuPortalTarget={document.body}
                                                styles={selectStyles}
                                            />
                                        </div>
                                    </div>
                                )}

                                {/* Designation */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Designation
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={data.designation}
                                            onChange={(e) =>
                                                setData(
                                                    "designation",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="e.g. Sales Manager, On-Trade"
                                        />
                                    </div>
                                </div>

                                {/* ── ORGANIZATION ── */}
                                <div className="col-12 mt-2 mb-2">
                                    <h6 className="fw-bold text-dark border-bottom pb-2">
                                        Organization
                                    </h6>
                                </div>

                                {/* Company — read only */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Company
                                        </label>
                                        <div className="form-control bg-light d-flex align-items-center">
                                            <i className="fas fa-building me-2 text-muted"></i>
                                            <span>{company?.name || "—"}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Branch */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Branch
                                        </label>
                                        <Select
                                            options={branchOptions}
                                            value={
                                                branchOptions.find(
                                                    (o) =>
                                                        o.value ===
                                                        data.branch_id,
                                                ) || null
                                            }
                                            onChange={(o) =>
                                                setData(
                                                    "branch_id",
                                                    o?.value || null,
                                                )
                                            }
                                            placeholder="Select branch..."
                                            isClearable
                                            menuPortalTarget={document.body}
                                            styles={selectStyles}
                                        />
                                    </div>
                                </div>

                                {/* Department */}
                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Department
                                        </label>
                                        <Select
                                            options={departmentOptions}
                                            value={
                                                departmentOptions.find(
                                                    (o) =>
                                                        o.value ===
                                                        data.dept_id,
                                                ) || null
                                            }
                                            onChange={(o) =>
                                                setData(
                                                    "dept_id",
                                                    o?.value || null,
                                                )
                                            }
                                            placeholder="Select department..."
                                            isClearable
                                            menuPortalTarget={document.body}
                                            styles={selectStyles}
                                        />
                                    </div>
                                </div>

                                {/* ── ADDRESS ── */}
                                <div className="col-12 mt-2 mb-2">
                                    <h6 className="fw-bold text-dark border-bottom pb-2">
                                        Location
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
                                    label="Employee Address"
                                />

                                {/* ── CONTACT ── */}
                                <div className="col-12 mt-2 mb-2">
                                    <h6 className="fw-bold text-dark border-bottom pb-2">
                                        Contact Details
                                    </h6>
                                </div>

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
                                        />
                                    </div>
                                </div>

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
                                        />
                                    </div>
                                </div>

                                <div className="col-md-4">
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
                                        />
                                    </div>
                                </div>

                                {/* ── DOCUMENTS ── */}
                                <div className="col-12 mt-2 mb-2">
                                    <h6 className="fw-bold text-dark border-bottom pb-2">
                                        Documents
                                    </h6>
                                </div>

                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Aadhar Number
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={data.aadhar_number}
                                            onChange={(e) =>
                                                setData(
                                                    "aadhar_number",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Aadhar Image / PDF
                                        </label>
                                        <input
                                            type="file"
                                            className="form-control"
                                            accept=".jpg,.jpeg,.png,.pdf"
                                            onChange={(e) =>
                                                setData(
                                                    "aadhar_image",
                                                    e.target.files[0],
                                                )
                                            }
                                        />
                                        {isEdit && employee?.aadhar_image && (
                                            <a
                                                href={`/storage/${employee.aadhar_image}`}
                                                target="_blank"
                                                className="btn btn-sm btn-outline-dark mt-1"
                                            >
                                                View Current
                                            </a>
                                        )}
                                    </div>
                                </div>

                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Employee Photo
                                        </label>
                                        <input
                                            type="file"
                                            className="form-control"
                                            accept=".jpg,.jpeg,.png"
                                            onChange={(e) =>
                                                setData(
                                                    "employee_image",
                                                    e.target.files[0],
                                                )
                                            }
                                        />
                                        {isEdit && employee?.employee_image && (
                                            <img
                                                src={`/storage/${employee.employee_image}`}
                                                alt="Current"
                                                className="mt-1 rounded"
                                                style={{
                                                    height: 40,
                                                    width: 40,
                                                    objectFit: "cover",
                                                }}
                                            />
                                        )}
                                    </div>
                                </div>

                                {/* ── DATES ── */}
                                <div className="col-12 mt-2 mb-2">
                                    <h6 className="fw-bold text-dark border-bottom pb-2">
                                        Dates
                                    </h6>
                                </div>

                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Date of Birth
                                        </label>
                                        <input
                                            type="date"
                                            className="form-control"
                                            value={data.dob}
                                            onChange={(e) =>
                                                setData("dob", e.target.value)
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="col-md-6">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Date of Joining
                                        </label>
                                        <input
                                            type="date"
                                            className="form-control"
                                            value={data.doj}
                                            onChange={(e) =>
                                                setData("doj", e.target.value)
                                            }
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="d-flex gap-2 justify-content-end pt-3 mt-3 border-top">
                                <button
                                    type="button"
                                    className="btn btn-light px-4"
                                    onClick={() =>
                                        router.visit("/employee-masters")
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
