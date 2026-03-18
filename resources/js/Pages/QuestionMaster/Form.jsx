import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, question = null }) {
    const fields = [
        {
            name: "question_text",
            label: "Question Text",
            type: "textarea",
            required: true,
            placeholder: "Enter question here...",
            fullWidth: true,
        },
        {
            name: "is_count",
            label: "Require Count Input from Employee",
            type: "checkbox",
            helpText:
                "If enabled, employee must enter a numeric count when answering this question",
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Question Master"
            masterData={question}
            viewBase="/question-masters"
            fields={fields}
            title="Question Master"
        />
    );
}
