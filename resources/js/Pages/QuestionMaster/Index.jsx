import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters }) {
    const columns = [
        { key: "question_text", label: "Question Text" },
        {
            key: "is_count",
            label: "Count Required",
            width: "130px",
            type: "custom",
        },
    ];

    const customRender = (row, col) => {
        if (col.key === "is_count") {
            return row.is_count ? (
                <span className="badge bg-dark">
                    <i className="fas fa-hashtag me-1"></i>
                    Yes
                </span>
            ) : (
                <span className="badge bg-secondary">No</span>
            );
        }
        return null;
    };

    return (
        <MasterIndex
            auth={auth}
            masterName="Question Master"
            viewBase="/question-masters"
            columns={columns}
            data={records}
            filters={filters}
            excelTemplateRoute="question-master.download-template"
            excelImportRoute="/question-master/upload"
            title="Question Master"
            customRender={customRender}
        />
    );
}
