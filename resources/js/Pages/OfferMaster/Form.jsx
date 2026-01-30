import MasterForm from "../Masters/MasterForm";

export default function Form({
    auth,
    offer = null,
    categoryOnes = [],
    categoryTwos = [],
    categoryThrees = [],
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

    const fields = [
        {
            name: "offer_type",
            label: "Offer Type",
            type: "select",
            required: true,
            options: [
                { value: "category", label: "Category" },
                { value: "Group", label: "Group" },
                { value: "sales_volume", label: "Sales Volume" },
            ],
        },
        {
            name: "category_one_id",
            label: "Category One",
            type: "select",
            required: false,
            options: cat1Options,
        },
        {
            name: "category_two_id",
            label: "Category Two",
            type: "select",
            required: false,
            options: cat2Options,
        },
        {
            name: "category_three_id",
            label: "Category Three",
            type: "select",
            required: false,
            options: cat3Options,
        },
        {
            name: "min_quantity",
            label: "Minimum Quantity",
            type: "number",
            required: false,
            placeholder: "Enter minimum quantity",
        },
        {
            name: "max_quantity",
            label: "Maximum Quantity",
            type: "number",
            required: false,
            placeholder: "Enter maximum quantity",
        },
        {
            name: "offer_title",
            label: "Offer Title",
            type: "text",
            required: true,
            placeholder: "Enter offer title",
        },
        {
            name: "description",
            label: "Description",
            type: "textarea",
            required: false,
            placeholder: "Enter offer description",
            rows: 4,
        },
        {
            name: "start_date",
            label: "Start Date",
            type: "date",
            required: true,
        },
        {
            name: "end_date",
            label: "End Date",
            type: "date",
            required: true,
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Offer Master"
            masterData={offer}
            viewBase="/offer-masters"
            fields={fields}
            title="Offer Master"
        />
    );
}
