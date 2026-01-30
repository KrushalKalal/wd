import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters }) {
    const columns = [{ key: "question_text", label: "Question Text" }];

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
        />
    );
}
