import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters }) {
    const columns = [{ key: "name", label: "Category Two Name" }];

    return (
        <MasterIndex
            auth={auth}
            masterName="Category Two Master"
            viewBase="/category-two-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="category-two-master.download-template"
            excelImportRoute="/category-two-master/upload"
            title="Category Two Master"
        />
    );
}
