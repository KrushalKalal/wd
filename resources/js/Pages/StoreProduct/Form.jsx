import MasterForm from "../Masters/MasterForm";

export default function Form({
    auth,
    storeProduct = null,
    stores = [],
    products = [],
}) {
    const storeOptions = stores.map((s) => ({
        value: s.id,
        label: s.name,
    }));

    const productOptions = products.map((p) => ({
        value: p.id,
        label: p.name,
    }));

    const fields = [
        {
            name: "store_id",
            label: "Store",
            type: "select",
            required: true,
            options: storeOptions,
        },
        {
            name: "product_id",
            label: "Product",
            type: "select",
            required: true,
            options: productOptions,
        },
        {
            name: "current_stock",
            label: "Current Stock",
            type: "number",
            required: true,
            placeholder: "Enter current stock quantity",
            step: "1",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Store Product"
            masterData={storeProduct}
            viewBase="/store-products"
            fields={fields}
            title="Store Product Management"
        />
    );
}
