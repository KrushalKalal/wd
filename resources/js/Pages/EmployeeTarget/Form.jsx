import MasterForm from "../Masters/MasterForm";

export default function Form({ auth, target = null, employees = [] }) {
    const employeeOptions = employees.map((e) => ({
        value: e.id,
        label: e.name,
    }));

    const monthOptions = [
        { value: 1, label: "January" },
        { value: 2, label: "February" },
        { value: 3, label: "March" },
        { value: 4, label: "April" },
        { value: 5, label: "May" },
        { value: 6, label: "June" },
        { value: 7, label: "July" },
        { value: 8, label: "August" },
        { value: 9, label: "September" },
        { value: 10, label: "October" },
        { value: 11, label: "November" },
        { value: 12, label: "December" },
    ];

    const currentYear = new Date().getFullYear();
    const yearOptions = Array.from({ length: 10 }, (_, i) => ({
        value: currentYear - 5 + i,
        label: (currentYear - 5 + i).toString(),
    }));

    const fields = [
        {
            name: "employee_id",
            label: "Employee",
            type: "select",
            required: true,
            options: employeeOptions,
        },
        {
            name: "month",
            label: "Month",
            type: "select",
            required: true,
            options: monthOptions,
        },
        {
            name: "year",
            label: "Year",
            type: "select",
            required: true,
            options: yearOptions,
        },
        {
            name: "visit_target",
            label: "Visit Target",
            type: "number",
            required: true,
            placeholder: "Enter visit target (number of visits)",
            step: "1",
        },
        {
            name: "sales_target",
            label: "Sales Target (Amount)",
            type: "number",
            required: true,
            placeholder: "Enter sales target amount",
            step: "0.01",
        },
    ];

    // If editing, add current progress fields
    if (target) {
        fields.push(
            {
                name: "visits_completed",
                label: "Visits Completed",
                type: "number",
                placeholder: "Number of visits completed",
                step: "1",
            },
            {
                name: "sales_achieved",
                label: "Sales Achieved (Amount)",
                type: "number",
                placeholder: "Sales amount achieved",
                step: "0.01",
            },
        );
    }

    return (
        <MasterForm
            auth={auth}
            masterName="Employee Target"
            masterData={target}
            viewBase="/employee-targets"
            fields={fields}
            title="Employee Target Management"
        />
    );
}
