import { useEffect, useRef, useState } from "react";

export default function AddressAutocomplete({
    onAddressSelect,
    defaultValue = "",
    placeholder = "Search address...",
    disabled = false,
}) {
    const inputRef = useRef(null);
    const [inputValue, setInputValue] = useState(defaultValue);
    const autocompleteRef = useRef(null);

    useEffect(() => {
        if (!window.google || !inputRef.current) return;

        autocompleteRef.current = new window.google.maps.places.Autocomplete(
            inputRef.current,
            {
                componentRestrictions: { country: "in" },
                fields: ["address_components", "formatted_address", "geometry"],
            },
        );

        autocompleteRef.current.addListener("place_changed", () => {
            const place = autocompleteRef.current.getPlace();

            // ADD THIS LOG
            console.log("Google Place Result:", place);

            if (!place.address_components) {
                console.log("No address components returned");
                return;
            }

            const get = (type) =>
                place.address_components.find((c) => c.types.includes(type))
                    ?.long_name || "";

            const result = {
                full_address: place.formatted_address,
                area:
                    get("sublocality_level_1") ||
                    get("sublocality") ||
                    get("neighborhood") ||
                    get("locality"),
                city: get("locality"),
                state: get("administrative_area_level_1"),
                pin_code: get("postal_code"),
                latitude: place.geometry?.location?.lat() || null,
                longitude: place.geometry?.location?.lng() || null,
            };

            // ADD THIS LOG
            console.log("Parsed Result:", result);

            setInputValue(place.formatted_address);
            onAddressSelect(result);
        });

        return () => {
            if (autocompleteRef.current) {
                window.google.maps.event.clearInstanceListeners(
                    autocompleteRef.current,
                );
            }
        };
    }, []);

    return (
        <input
            ref={inputRef}
            type="text"
            className="form-control"
            placeholder={placeholder}
            value={inputValue}
            onChange={(e) => setInputValue(e.target.value)}
            disabled={disabled}
            autoComplete="off"
        />
    );
}
