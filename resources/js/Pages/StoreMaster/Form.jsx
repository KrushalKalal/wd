import MasterForm from "../Masters/MasterForm";

export default function Form({
    auth,
    store = null,
    states = [],
    categoryOnes = [],
    categoryTwos = [],
    categoryThrees = [],
}) {
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

    const fields = [
        {
            name: "name",
            label: "Store Name",
            type: "text",
            required: true,
            placeholder: "Enter store name",
        },
        {
            name: "address",
            label: "Address",
            type: "textarea",
            required: false,
            placeholder: "Enter store address",
            rows: 3,
        },
        {
            name: "state_id",
            label: "State",
            type: "select",
            required: true,
        },
        {
            name: "city_id",
            label: "City",
            type: "select",
            required: true,
        },
        {
            name: "area_id",
            label: "Area",
            type: "select",
            required: true,
        },
        {
            name: "pin_code",
            label: "Pin Code",
            type: "text",
            required: false,
            placeholder: "Enter pin code",
        },
        {
            name: "latitude",
            label: "Latitude",
            type: "number",
            required: false,
            placeholder: "Enter latitude",
            step: "any",
        },
        {
            name: "longitude",
            label: "Longitude",
            type: "number",
            required: false,
            placeholder: "Enter longitude",
            step: "any",
        },
        {
            name: "category_one_id",
            label: "Category One",
            type: "select",
            required: false,
            options: cat1Options,
        },
        {
            name: "category_two_id",
            label: "Category Two",
            type: "select",
            required: false,
            options: cat2Options,
        },
        {
            name: "category_three_id",
            label: "Category Three",
            type: "select",
            required: false,
            options: cat3Options,
        },
        {
            name: "contact_number_1",
            label: "Contact Number 1",
            type: "text",
            required: false,
            placeholder: "Enter primary contact number",
        },
        {
            name: "contact_number_2",
            label: "Contact Number 2",
            type: "text",
            required: false,
            placeholder: "Enter secondary contact number",
        },
        {
            name: "email",
            label: "Email",
            type: "email",
            required: false,
            placeholder: "Enter email",
        },
        {
            name: "billing_details",
            label: "Billing Details (JSON)",
            type: "json",
            required: false,
            placeholder: '{"name": "...", "address": "..."}',
            rows: 5,
        },
        {
            name: "shipping_details",
            label: "Shipping Details (JSON)",
            type: "json",
            required: false,
            placeholder: '{"name": "...", "address": "..."}',
            rows: 5,
        },
        {
            name: "manual_stock_entry",
            label: "Manual Stock Entry",
            type: "select",
            required: false,
            options: [
                { value: true, label: "Yes" },
                { value: false, label: "No" },
            ],
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Store Master"
            masterData={store}
            viewBase="/store-masters"
            fields={fields}
            hasStateDropdown={true}
            hasCityDropdown={true}
            hasAreaDropdown={true}
            states={states}
            title="Store Master"
        />
    );
}
