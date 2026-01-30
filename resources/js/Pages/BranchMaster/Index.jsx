import MasterIndex from "../Masters/MasterIndex";
import { useState } from "react";
import axios from "axios";

export default function Index({ auth, records, filters, companies, states }) {
    const [cities, setCities] = useState([]);
    const [areas, setAreas] = useState([]);

    const columns = [
        { key: "company.name", label: "Company", width: "180px" },
        { key: "name", label: "Branch Name", width: "180px" },
        { key: "state.name", label: "State", width: "120px" },
        { key: "city.name", label: "City", width: "120px" },
        { key: "contact_number_1", label: "Contact", width: "130px" },
        { key: "email", label: "Email", width: "180px" },
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
            masterName="Branch Master"
            viewBase="/branch-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="branch-master.download-template"
            excelImportRoute="/branch-master/upload"
            hasCompanyFilter={true}
            hasStateFilter={true}
            hasCityFilter={true}
            hasAreaFilter={true}
            states={states}
            cities={cities}
            areas={areas}
            onStateChange={handleStateChange}
            onCityChange={handleCityChange}
            title="Branch Master"
        />
    );
}
