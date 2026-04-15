import { useState, useEffect, useRef } from "react";
import AddressAutocomplete from "./AddressAutocomplete";
import axios from "axios";
import Select from "react-select";

export default function AddressSection({
    address = "",
    areaId = null,
    pinCode = "",
    userLocation = {},
    locationLocks = {},
    onChange,
    errors = {},
    simple = false,
    label = "Address",
    // Branch restriction — if set, address state must match
    restrictStateName = null,
}) {
    const [zones, setZones] = useState([]);
    const [selectedZone, setSelectedZone] = useState(null);
    const [resolvedState, setResolvedState] = useState(null);
    const [resolvedCity, setResolvedCity] = useState(null);
    const [resolvedPinCode, setResolvedPinCode] = useState(pinCode || "");
    const [locationError, setLocationError] = useState(null);
    const [resolving, setResolving] = useState(false);
    const [needsZone, setNeedsZone] = useState(false);
    const selectedZoneRef = useRef(null);
    const [resolvedArea, setResolvedArea] = useState(null);
    const userLocationRef = useRef(userLocation);
    useEffect(() => {
        userLocationRef.current = userLocation;
    }, [userLocation]);

    // Load zones for Master Admin / Country Head
    useEffect(() => {
        if (simple) return;
        if (!locationLocks.zone_id) {
            axios.get("/api/zones").then((res) => {
                setZones(res.data);
            });
        }
    }, [locationLocks.zone_id, simple]);

    // Auto-select zone in dropdown when branch fills userLocation.zone_id
    useEffect(() => {
        if (simple) return;
        if (locationLocks.zone_id) return;
        if (userLocation.zone_id && userLocation.zone_name) {
            const option = {
                value: userLocation.zone_id,
                label: userLocation.zone_name,
            };
            setSelectedZone(option);
            selectedZoneRef.current = option;
            setNeedsZone(false);
            setLocationError(null);
        }
    }, [userLocation.zone_id, userLocation.zone_name]);

    // If city is locked — pre-fill state/city display
    useEffect(() => {
        if (simple) return;
        if (locationLocks.city_id && userLocation.city_id) {
            setResolvedState({
                name: userLocation.state_name,
                id: userLocation.state_id,
            });
            setResolvedCity({
                name: userLocation.city_name,
                id: userLocation.city_id,
            });
        }
    }, [locationLocks.city_id, userLocation.city_id, simple]);

    // If state is locked — set resolved state display
    useEffect(() => {
        if (simple) return;
        if (locationLocks.state_id && userLocation.state_id) {
            setResolvedState({
                name: userLocation.state_name,
                id: userLocation.state_id,
            });
        }
    }, [locationLocks.state_id, userLocation.state_id, simple]);

    const handleZoneChange = (option) => {
        setSelectedZone(option);
        selectedZoneRef.current = option;
        setNeedsZone(false);
        setLocationError(null);
        setResolvedState(null);
        setResolvedCity(null);
        setResolvedArea(null);
        onChange({
            zone_id: option?.value || null,
            state_id: null,
            city_id: null,
            area_id: null,
        });
    };

    const handleAddressSelect = async (result) => {
        console.log("result.state:", result.state);
        console.log(
            "userLocationRef.state_name:",
            userLocationRef.current.state_name,
        );
        setLocationError(null);
        setResolvedPinCode(result.pin_code || "");

        if (simple) {
            onChange({
                address: result.full_address,
                latitude: result.latitude,
                longitude: result.longitude,
            });
            return;
        }

        // Determine zone_id
        let zoneId = null;
        if (locationLocks.zone_id) {
            zoneId = userLocationRef.current.zone_id;
        } else {
            zoneId =
                selectedZoneRef.current?.value ||
                userLocationRef.current.zone_id ||
                null;
            if (!zoneId) {
                setNeedsZone(true);
                setLocationError(
                    "Please select a zone first before searching address.",
                );
                return;
            }
        }

        // State restriction — branch selected or role locked
        if (userLocationRef.current.state_name) {
            if (
                result.state.toLowerCase() !==
                userLocationRef.current.state_name.toLowerCase()
            ) {
                setLocationError(
                    `This address is in ${result.state}. Please search address within ${userLocationRef.current.state_name}.`,
                );
                return;
            }
        }

        if (locationLocks.city_id && userLocationRef.current.city_id) {
            setResolvedState({
                name: userLocationRef.current.state_name,
                id: userLocationRef.current.state_id,
            });
            setResolvedCity({
                name: userLocationRef.current.city_name,
                id: userLocationRef.current.city_id,
            });
        }

        setResolving(true);
        try {
            const res = await axios.get("/api/resolve-location", {
                params: {
                    state_name: result.state,
                    city_name: result.city,
                    area_name: result.area,
                    zone_id: zoneId,
                },
            });

            if (!res.data.success) {
                setLocationError(res.data.error);
                onChange({ address: result.full_address });
                return;
            }

            const resolved = res.data;
            setResolvedState({
                name: resolved.state_name,
                id: resolved.state_id,
            });
            setResolvedCity({ name: resolved.city_name, id: resolved.city_id });
            setResolvedArea({ name: resolved.area_name, id: resolved.area_id });

            onChange({
                address: result.full_address,
                state_id: resolved.state_id,
                city_id: resolved.city_id,
                area_id: resolved.area_id,
                zone_id: resolved.zone_id,
                pin_code: result.pin_code,
                latitude: result.latitude,
                longitude: result.longitude,
            });
        } catch (err) {
            if (err.response?.status === 403) {
                setLocationError(err.response.data.error);
            } else {
                setLocationError(
                    "Failed to resolve location. Please try again.",
                );
            }
            onChange({ address: result.full_address });
        } finally {
            setResolving(false);
        }
    };

    const zoneOptions = zones.map((z) => ({ value: z.id, label: z.name }));

    const selectStyles = {
        control: (base) => ({
            ...base,
            minHeight: "31px",
            height: "31px",
            fontSize: "0.875rem",
            borderColor: "#dee2e6",
        }),
        menuPortal: (base) => ({ ...base, zIndex: 9999 }),
        valueContainer: (base) => ({ ...base, padding: "0 8px" }),
        indicatorsContainer: (base) => ({ ...base, height: "31px" }),
    };

    return (
        <div className="col-12">
            {/* Zone */}
            {!simple && (
                <div className="mb-3">
                    {locationLocks.zone_id ? (
                        <div className="row g-2 mb-2">
                            <div className="col-md-3">
                                <label className="form-label small text-muted fw-semibold">
                                    Zone{" "}
                                    <i
                                        className="fas fa-lock ms-1 text-warning"
                                        title="Auto-filled from your profile"
                                    ></i>
                                </label>
                                <input
                                    type="text"
                                    className="form-control form-control-sm bg-light"
                                    value={userLocation.zone_name || ""}
                                    readOnly
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="row g-2 mb-2">
                            <div className="col-md-4">
                                <label className="form-label fw-semibold">
                                    Zone <span className="text-danger">*</span>
                                </label>
                                <Select
                                    options={zoneOptions}
                                    value={selectedZone}
                                    onChange={handleZoneChange}
                                    placeholder="Select zone first..."
                                    isClearable
                                    menuPortalTarget={document.body}
                                    styles={{
                                        control: (base) => ({
                                            ...base,
                                            minHeight: "38px",
                                            borderColor: needsZone
                                                ? "#dc3545"
                                                : "#dee2e6",
                                        }),
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                        }),
                                    }}
                                />
                                {needsZone && (
                                    <div
                                        className="text-danger mt-1"
                                        style={{ fontSize: "0.875em" }}
                                    >
                                        Please select a zone before searching
                                        address
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Address Search */}
            <div className="mb-3">
                <label className="form-label fw-semibold">
                    {label} {!simple && <span className="text-danger">*</span>}
                </label>
                <AddressAutocomplete
                    onAddressSelect={handleAddressSelect}
                    defaultValue={address}
                    placeholder="Search address on Google Maps..."
                />
                {resolving && (
                    <small className="text-muted d-block mt-1">
                        <span
                            className="spinner-border spinner-border-sm me-1"
                            role="status"
                        ></span>
                        Resolving location...
                    </small>
                )}
                {locationError && (
                    <div className="alert alert-danger py-2 mt-2 mb-0">
                        <i className="fas fa-exclamation-triangle me-2"></i>
                        {locationError}
                    </div>
                )}
                {errors.address && (
                    <div
                        className="text-danger mt-1"
                        style={{ fontSize: "0.875em" }}
                    >
                        {errors.address}
                    </div>
                )}
            </div>

            {/* Resolved fields */}
            {!simple && (resolvedState || locationLocks.state_id) && (
                <div className="row g-2 mb-3">
                    <div className="col-md-3">
                        <label className="form-label small text-muted fw-semibold">
                            State
                            {locationLocks.state_id && (
                                <i
                                    className="fas fa-lock ms-1 text-warning"
                                    title="Locked"
                                ></i>
                            )}
                        </label>
                        <input
                            type="text"
                            className="form-control form-control-sm bg-light"
                            value={
                                locationLocks.state_id
                                    ? userLocation.state_name || ""
                                    : resolvedState?.name || ""
                            }
                            readOnly
                        />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label small text-muted fw-semibold">
                            City
                            {locationLocks.city_id && (
                                <i
                                    className="fas fa-lock ms-1 text-warning"
                                    title="Locked"
                                ></i>
                            )}
                        </label>
                        <input
                            type="text"
                            className="form-control form-control-sm bg-light"
                            value={
                                locationLocks.city_id
                                    ? userLocation.city_name || ""
                                    : resolvedCity?.name || ""
                            }
                            readOnly
                        />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label small text-muted fw-semibold">
                            Area
                        </label>
                        <input
                            type="text"
                            className="form-control form-control-sm bg-light"
                            value={resolvedArea?.name || ""}
                            readOnly
                        />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label small text-muted fw-semibold">
                            Pin Code
                        </label>
                        <input
                            type="text"
                            className="form-control form-control-sm bg-light"
                            value={resolvedPinCode}
                            readOnly
                        />
                    </div>
                </div>
            )}
        </div>
    );
}
