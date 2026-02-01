import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, product = null, productCategories = [] }) {
    const pCatOptions = productCategories.map((c) => ({
        value: c.id,
        label: c.name,
    }));

    const fields = [
        {
            name: "name",
            label: "Product Name",
            type: "text",
            required: true,
            placeholder: "Enter product name",
        },
        {
            name: "p_category_id",
            label: "Product Category",
            type: "select",
            required: true,
            options: pCatOptions,
        },
        {
            name: "mrp",
            label: "MRP (₹)",
            type: "number",
            required: true,
            placeholder: "Enter MRP",
            step: "0.01",
        },
        {
            name: "edd",
            label: "EDD",
            type: "number",
            required: false,
            placeholder: "Enter EDD",
            step: "0.01",
        },
        {
            name: "total_stock",
            label: "Total Stock",
            type: "number",
            required: false,
            placeholder: "Enter total stock",
            step: "1",
        },
        {
            name: "catalogue_pdf",
            label: "Product Catalogue PDF",
            type: "file",
            required: false,
            accept: ".pdf",
            helpText: "Upload product catalogue in PDF format (Max: 10MB)",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Product Master"
            masterData={product}
            viewBase="/product-masters"
            fields={fields}
            title="Product Master"
        />
    );
}
