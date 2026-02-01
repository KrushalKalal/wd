import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters }) {
    const columns = [{ key: "name", label: "Zone Name" }];

    return (
        <MasterIndex
            auth={auth}
            masterName="Zone Master"
            viewBase="/zone-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="zone-master.download-template"
            excelImportRoute="/zone-master/upload"
            title="Zone Master"
        />
    );
}
