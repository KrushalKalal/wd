import MasterIndex from "../Masters/MasterIndex";
import { useState } from "react";
import axios from "axios";

export default function Index({ auth, records, filters, states }) {
    const [cities, setCities] = useState([]);

    const columns = [
        { key: "state.name", label: "State" },
        { key: "city.name", label: "City" },
        { key: "name", label: "Area Name" },
    ];

    const handleStateChange = (stateOption) => {
        if (stateOption?.value) {
            axios.get(`/cities/${stateOption.value}`).then((res) => {
                setCities(res.data);
            });
        } else {
            setCities([]);
        }
    };

    return (
        <MasterIndex
            auth={auth}
            masterName="Area Master"
            viewBase="/area-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="area-master.download-template"
            excelImportRoute="/area-master/upload"
            hasStateFilter={true}
            hasCityFilter={true}
            states={states}
            cities={cities}
            onStateChange={handleStateChange}
            title="Area Master"
        />
    );
}
