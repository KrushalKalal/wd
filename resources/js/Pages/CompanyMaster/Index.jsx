import MasterIndex from "../Masters/MasterIndex";
import { useState } from "react";
import axios from "axios";

export default function Index({ auth, records, filters, states }) {
    const [cities, setCities] = useState([]);
    const [areas, setAreas] = useState([]);

    const columns = [
        { key: "name", label: "Company Name", width: "200px" },
        { key: "state.name", label: "State", width: "120px" },
        { key: "city.name", label: "City", width: "120px" },
        { key: "area.name", label: "Area", width: "120px" },
        { key: "contact_number_1", label: "Contact", width: "130px" },
        { key: "email_1", label: "Email", width: "180px" },
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

    const handleCityChange = (cityOption) => {
        if (cityOption?.value) {
            axios.get(`/areas/${cityOption.value}`).then((res) => {
                setAreas(res.data);
            });
        } else {
            setAreas([]);
        }
    };

    return (
        <MasterIndex
            auth={auth}
            masterName="Company Master"
            viewBase="/company-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="company-master.download-template"
            excelImportRoute="/company-master/upload"
            hasStateFilter={true}
            hasCityFilter={true}
            hasAreaFilter={true}
            states={states}
            cities={cities}
            areas={areas}
            onStateChange={handleStateChange}
            onCityChange={handleCityChange}
            title="Company Master"
        />
    );
}
