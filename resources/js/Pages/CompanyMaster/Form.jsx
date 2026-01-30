import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, company = null, states = [] }) {
    const fields = [
        {
            name: "name",
            label: "Company Name",
            type: "text",
            required: true,
            placeholder: "Enter company name",
        },
        {
            name: "address",
            label: "Address",
            type: "textarea",
            required: false,
            placeholder: "Enter company address",
            rows: 3,
        },
        {
            name: "state_id",
            label: "State",
            type: "select",
            required: false,
        },
        {
            name: "city_id",
            label: "City",
            type: "select",
            required: false,
        },
        {
            name: "area_id",
            label: "Area",
            type: "select",
            required: false,
        },
        {
            name: "pin_code",
            label: "Pin Code",
            type: "text",
            required: false,
            placeholder: "Enter pin code",
        },
        {
            name: "country",
            label: "Country",
            type: "text",
            required: true,
            placeholder: "Enter country",
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
            name: "email_1",
            label: "Email 1",
            type: "email",
            required: false,
            placeholder: "Enter primary email",
        },
        {
            name: "email_2",
            label: "Email 2",
            type: "email",
            required: false,
            placeholder: "Enter secondary email",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Company Master"
            masterData={company}
            viewBase="/company-masters"
            fields={fields}
            hasStateDropdown={true}
            hasCityDropdown={true}
            hasAreaDropdown={true}
            states={states}
            title="Company Master"
        />
    );
}
