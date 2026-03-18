import MasterIndex from "../Masters/MasterIndex";

export default function Index({
    auth,
    records,
    filters,
    productCategories,
    states,
}) {
    const columns = [
        { key: "state.name", label: "State", width: "120px" },
        { key: "p_category.name", label: "Category", width: "140px" },
        { key: "sku", label: "SKU", width: "120px" },
        { key: "name", label: "Product Name", width: "180px" },
        { key: "mrp", label: "Price", width: "90px" },
        { key: "pack_size", label: "Pack Size", width: "90px" },
        { key: "volume", label: "Volume", width: "90px" },
        { key: "total_stock", label: "Stock", width: "80px" },
        {
            key: "image",
            label: "Image",
            width: "80px",
            type: "custom",
        },
        {
            key: "catalogue_pdf",
            label: "Catalogue",
            width: "100px",
            type: "pdf",
        },
    ];

    const customRender = (row, col) => {
        if (col.key === "image") {
            return row.image ? (
                <a
                    href={`/storage/${row.image}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn btn-sm btn-dark text-white"
                    title="View Image"
                >
                    <i className="fas fa-image"></i>
                </a>
            ) : (
                <span className="text-muted">—</span>
            );
        }
        return null;
    };

    return (
        <MasterIndex
            auth={auth}
            masterName="Product Master"
            viewBase="/product-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="product-master.download-template"
            excelImportRoute="/product-master/upload"
            hasProductCategoryFilter={true}
            hasStateFilter={true}
            productCategories={productCategories}
            states={states}
            title="Product Master"
            customRender={customRender}
        />
    );
}
