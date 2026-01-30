import MasterForm from "../Masters/MasterForm";

export default function Form({
    auth,
    employee = null,
    companies = [],
    branches = [],
    departments = [],
    states = [],
    employees = [],
    roles = [],
}) {
    const roleOptions = roles.map((r) => ({
        value: r.id,
        label: r.name,
    }));
    const companyOptions = companies.map((c) => ({
        value: c.id,
        label: c.name,
    }));
    const branchOptions = branches.map((b) => ({
        value: b.id,
        label: b.name,
    }));
    const deptOptions = departments.map((d) => ({
        value: d.id,
        label: d.name,
    }));
    const employeeOptions = employees.map((e) => ({
        value: e.id,
        label: e.name,
    }));

    const fields = [
        {
            name: "name",
            label: "Employee Name",
            type: "text",
            required: true,
        },
        {
            name: "email",
            label: "Email (Login)",
            type: "email",
            required: true,
        },
        {
            name: "password",
            label: "Password",
            type: "text",
            required: !employee,
            placeholder: employee ? "Leave blank to keep current password" : "Enter password",
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
            required: false,
        },
        {
            name: "company_id",
            label: "Company",
            type: "select",
            required: false,
            options: companyOptions,
        },
        {
            name: "branch_id",
            label: "Branch",
            type: "select",
            required: false,
            options: branchOptions,
        },
        {
            name: "dept_id",
            label: "Department",
            type: "select",
            required: false,
            options: deptOptions,
        },
        {
            name: "state_id",
            label: "State",
            type: "select",
            required: false,
        },
        {
            name: "city_id",
            label: "City",
            type: "select",
            required: false,
        },
        {
            name: "area_id",
            label: "Area",
            type: "select",
            required: false,
        },
        {
            name: "address",
            label: "Address",
            type: "textarea",
            required: false,
            rows: 3,
        },
        {
            name: "pin_code",
            label: "Pin Code",
            type: "text",
            required: false,
        },
        {
            name: "contact_number_1",
            label: "Contact Number 1",
            type: "text",
            required: false,
        },
        {
            name: "contact_number_2",
            label: "Contact Number 2",
            type: "text",
            required: false,
        },
        {
            name: "email_1",
            label: "Email 1",
            type: "email",
            required: false,
        },
        {
            name: "email_2",
            label: "Email 2",
            type: "email",
            required: false,
        },
        {
            name: "dob",
            label: "Date of Birth",
            type: "date",
            required: false,
        },
        {
            name: "doj",
            label: "Date of Joining",
            type: "date",
            required: false,
        },
        {
            name: "aadhar_number",
            label: "Aadhar Number",
            type: "text",
            required: false,
        },
        {
            name: "aadhar_image",
            label: "Aadhar Image/PDF",
            type: "file",
            required: false,
            accept: ".jpg,.jpeg,.png,.pdf",
            helpText: "Upload Aadhar card (Max: 2MB)",
        },
        {
            name: "employee_image",
            label: "Employee Photo",
            type: "file",
            required: false,
            accept: ".jpg,.jpeg,.png",
            helpText: "Upload employee photo (Max: 2MB)",
        },
        {
            name: "reporting_to",
            label: "Reports To (Manager)",
            type: "select",
            required: false,
            options: employeeOptions,
        },
    ];

    return (
           <MasterForm
               auth={auth}
               masterName="Employee Master"
               masterData={store}
               viewBase="/employee-masters"
               fields={fields}
               hasStateDropdown={true}
               hasCityDropdown={true}
               hasAreaDropdown={true}
               states={states}
               title="Employee Master"
           />
       );
}