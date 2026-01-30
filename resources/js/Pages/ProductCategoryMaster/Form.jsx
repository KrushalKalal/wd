import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, categoryOne = null }) {
    const fields = [
        {
            name: "name",
            label: "Product Category Name",
            type: "text",
            required: true,
            placeholder: "Enter Product Category name",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Product Category Master"
            masterData={categoryOne}
            viewBase="/product-category-masters"
            fields={fields}
            title="Product Category Master"
        />
    );
}
