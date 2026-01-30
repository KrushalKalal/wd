import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters }) {
    const columns = [{ key: "name", label: "State Name" }];

    return (
        <MasterIndex
            auth={auth}
            masterName="State Master"
            viewBase="/state-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="state-master.download-template"
            excelImportRoute="/state-master/upload"
            title="State Master"
        />
    );
}
