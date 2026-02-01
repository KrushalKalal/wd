import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, zone = null }) {
    const fields = [
        {
            name: "name",
            label: "Zone Name",
            type: "text",
            required: true,
            placeholder: "Enter zone name",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Zone Master"
            masterData={zone}
            viewBase="/zone-masters"
            fields={fields}
            title="Zone Master"
        />
    );
}
