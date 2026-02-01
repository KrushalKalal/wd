import MasterIndex from "../Masters/MasterIndex";
import { useState } from "react";
import axios from "axios";

export default function Index({ auth, records, filters, zones }) {
    const [states, setStates] = useState([]);

    const columns = [
        { key: "zone.name", label: "Zone" },
        { key: "name", label: "State Name" },
    ];

    const handleZoneChange = (zoneOption) => {
        if (zoneOption?.value) {
            axios.get(`/states/${zoneOption.value}`).then((res) => {
                setStates(res.data);
            });
        } else {
            setStates([]);
        }
    };

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
            hasZoneFilter={true}
            zones={zones}
            onZoneChange={handleZoneChange}
            title="State Master"
        />
    );
}
