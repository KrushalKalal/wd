import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, state = null }) {
    const fields = [
        {
            name: "name",
            label: "State Name",
            type: "text",
            required: true,
            placeholder: "Enter state name",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="State Master"
            masterData={state}
            viewBase="/state-masters"
            fields={fields}
            title="State Master"
        />
    );
}
