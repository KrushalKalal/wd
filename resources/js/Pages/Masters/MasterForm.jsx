import React, { useEffect, useState } from "react";
import { useForm, router, usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";
import AlertModal from "../AlertModel";
import Select from "react-select";
import axios from "axios";

export default function MasterForm({
    auth,
    masterName,
    masterData = null,
    viewBase,
    fields = [],
    hasStateDropdown = false,
    hasCityDropdown = false,
    hasAreaDropdown = false,
    hasZoneDropdown = false,
    zones = [],
    states = [],
    title,
}) {
    const { flash, errors: serverErrors } = usePage().props;
    const [errors, setErrors] = useState({});
    const [availableStates, setAvailableStates] = useState(states || []);
    const [selectedZone, setSelectedZone] = useState(null);
    const [selectedState, setSelectedState] = useState(null);
    const [selectedCity, setSelectedCity] = useState(null);
    const [selectedArea, setSelectedArea] = useState(null);
    const [cities, setCities] = useState([]);
    const [areas, setAreas] = useState([]);
    const [alert, setAlert] = useState({
        show: false,
        type: "",
        message: "",
    });

    const isEdit = !!masterData;

    // Initialize form data
    const initialData = {};
    fields.forEach((field) => {
        if (field.type === "date" && masterData?.[field.name]) {
            initialData[field.name] = masterData[field.name];
        } else if (field.type === "number" && masterData?.[field.name]) {
            initialData[field.name] = masterData[field.name];
        } else if (field.type === "multiselect" && masterData?.[field.name]) {
            initialData[field.name] = masterData[field.name] || [];
        } else if (field.type === "json" && masterData?.[field.name]) {
            initialData[field.name] =
                typeof masterData[field.name] === "object"
                    ? JSON.stringify(masterData[field.name], null, 2)
                    : masterData[field.name];
        } else if (field.type === "file") {
            initialData[field.name] = null;
        } else if (
            field.type === "select" &&
            field.name !== "state_id" &&
            field.name !== "city_id" &&
            field.name !== "area_id"
        ) {
            initialData[field.name] = masterData?.[field.name] || "";
        } else {
            initialData[field.name] = masterData?.[field.name] || "";
        }
    });

    const { data, setData, post, processing } = useForm(initialData);

    const zoneOptions = zones.map((z) => ({ value: z.id, label: z.name }));
    const stateOptions = states.map((s) => ({ value: s.id, label: s.name }));
    const cityOptions = cities.map((c) => ({ value: c.id, label: c.name }));
    const areaOptions = areas.map((a) => ({ value: a.id, label: a.name }));

    // Show flash messages
    useEffect(() => {
        if (flash?.success) {
            setAlert({
                show: true,
                type: "success",
                message: flash.success,
            });
        }
        if (flash?.error) {
            setAlert({
                show: true,
                type: "error",
                message: flash.error,
            });
        }
    }, [flash]);

    // Handle server validation errors
    useEffect(() => {
        if (serverErrors && Object.keys(serverErrors).length > 0) {
            setErrors(serverErrors);
            const firstError = Object.values(serverErrors)[0];
            const errorMessage = Array.isArray(firstError)
                ? firstError[0]
                : firstError;
            setAlert({
                show: true,
                type: "error",
                message: errorMessage,
            });
        }
    }, [serverErrors]);

    useEffect(() => {
        if (masterData) {
            fields.forEach((field) => {
                if (field.type === "date" && masterData[field.name]) {
                    setData(field.name, masterData[field.name]);
                } else if (field.type === "number" && masterData[field.name]) {
                    setData(field.name, masterData[field.name]);
                } else if (
                    field.type === "multiselect" &&
                    masterData[field.name]
                ) {
                    setData(field.name, masterData[field.name] || []);
                } else if (field.type === "json" && masterData[field.name]) {
                    const jsonValue =
                        typeof masterData[field.name] === "object"
                            ? JSON.stringify(masterData[field.name], null, 2)
                            : masterData[field.name];
                    setData(field.name, jsonValue);
                } else if (field.type === "file") {
                    setData(field.name, null);
                } else {
                    setData(field.name, masterData[field.name] || "");
                }
            });

            if (hasZoneDropdown && masterData.zone_id) {
                const zone = zoneOptions.find(
                    (z) => z.value === masterData.zone_id,
                );
                setSelectedZone(zone || null);
            }

            if (hasStateDropdown && masterData.state_id) {
                const state = stateOptions.find(
                    (s) => s.value === masterData.state_id,
                );
                setSelectedState(state || null);

                if (hasCityDropdown) {
                    fetchCities(masterData.state_id, masterData.city_id);
                }
            }
        }
        setErrors({});
    }, [masterData]);

    const fetchCities = (stateId, cityIdToSelect = null) => {
        axios
            .get(`/cities/${stateId}`)
            .then((res) => {
                setCities(res.data);
                if (cityIdToSelect) {
                    const city = res.data.find((c) => c.id === cityIdToSelect);
                    if (city) {
                        setSelectedCity({ value: city.id, label: city.name });

                        if (hasAreaDropdown && masterData?.area_id) {
                            fetchAreas(cityIdToSelect, masterData.area_id);
                        }
                    }
                }
            })
            .catch((err) => {
                console.error("Error fetching cities:", err);
            });
    };

    const fetchAreas = (cityId, areaIdToSelect = null) => {
        axios
            .get(`/areas/${cityId}`)
            .then((res) => {
                setAreas(res.data);
                if (areaIdToSelect) {
                    const area = res.data.find((a) => a.id === areaIdToSelect);
                    if (area) {
                        setSelectedArea({ value: area.id, label: area.name });
                    }
                }
            })
            .catch((err) => {
                console.error("Error fetching areas:", err);
            });
    };

    const handleZoneChange = (option) => {
        setSelectedZone(option);
        setData("zone_id", option?.value || null);

        // Reset dependent dropdowns
        setData("state_id", null);
        setData("city_id", null);
        setData("area_id", null);
        setSelectedState(null);
        setSelectedCity(null);
        setSelectedArea(null);
        setCities([]);
        setAreas([]);

        if (option?.value) {
            // Fetch states for this zone
            axios
                .get(`/states/${option.value}`)
                .then((res) => {
                    // Update available states
                    const stateOptions = res.data.map((s) => ({
                        value: s.id,
                        label: s.name,
                    }));
                    // You'll need to add a state variable for availableStates
                    // setAvailableStates(res.data);
                })
                .catch((err) => {
                    console.error("Error fetching states for zone:", err);
                });
        }
    };

    const handleStateChange = (option) => {
        setSelectedState(option);
        setData("state_id", option?.value || null);
        setData("city_id", null);
        setData("area_id", null);
        setSelectedCity(null);
        setSelectedArea(null);
        setCities([]);
        setAreas([]);

        if (option?.value) {
            fetchCities(option.value);
        }
    };

    const handleCityChange = (option) => {
        setSelectedCity(option);
        setData("city_id", option?.value || null);
        setData("area_id", null);
        setSelectedArea(null);
        setAreas([]);

        if (option?.value) {
            fetchAreas(option.value);
        }
    };

    const handleAreaChange = (option) => {
        setSelectedArea(option);
        setData("area_id", option?.value || null);
    };

    const validateForm = () => {
        const newErrors = {};

        fields.forEach((field) => {
            if (field.required) {
                if (field.type === "select") {
                    if (!data[field.name]) {
                        newErrors[field.name] = `${field.label} is required`;
                    }
                } else if (field.type === "multiselect") {
                    if (!data[field.name] || data[field.name].length === 0) {
                        newErrors[field.name] = `${field.label} is required`;
                    }
                } else if (field.type === "number") {
                    if (
                        data[field.name] === "" ||
                        data[field.name] === null ||
                        data[field.name] === undefined
                    ) {
                        newErrors[field.name] = `${field.label} is required`;
                    }
                } else if (field.type === "date") {
                    if (!data[field.name]) {
                        newErrors[field.name] = `${field.label} is required`;
                    }
                } else if (field.type === "file") {
                    if (!isEdit && !data[field.name]) {
                        newErrors[field.name] = `${field.label} is required`;
                    }
                } else if (field.type === "json") {
                    if (!data[field.name] || data[field.name].trim() === "") {
                        newErrors[field.name] = `${field.label} is required`;
                    } else {
                        try {
                            JSON.parse(data[field.name]);
                        } catch (e) {
                            newErrors[field.name] = `Invalid JSON format`;
                        }
                    }
                } else {
                    if (!data[field.name] || data[field.name].trim() === "") {
                        newErrors[field.name] = `${field.label} is required`;
                    }
                }
            } else if (field.type === "json" && data[field.name]) {
                try {
                    JSON.parse(data[field.name]);
                } catch (e) {
                    newErrors[field.name] = `Invalid JSON format`;
                }
            }
        });

        setErrors(newErrors);

        if (Object.keys(newErrors).length > 0) {
            const firstError = Object.values(newErrors)[0];
            setAlert({
                show: true,
                type: "error",
                message: firstError,
            });
        }

        return Object.keys(newErrors).length === 0;
    };

    const submit = (e) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        const url = isEdit ? `${viewBase}/${masterData.id}` : viewBase;
        const submitData = { ...data };

        fields.forEach((field) => {
            if (field.type === "json" && submitData[field.name]) {
                try {
                    submitData[field.name] = JSON.parse(submitData[field.name]);
                } catch (e) {
                    // Keep as string if parsing fails
                }
            }
        });

        post(url, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: (page) => {
                console.log("Success response:", page);
            },
            onError: (errors) => {
                console.log("Error response:", errors);
                setErrors(errors);
                const firstError = Object.values(errors)[0];
                const errorMessage = Array.isArray(firstError)
                    ? firstError[0]
                    : firstError;
                setAlert({
                    show: true,
                    type: "error",
                    message: errorMessage,
                });
            },
        });
    };

    const renderField = (field) => {
        const fieldClass = field.fullWidth ? "col-12" : "col-md-6";

        switch (field.type) {
            case "text":
            case "email":
                return (
                    <div className={fieldClass} key={field.name}>
                        <div className="mb-3">
                            <label className="form-label fw-semibold">
                                {field.label}{" "}
                                {field.required && (
                                    <span className="text-danger">*</span>
                                )}
                            </label>
                            <input
                                type={field.type}
                                className={`form-control ${
                                    errors[field.name] ? "is-invalid" : ""
                                }`}
                                value={data[field.name] || ""}
                                onChange={(e) =>
                                    setData(field.name, e.target.value)
                                }
                                placeholder={
                                    field.placeholder || `Enter ${field.label}`
                                }
                            />
                            {errors[field.name] && (
                                <div className="invalid-feedback">
                                    {Array.isArray(errors[field.name])
                                        ? errors[field.name][0]
                                        : errors[field.name]}
                                </div>
                            )}
                        </div>
                    </div>
                );

            case "number":
                return (
                    <div className={fieldClass} key={field.name}>
                        <div className="mb-3">
                            <label className="form-label fw-semibold">
                                {field.label}{" "}
                                {field.required && (
                                    <span className="text-danger">*</span>
                                )}
                            </label>
                            <input
                                type="number"
                                step={field.step || "any"}
                                className={`form-control ${
                                    errors[field.name] ? "is-invalid" : ""
                                }`}
                                value={data[field.name] || ""}
                                onChange={(e) =>
                                    setData(field.name, e.target.value)
                                }
                                placeholder={
                                    field.placeholder || `Enter ${field.label}`
                                }
                            />
                            {errors[field.name] && (
                                <div className="invalid-feedback">
                                    {Array.isArray(errors[field.name])
                                        ? errors[field.name][0]
                                        : errors[field.name]}
                                </div>
                            )}
                        </div>
                    </div>
                );

            case "date":
                return (
                    <div className={fieldClass} key={field.name}>
                        <div className="mb-3">
                            <label className="form-label fw-semibold">
                                {field.label}{" "}
                                {field.required && (
                                    <span className="text-danger">*</span>
                                )}
                            </label>
                            <input
                                type="date"
                                className={`form-control ${
                                    errors[field.name] ? "is-invalid" : ""
                                }`}
                                value={data[field.name] || ""}
                                onChange={(e) =>
                                    setData(field.name, e.target.value)
                                }
                            />
                            {errors[field.name] && (
                                <div className="invalid-feedback">
                                    {Array.isArray(errors[field.name])
                                        ? errors[field.name][0]
                                        : errors[field.name]}
                                </div>
                            )}
                        </div>
                    </div>
                );

            case "textarea":
                return (
                    <div className="col-12" key={field.name}>
                        <div className="mb-3">
                            <label className="form-label fw-semibold">
                                {field.label}{" "}
                                {field.required && (
                                    <span className="text-danger">*</span>
                                )}
                            </label>
                            <textarea
                                className={`form-control ${
                                    errors[field.name] ? "is-invalid" : ""
                                }`}
                                value={data[field.name] || ""}
                                onChange={(e) =>
                                    setData(field.name, e.target.value)
                                }
                                rows={field.rows || 3}
                                placeholder={
                                    field.placeholder || `Enter ${field.label}`
                                }
                            />
                            {errors[field.name] && (
                                <div className="invalid-feedback">
                                    {Array.isArray(errors[field.name])
                                        ? errors[field.name][0]
                                        : errors[field.name]}
                                </div>
                            )}
                        </div>
                    </div>
                );

            case "multiselect":
                return (
                    <div className="col-12" key={field.name}>
                        <div className="mb-3">
                            <label className="form-label fw-semibold">
                                {field.label}{" "}
                                {field.required && (
                                    <span className="text-danger">*</span>
                                )}
                            </label>
                            <Select
                                isMulti
                                options={field.options || []}
                                value={field.options?.filter((opt) =>
                                    (data[field.name] || []).includes(
                                        opt.value,
                                    ),
                                )}
                                onChange={(selectedOptions) => {
                                    const values = selectedOptions
                                        ? selectedOptions.map(
                                              (opt) => opt.value,
                                          )
                                        : [];
                                    setData(field.name, values);
                                }}
                                placeholder={`Select ${field.label}`}
                                isClearable
                                isSearchable
                                menuPortalTarget={document.body}
                                styles={{
                                    control: (base) => ({
                                        ...base,
                                        minHeight: "38px",
                                        borderColor: errors[field.name]
                                            ? "#dc3545"
                                            : "#dee2e6",
                                    }),
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 9999,
                                    }),
                                }}
                            />
                            {field.helpText && (
                                <small className="form-text text-muted d-block mt-1">
                                    {field.helpText}
                                </small>
                            )}
                            {errors[field.name] && (
                                <div
                                    className="text-danger mt-1"
                                    style={{ fontSize: "0.875em" }}
                                >
                                    {Array.isArray(errors[field.name])
                                        ? errors[field.name][0]
                                        : errors[field.name]}
                                </div>
                            )}
                        </div>
                    </div>
                );

            case "select":
                if (field.name === "zone_id") {
                    return (
                        <div className={fieldClass} key={field.name}>
                            <div className="mb-3">
                                <label className="form-label fw-semibold">
                                    {field.label}{" "}
                                    {field.required && (
                                        <span className="text-danger">*</span>
                                    )}
                                </label>
                                <Select
                                    options={zoneOptions}
                                    value={selectedZone}
                                    onChange={handleZoneChange}
                                    placeholder={`Select ${field.label}`}
                                    isClearable
                                    isSearchable
                                    menuPortalTarget={document.body}
                                    styles={{
                                        control: (base) => ({
                                            ...base,
                                            minHeight: "38px",
                                            borderColor: errors[field.name]
                                                ? "#dc3545"
                                                : "#dee2e6",
                                        }),
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                        }),
                                    }}
                                />
                                {errors[field.name] && (
                                    <div
                                        className="text-danger mt-1"
                                        style={{ fontSize: "0.875em" }}
                                    >
                                        {Array.isArray(errors[field.name])
                                            ? errors[field.name][0]
                                            : errors[field.name]}
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                }

                if (field.name === "state_id") {
                    const stateOptions = availableStates.map((s) => ({
                        value: s.id,
                        label: s.name,
                    }));

                    return (
                        <div className={fieldClass} key={field.name}>
                            <div className="mb-3">
                                <label className="form-label fw-semibold">
                                    {field.label}{" "}
                                    {field.required && (
                                        <span className="text-danger">*</span>
                                    )}
                                </label>
                                <Select
                                    options={stateOptions}
                                    value={selectedState}
                                    onChange={handleStateChange}
                                    placeholder={`Select ${field.label}`}
                                    isClearable
                                    isSearchable
                                    menuPortalTarget={document.body}
                                    styles={{
                                        control: (base) => ({
                                            ...base,
                                            minHeight: "38px",
                                            borderColor: errors[field.name]
                                                ? "#dc3545"
                                                : "#dee2e6",
                                        }),
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                        }),
                                    }}
                                />
                                {errors[field.name] && (
                                    <div
                                        className="text-danger mt-1"
                                        style={{ fontSize: "0.875em" }}
                                    >
                                        {Array.isArray(errors[field.name])
                                            ? errors[field.name][0]
                                            : errors[field.name]}
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                }

                if (field.name === "city_id") {
                    return (
                        <div className={fieldClass} key={field.name}>
                            <div className="mb-3">
                                <label className="form-label fw-semibold">
                                    {field.label}{" "}
                                    {field.required && (
                                        <span className="text-danger">*</span>
                                    )}
                                </label>
                                <Select
                                    options={cityOptions}
                                    value={selectedCity}
                                    onChange={handleCityChange}
                                    placeholder={
                                        selectedState
                                            ? `Select ${field.label}`
                                            : "Select state first"
                                    }
                                    isClearable
                                    isSearchable
                                    isDisabled={!selectedState}
                                    menuPortalTarget={document.body}
                                    styles={{
                                        control: (base) => ({
                                            ...base,
                                            minHeight: "38px",
                                            borderColor: errors[field.name]
                                                ? "#dc3545"
                                                : "#dee2e6",
                                        }),
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                        }),
                                    }}
                                />
                                {errors[field.name] && (
                                    <div
                                        className="text-danger mt-1"
                                        style={{ fontSize: "0.875em" }}
                                    >
                                        {Array.isArray(errors[field.name])
                                            ? errors[field.name][0]
                                            : errors[field.name]}
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                }

                if (field.name === "area_id") {
                    return (
                        <div className={fieldClass} key={field.name}>
                            <div className="mb-3">
                                <label className="form-label fw-semibold">
                                    {field.label}{" "}
                                    {field.required && (
                                        <span className="text-danger">*</span>
                                    )}
                                </label>
                                <Select
                                    options={areaOptions}
                                    value={selectedArea}
                                    onChange={handleAreaChange}
                                    placeholder={
                                        selectedCity
                                            ? `Select ${field.label}`
                                            : "Select city first"
                                    }
                                    isClearable
                                    isSearchable
                                    isDisabled={!selectedCity}
                                    menuPortalTarget={document.body}
                                    styles={{
                                        control: (base) => ({
                                            ...base,
                                            minHeight: "38px",
                                            borderColor: errors[field.name]
                                                ? "#dc3545"
                                                : "#dee2e6",
                                        }),
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                        }),
                                    }}
                                />
                                {errors[field.name] && (
                                    <div
                                        className="text-danger mt-1"
                                        style={{ fontSize: "0.875em" }}
                                    >
                                        {Array.isArray(errors[field.name])
                                            ? errors[field.name][0]
                                            : errors[field.name]}
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                }

                // Regular select
                return (
                    <div className={fieldClass} key={field.name}>
                        <div className="mb-3">
                            <label className="form-label fw-semibold">
                                {field.label}{" "}
                                {field.required && (
                                    <span className="text-danger">*</span>
                                )}
                            </label>
                            <Select
                                options={field.options || []}
                                value={
                                    field.options?.find(
                                        (opt) => opt.value === data[field.name],
                                    ) || null
                                }
                                onChange={(option) => {
                                    const value = option?.value || null;
                                    setData(field.name, value);
                                    if (field.onChange) {
                                        field.onChange(value);
                                    }
                                }}
                                placeholder={`Select ${field.label}`}
                                isClearable
                                isSearchable
                                menuPortalTarget={document.body}
                                styles={{
                                    control: (base) => ({
                                        ...base,
                                        minHeight: "38px",
                                        borderColor: errors[field.name]
                                            ? "#dc3545"
                                            : "#dee2e6",
                                    }),
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 9999,
                                    }),
                                }}
                            />
                            {field.helpText && (
                                <small className="form-text text-muted d-block mt-1">
                                    {field.helpText}
                                </small>
                            )}
                            {errors[field.name] && (
                                <div
                                    className="text-danger mt-1"
                                    style={{ fontSize: "0.875em" }}
                                >
                                    {Array.isArray(errors[field.name])
                                        ? errors[field.name][0]
                                        : errors[field.name]}
                                </div>
                            )}
                        </div>
                    </div>
                );

            case "file":
                return (
                    <div className={fieldClass} key={field.name}>
                        <div className="mb-3">
                            <label className="form-label fw-semibold">
                                {field.label}{" "}
                                {field.required && !isEdit && (
                                    <span className="text-danger">*</span>
                                )}
                            </label>
                            <input
                                type="file"
                                className={`form-control ${
                                    errors[field.name] ? "is-invalid" : ""
                                }`}
                                accept={field.accept || "*"}
                                onChange={(e) => {
                                    const file = e.target.files[0];
                                    setData(field.name, file);
                                }}
                            />
                            {field.helpText && (
                                <small className="form-text text-muted d-block mt-1">
                                    {field.helpText}
                                </small>
                            )}
                            {isEdit &&
                                masterData &&
                                masterData[field.name] &&
                                typeof masterData[field.name] === "string" && (
                                    <div className="mt-2">
                                        <a
                                            href={`/storage/${masterData[field.name]}`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="btn btn-sm btn-outline-primary"
                                        >
                                            <i className="fas fa-file me-2"></i>
                                            View Current File
                                        </a>
                                    </div>
                                )}
                            {errors[field.name] && (
                                <div className="invalid-feedback d-block">
                                    {Array.isArray(errors[field.name])
                                        ? errors[field.name][0]
                                        : errors[field.name]}
                                </div>
                            )}
                        </div>
                    </div>
                );

            default:
                return null;
        }
    };

    return (
        <MainLayout user={auth.user} title={title}>
            {/* LIKE MASTERINDEX BACKGROUND */}
            <div
                className="container-fluid py-4"
                style={{ backgroundColor: "#ffffff", minHeight: "100vh" }}
            >
                {/* HEADER */}
                <div className="mb-4">
                    <h2 className="mb-1 fw-bold">
                        {isEdit ? "Edit" : "Add New"} {masterName}
                    </h2>
                    <p className="text-muted mb-0">
                        Fill in the form below to {isEdit ? "update" : "create"}{" "}
                        a {masterName.toLowerCase()}
                    </p>
                </div>

                {/* FORM CARD */}
                <div className="card border-0 shadow-sm">
                    <div className="card-body p-4">
                        <form onSubmit={submit}>
                            <div className="row">
                                {fields.map((field) => renderField(field))}
                            </div>

                            <div className="row">
                                <div className="col-12">
                                    <div className="d-flex gap-2 justify-content-end pt-3 mt-3 border-top">
                                        <button
                                            type="button"
                                            className="btn btn-light px-4"
                                            onClick={() =>
                                                router.visit(viewBase)
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
                                                    <span
                                                        className="spinner-border spinner-border-sm me-2"
                                                        role="status"
                                                        aria-hidden="true"
                                                    ></span>
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
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {/* ALERT MODAL */}
            <AlertModal
                show={alert.show}
                type={alert.type}
                message={alert.message}
                onClose={() => setAlert({ ...alert, show: false })}
            />
        </MainLayout>
    );
}
