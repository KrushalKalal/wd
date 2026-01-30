import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, department = null }) {
    const fields = [
        {
            name: "name",
            label: "Department Name",
            type: "text",
            required: true,
            placeholder: "Enter department name",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Department Master"
            masterData={department}
            viewBase="/department-masters"
            fields={fields}
            title="Department Master"
        />
    );
}
