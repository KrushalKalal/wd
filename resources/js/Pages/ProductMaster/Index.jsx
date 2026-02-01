import MasterIndex from "../Masters/MasterIndex";

export default function Index({
    auth,
    records,
    filters,
    categoryOnes,
    categoryTwos,
    categoryThrees,
    productCategories,
}) {
    console.log(records);
    const columns = [
        { key: "p_category.name", label: "Product Category", width: "150px" },
        { key: "name", label: "Product Name", width: "200px" },
        { key: "mrp", label: "MRP", width: "100px" },
        { key: "edd", label: "EDD", width: "100px" },
        { key: "total_stock", label: "Stock", width: "100px" },
        {
            key: "catalogue_pdf",
            label: "Catalogue",
            width: "110px",
            type: "pdf",
        },
    ];

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
            productCategories={productCategories}
            title="Product Master"
        />
    );
}
