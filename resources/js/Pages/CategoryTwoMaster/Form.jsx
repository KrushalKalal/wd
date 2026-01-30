import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, categoryOne = null }) {
    const fields = [
        {
            name: "name",
            label: "Category Two Name",
            type: "text",
            required: true,
            placeholder: "Enter Category Two name",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Category Two Master"
            masterData={categoryOne}
            viewBase="/category-two-masters"
            fields={fields}
            title="Category Two Master"
        />
    );
}
