import MasterForm from "../Masters/MasterForm";

export default function Form({
    auth,
    product = null,
    categoryOnes = [],
    categoryTwos = [],
    categoryThrees = [],
    productCategories = [],
}) {
    const cat1Options = categoryOnes.map((c) => ({
        value: c.id,
        label: c.name,
    }));
    const cat2Options = categoryTwos.map((c) => ({
        value: c.id,
        label: c.name,
    }));
    const cat3Options = categoryThrees.map((c) => ({
        value: c.id,
        label: c.name,
    }));
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
            name: "category_one_id",
            label: "Category One",
            type: "select",
            required: true,
            options: cat1Options,
        },
        {
            name: "category_two_id",
            label: "Category Two",
            type: "select",
            required: true,
            options: cat2Options,
        },
        {
            name: "category_three_id",
            label: "Category Three",
            type: "select",
            required: true,
            options: cat3Options,
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
            name: "edo",
            label: "EDO (Expected Delivery Date)",
            type: "date",
            required: false,
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
