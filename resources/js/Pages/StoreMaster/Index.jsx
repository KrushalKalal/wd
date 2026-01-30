import MasterIndex from "../Masters/MasterIndex";
import { useState } from "react";
import axios from "axios";

export default function Index({
    auth,
    records,
    filters,
    states,
    categoryOnes,
    categoryTwos,
    categoryThrees,
}) {
    const [cities, setCities] = useState([]);
    const [areas, setAreas] = useState([]);

    const columns = [
        { key: "name", label: "Store Name", width: "180px" },
        { key: "address", label: "Address", width: "200px" },
        { key: "state.name", label: "State", width: "120px" },
        { key: "city.name", label: "City", width: "120px" },
        { key: "area.name", label: "Area", width: "120px" },
        { key: "category_one.name", label: "Category One", width: "140px" },
        { key: "category_two.name", label: "Category Two", width: "140px" },
        { key: "category_three.name", label: "Category Three", width: "140px" },
        { key: "contact_number_1", label: "Contact", width: "130px" },
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
            masterName="Store Master"
            viewBase="/store-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="store-master.download-template"
            excelImportRoute="/store-master/upload"
            hasStateFilter={true}
            hasCityFilter={true}
            hasAreaFilter={true}
            hasCategoryOneFilter={true}
            hasCategoryTwoFilter={true}
            hasCategoryThreeFilter={true}
            states={states}
            cities={cities}
            areas={areas}
            categoryOnes={categoryOnes}
            categoryTwos={categoryTwos}
            categoryThrees={categoryThrees}
            onStateChange={handleStateChange}
            onCityChange={handleCityChange}
            title="Store Master"
        />
    );
}
