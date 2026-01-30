import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters }) {
    const columns = [{ key: "name", label: "Category Three Name" }];

    return (
        <MasterIndex
            auth={auth}
            masterName="Category Three Master"
            viewBase="/category-three-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="category-three-master.download-template"
            excelImportRoute="/category-three-master/upload"
            title="Category Three Master"
        />
    );
}
