import MasterIndex from "../Masters/MasterIndex";
import { useState, useEffect } from "react";
import axios from "axios";

export default function Index({ auth, records, filters, states }) {
    const [cities, setCities] = useState([]);
    console.log(cities);

    const columns = [
        { key: "state.name", label: "State" },
        { key: "name", label: "City Name" },
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
            masterName="City Master"
            viewBase="/city-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="city-master.download-template"
            excelImportRoute="/city-master/upload"
            hasStateFilter={true}
            states={states}
            onStateChange={handleStateChange}
            title="City Master"
        />
    );
}
