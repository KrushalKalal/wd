import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters }) {
    const columns = [{ key: "name", label: "Product Category Name" }];

    return (
        <MasterIndex
            auth={auth}
            masterName="Product Category Master"
            viewBase="/product-category-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="product-category-master.download-template"
            excelImportRoute="/product-category-master/upload"
            title="Product Category Master"
        />
    );
}
