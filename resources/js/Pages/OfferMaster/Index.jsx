import MasterIndex from "../Masters/MasterIndex";

export default function Index({
    auth,
    records,
    filters,
    categoryOnes,
    categoryTwos,
    categoryThrees,
}) {
    const columns = [
        { key: "offer_type", label: "Offer Type", width: "130px" },
        { key: "offer_title", label: "Title", width: "200px" },
        { key: "category_one.name", label: "Category One", width: "140px" },
        { key: "category_two.name", label: "Category Two", width: "140px" },
        { key: "category_three.name", label: "Category Three", width: "140px" },
        { key: "start_date", label: "Start Date", width: "120px" },
        { key: "end_date", label: "End Date", width: "120px" },
    ];

    return (
        <MasterIndex
            auth={auth}
            masterName="Offer Master"
            viewBase="/offer-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="offer-master.download-template"
            excelImportRoute="/offer-master/upload"
            hasCategoryOneFilter={true}
            hasCategoryTwoFilter={true}
            hasCategoryThreeFilter={true}
            categoryOnes={categoryOnes}
            categoryTwos={categoryTwos}
            categoryThrees={categoryThrees}
            title="Offer Master"
        />
    );
}
