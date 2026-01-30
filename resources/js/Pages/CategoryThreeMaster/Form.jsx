import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, categoryOne = null }) {
    const fields = [
        {
            name: "name",
            label: "Category Three Name",
            type: "text",
            required: true,
            placeholder: "Enter Category Three name",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Category Three Master"
            masterData={categoryOne}
            viewBase="/category-three-masters"
            fields={fields}
            title="Category Three Master"
        />
    );
}
