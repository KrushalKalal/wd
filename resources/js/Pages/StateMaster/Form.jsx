import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, state = null, zones = [] }) {
    const fields = [
        {
            name: "zone_id",
            label: "Zone",
            type: "select",
            required: true,
        },
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
            hasZoneDropdown={true}
            zones={zones}
            title="State Master"
        />
    );
}
