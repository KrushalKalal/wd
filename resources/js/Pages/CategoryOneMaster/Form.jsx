import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, categoryOne = null }) {
    const fields = [
        {
            name: "name",
            label: "Category One Name",
            type: "text",
            required: true,
            placeholder: "Enter category one name",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Category One Master"
            masterData={categoryOne}
            viewBase="/category-one-masters"
            fields={fields}
            title="Category One Master"
        />
    );
}
