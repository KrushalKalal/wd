import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters }) {
    const columns = [{ key: "name", label: "Category One Name" }];

    return (
        <MasterIndex
            auth={auth}
            masterName="Category One Master"
            viewBase="/category-one-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="category-one-master.download-template"
            excelImportRoute="/category-one-master/upload"
            title="Category One Master"
        />
    );
}
