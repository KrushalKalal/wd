import MasterForm from "../Masters/MasterForm";

export default function Form({
    auth,
    employee = null,
    companies = [],
    branches = [],
    departments = [],
    zones = [],
    states = [],
    employees = [],
    roles = [],
}) {
    const roleOptions = roles.map((r) => ({ value: r.id, label: r.name }));
    const companyOptions = companies.map((c) => ({
        value: c.id,
        label: c.name,
    }));
    const branchOptions = branches.map((b) => ({ value: b.id, label: b.name }));
    const departmentOptions = departments.map((d) => ({
        value: d.id,
        label: d.name,
    }));
    const managerOptions = employees.map((e) => ({
        value: e.id,
        label: e.name,
    }));

    const fields = [
        // Basic Info
        {
            name: "name",
            label: "Employee Name",
            type: "text",
            required: true,
            placeholder: "Enter employee name",
        },
        {
            name: "email",
            label: "Email (Login)",
            type: "email",
            required: true,
            placeholder: "Enter email address",
        },
        {
            name: "password",
            label: "Password",
            type: "text",
            required: !employee,
            placeholder: employee
                ? "Leave blank to keep current password"
                : "Enter password",
        },
        {
            name: "role_id",
            label: "Role",
            type: "select",
            required: true,
            options: roleOptions,
        },
        {
            name: "designation",
            label: "Designation",
            type: "text",
            placeholder: "e.g., Sales Manager",
        },

        // Organization
        {
            name: "company_id",
            label: "Company",
            type: "select",
            options: companyOptions,
        },
        {
            name: "branch_id",
            label: "Branch",
            type: "select",
            options: branchOptions,
        },
        {
            name: "dept_id",
            label: "Department",
            type: "select",
            options: departmentOptions,
        },

        // Location - NOW WITH ZONE
        {
            name: "zone_id",
            label: "Zone",
            type: "select",
        },
        {
            name: "state_id",
            label: "State",
            type: "select",
        },
        {
            name: "city_id",
            label: "City",
            type: "select",
        },
        {
            name: "area_id",
            label: "Area",
            type: "select",
        },
        {
            name: "pin_code",
            label: "Pin Code",
            type: "text",
            placeholder: "Enter pin code",
        },
        {
            name: "address",
            label: "Address",
            type: "textarea",
            rows: 3,
            placeholder: "Enter complete address",
        },

        // Contact Details
        {
            name: "contact_number_1",
            label: "Contact Number 1",
            type: "text",
            placeholder: "Primary contact number",
        },
        {
            name: "contact_number_2",
            label: "Contact Number 2",
            type: "text",
            placeholder: "Secondary contact number",
        },
        {
            name: "email_1",
            label: "Email 1",
            type: "email",
            placeholder: "Primary email",
        },
        {
            name: "email_2",
            label: "Email 2",
            type: "email",
            placeholder: "Secondary email",
        },

        // Documents
        {
            name: "aadhar_number",
            label: "Aadhar Number",
            type: "text",
            placeholder: "Enter Aadhar number",
        },
        {
            name: "aadhar_image",
            label: "Aadhar Image/PDF",
            type: "file",
            accept: ".jpg,.jpeg,.png,.pdf",
            helpText: "Upload Aadhar card (JPG, PNG, or PDF, max 2MB)",
        },
        {
            name: "employee_image",
            label: "Employee Photo",
            type: "file",
            accept: ".jpg,.jpeg,.png",
            helpText: "Upload employee photo (JPG or PNG, max 2MB)",
        },

        // Dates
        {
            name: "dob",
            label: "Date of Birth",
            type: "date",
        },
        {
            name: "doj",
            label: "Date of Joining",
            type: "date",
        },

        // Reporting
        {
            name: "reporting_to",
            label: "Reporting To (Manager)",
            type: "select",
            options: managerOptions,
        },
    ];

    return (
        <MasterForm
            auth={auth}
            masterName="Employee"
            masterData={employee}
            viewBase="/employee-masters"
            fields={fields}
            hasZoneDropdown={true}
            hasStateDropdown={true}
            hasCityDropdown={true}
            hasAreaDropdown={true}
            zones={zones}
            states={states}
            title="Employee Management"
        />
    );
}
