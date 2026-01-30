import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, question = null }) {
    const fields = [
        {
            name: "question_text",
            label: "Question Text",
            type: "textarea",
            required: true,
            placeholder: "Enter question here...",
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
