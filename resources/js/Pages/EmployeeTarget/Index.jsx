import MasterIndex from "../Masters/MasterIndex";

export default function Index({ auth, records, filters, employees }) {
    const columns = [
        { key: "employee.name", label: "Employee", width: "180px" },
        { key: "month_name", label: "Month", width: "120px" },
        { key: "year", label: "Year", width: "100px" },
        { key: "visit_target", label: "Visit Target", width: "120px" },
        { key: "visits_completed", label: "Visits Done", width: "120px" },
        {
            key: "visit_completion",
            label: "Visit %",
            width: "100px",
            type: "badge",
            color: "info",
        },
        { key: "sales_target", label: "Sales Target", width: "130px" },
        { key: "sales_achieved", label: "Sales Done", width: "130px" },
        {
            key: "sales_completion",
            label: "Sales %",
            width: "100px",
            type: "badge",
            color: "success",
        },
        { key: "status", label: "Status", width: "120px", type: "badge" },
    ];

    // Custom filters (will be added to MasterIndex)
    const hasEmployeeFilter = true;
    const hasMonthFilter = true;
    const hasYearFilter = true;
    const hasStatusFilter = true;

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

    const statusOptions = [
        { value: "pending", label: "Pending" },
        { value: "in_progress", label: "In Progress" },
        { value: "achieved", label: "Achieved" },
        { value: "missed", label: "Missed" },
    ];

    const currentYear = new Date().getFullYear();
    const yearOptions = Array.from({ length: 10 }, (_, i) => ({
        value: currentYear - i,
        label: (currentYear - i).toString(),
    }));

    return (
        <MasterIndex
            auth={auth}
            masterName="Employee Target"
            viewBase="/employee-targets"
            columns={columns}
            data={records}
            filters={filters}
            hasToggle={false}
            // Custom Filters (need to add support in MasterIndex)
            hasEmployeeFilter={true}
            hasMonthFilter={true}
            hasYearFilter={true}
            hasStatusFilter={true}
            employees={employees}
            monthOptions={monthOptions}
            yearOptions={yearOptions}
            statusOptions={statusOptions}
            title="Employee Target Management"
        />
    );
}
