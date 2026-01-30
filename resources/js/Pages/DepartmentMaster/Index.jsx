import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters }) {
    const columns = [{ key: "name", label: "Department Name" }];

    return (
        <MasterIndex
            auth={auth}
            masterName="Department Master"
            viewBase="/department-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="department-master.download-template"
            excelImportRoute="/department-master/upload"
            title="Department Master"
        />
    );
}
