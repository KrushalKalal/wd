import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, city = null, states = [] }) {
    const fields = [
        {
            name: "state_id",
            label: "State",
            type: "select",
            required: true,
        },
        {
            name: "name",
            label: "City Name",
            type: "text",
            required: true,
            placeholder: "Enter city name",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="City Master"
            masterData={city}
            viewBase="/city-masters"
            fields={fields}
            hasStateDropdown={true}
            states={states}
            title="City Master"
        />
    );
}
