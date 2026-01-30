import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, area = null, states = [] }) {
    const fields = [
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
            name: "name",
            label: "Area Name",
            type: "text",
            required: true,
            placeholder: "Enter area name",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Area Master"
            masterData={area}
            viewBase="/area-masters"
            fields={fields}
            hasStateDropdown={true}
            hasCityDropdown={true}
            states={states}
            title="Area Master"
        />
    );
}
