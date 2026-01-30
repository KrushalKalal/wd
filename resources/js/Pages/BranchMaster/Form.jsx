import MasterForm from "../Masters/MasterForm";

export default function Form({
    auth,
    branch = null,
    companies = [],
    states = [],
}) {
    const companyOptions = companies.map((c) => ({
        value: c.id,
        label: c.name,
    }));

    const fields = [
        {
            name: "company_id",
            label: "Company",
            type: "select",
            required: true,
            options: companyOptions,
        },
        {
            name: "name",
            label: "Branch Name",
            type: "text",
            required: true,
            placeholder: "Enter branch name",
        },
        {
            name: "address",
            label: "Address",
            type: "textarea",
            required: false,
            placeholder: "Enter branch address",
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
            name: "email",
            label: "Email",
            type: "email",
            required: false,
            placeholder: "Enter email",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Branch Master"
            masterData={branch}
            viewBase="/branch-masters"
            fields={fields}
            hasStateDropdown={true}
            hasCityDropdown={true}
            hasAreaDropdown={true}
            states={states}
            title="Branch Master"
        />
    );
}
