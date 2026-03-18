import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters }) {
    const columns = [
        { key: "name", label: "Company Name", width: "200px" },
        { key: "state.name", label: "State", width: "120px" },
        { key: "city.name", label: "City", width: "120px" },
        { key: "area.name", label: "Area", width: "120px" },
        { key: "contact_number_1", label: "Contact", width: "130px" },
        { key: "email_1", label: "Email", width: "180px" },
    ];

    return (
        <MasterIndex
            auth={auth}
            masterName="Company Master"
            viewBase="/company-masters"
            columns={columns}
            data={records}
            filters={filters}
            hideAddButton={true}
            hideDelete={true}
            title="Company Master"
        />
    );
}
