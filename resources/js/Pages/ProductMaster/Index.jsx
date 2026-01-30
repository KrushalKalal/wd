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
        { key: "name", label: "Product Name", width: "200px" },
        { key: "category_one.name", label: "Category One", width: "140px" },
        { key: "category_two.name", label: "Category Two", width: "140px" },
        { key: "category_three.name", label: "Category Three", width: "140px" },
        { key: "p_category.name", label: "Product Category", width: "150px" },
        { key: "mrp", label: "MRP", width: "100px" },
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
            hasCategoryOneFilter={true}
            hasCategoryTwoFilter={true}
            hasCategoryThreeFilter={true}
            hasProductCategoryFilter={true}
            categoryOnes={categoryOnes}
            categoryTwos={categoryTwos}
            categoryThrees={categoryThrees}
            productCategories={productCategories}
            title="Product Master"
        />
    );
}
