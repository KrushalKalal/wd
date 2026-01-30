import MasterIndex from "../Masters/MasterIndex";
import { useState } from "react";
import axios from "axios";

export default function Index({
    auth,
    records,
    filters,
    stores,
    products,
    states,
}) {
    const [cities, setCities] = useState([]);
    const [areas, setAreas] = useState([]);

    const columns = [
        { key: "store.name", label: "Store Name", width: "200px" },
        { key: "product.name", label: "Product Name", width: "200px" },
        { key: "store.state.name", label: "State", width: "120px" },
        { key: "store.city.name", label: "City", width: "120px" },
        { key: "store.area.name", label: "Area", width: "120px" },
        { key: "current_stock", label: "Current Stock", width: "130px" },
        {
            key: "pending_stock",
            label: "Pending (+)",
            width: "120px",
            type: "badge",
            color: "warning",
        },
        {
            key: "return_stock",
            label: "Return (-)",
            width: "120px",
            type: "badge",
            color: "danger",
        },
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
            masterName="Store Product Management"
            viewBase="/store-products"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="store-product.download-template"
            excelImportRoute="/store-product/upload"
            hasToggle={false}
            hasStoreFilter={true}
            hasProductFilter={true}
            hasStateFilter={true}
            hasCityFilter={true}
            hasAreaFilter={true}
            stores={stores}
            products={products}
            states={states}
            cities={cities}
            areas={areas}
            onStateChange={handleStateChange}
            onCityChange={handleCityChange}
            title="Store Product Management"
        />
    );
}
