import { useState, useEffect } from "react";
import MasterForm from "../Masters/MasterForm";
import axios from "axios";

export default function Form({
    auth,
    offer = null,
    productCategories = [],
    categoryOnes = [],
    categoryTwos = [],
    categoryThrees = [],
    states = [],
    cities = [],
    areas = [],
}) {
    const [offerType, setOfferType] = useState(offer?.offer_type || "");
    const [availableStores, setAvailableStores] = useState([]);
    const [selectedCategories, setSelectedCategories] = useState({
        category_one_id: offer?.category_one_id || "",
        category_two_id: offer?.category_two_id || "",
        category_three_id: offer?.category_three_id || "",
    });

    const pCatOptions = productCategories.map((c) => ({
        value: c.id,
        label: c.name,
    }));
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

    // Load stores when categories change for store_category type
    useEffect(() => {
        if (
            offerType === "store_category" &&
            (selectedCategories.category_one_id ||
                selectedCategories.category_two_id ||
                selectedCategories.category_three_id)
        ) {
            const params = {};
            if (selectedCategories.category_one_id)
                params.category_one_id = selectedCategories.category_one_id;
            if (selectedCategories.category_two_id)
                params.category_two_id = selectedCategories.category_two_id;
            if (selectedCategories.category_three_id)
                params.category_three_id = selectedCategories.category_three_id;

            axios
                .get("/offer-master/stores-by-categories", { params })
                .then((response) => {
                    setAvailableStores(response.data);
                })
                .catch((error) => {
                    console.error("Error loading stores:", error);
                });
        } else {
            setAvailableStores([]);
        }
    }, [
        selectedCategories.category_one_id,
        selectedCategories.category_two_id,
        selectedCategories.category_three_id,
        offerType,
    ]);

    // Base fields that are always shown
    const baseFields = [
        {
            name: "offer_type",
            label: "Offer Type",
            type: "select",
            required: true,
            options: [
                { value: "product_category", label: "Product Category" },
                { value: "store_category", label: "Store Category" },
                { value: "sales_volume", label: "Sales Volume" },
                { value: "location", label: "Location Based" },
            ],
            onChange: (value) => setOfferType(value),
        },
        {
            name: "offer_title",
            label: "Offer Title",
            type: "text",
            required: true,
            placeholder: "Enter offer title",
        },
        {
            name: "description",
            label: "Description",
            type: "textarea",
            required: false,
            placeholder: "Enter offer description",
            rows: 4,
        },
        {
            name: "offer_percentage",
            label: "Offer Percentage (%)",
            type: "number",
            required: true,
            placeholder: "Enter offer percentage",
            step: "0.01",
        },
    ];

    // Conditional fields based on offer type
    let conditionalFields = [];

    if (offerType === "product_category") {
        conditionalFields = [
            {
                name: "p_category_id",
                label: "Product Category",
                type: "select",
                required: true,
                options: pCatOptions,
            },
        ];
    } else if (offerType === "store_category") {
        conditionalFields = [
            {
                name: "category_one_id",
                label: "Store Category 1",
                type: "select",
                required: false,
                options: cat1Options,
                onChange: (value) =>
                    setSelectedCategories((prev) => ({
                        ...prev,
                        category_one_id: value,
                    })),
            },
            {
                name: "category_two_id",
                label: "Store Category 2",
                type: "select",
                required: false,
                options: cat2Options,
                onChange: (value) =>
                    setSelectedCategories((prev) => ({
                        ...prev,
                        category_two_id: value,
                    })),
            },
            {
                name: "category_three_id",
                label: "Store Category 3",
                type: "select",
                required: false,
                options: cat3Options,
                onChange: (value) =>
                    setSelectedCategories((prev) => ({
                        ...prev,
                        category_three_id: value,
                    })),
            },
            {
                name: "store_ids",
                label: "Select Stores",
                type: "multiselect",
                required: true,
                options: availableStores.map((s) => ({
                    value: s.id,
                    label: s.name,
                })),
                helpText:
                    availableStores.length > 0
                        ? `${availableStores.length} stores found matching selected categories`
                        : "Please select at least one category to load stores",
            },
        ];
    } else if (offerType === "sales_volume") {
        conditionalFields = [
            {
                name: "min_sales_amount",
                label: "Minimum Sales Amount",
                type: "number",
                required: true,
                placeholder: "Enter minimum sales amount",
                step: "0.01",
            },
            // {
            //     name: "max_sales_amount",
            //     label: "Maximum Sales Amount",
            //     type: "number",
            //     required: false,
            //     placeholder: "Enter maximum sales amount (optional)",
            //     step: "0.01",
            // },
        ];
    } else if (offerType === "location") {
        conditionalFields = [
            {
                name: "state_id",
                label: "State",
                type: "select",
                required: false,
                options: states.map((s) => ({ value: s.id, label: s.name })),
            },
            {
                name: "city_id",
                label: "City",
                type: "select",
                required: false,
                options: [], // Will be loaded dynamically
            },
            {
                name: "area_id",
                label: "Area",
                type: "select",
                required: false,
                options: [], // Will be loaded dynamically
            },
        ];
    }

    // Date fields - always shown at the end
    const dateFields = [
        {
            name: "start_date",
            label: "Start Date",
            type: "date",
            required: true,
        },
        {
            name: "end_date",
            label: "End Date",
            type: "date",
            required: true,
        },
    ];

    const allFields = [...baseFields, ...conditionalFields, ...dateFields];

    return (
        <MasterForm
            auth={auth}
            masterName="Offer Master"
            masterData={offer}
            viewBase="/offer-masters"
            fields={allFields}
            hasStateDropdown={offerType === "location"}
            hasCityDropdown={offerType === "location"}
            hasAreaDropdown={offerType === "location"}
            states={states}
            title="Offer Master"
        />
    );
}
