import React, { useMemo, useState, useEffect } from "react";
import { router, Link, usePage } from "@inertiajs/react";
import DataTable from "react-data-table-component";
import MainLayout from "@/Layouts/MainLayout";
import AlertModal from "../AlertModel";
import Select from "react-select";
import axios from "axios";

export default function MasterIndex({
    auth,
    masterName,
    viewBase,
    columns,
    data,
    filters = {},
    excelTemplateRoute,
    excelImportRoute,
    hasStateFilter = false,
    hasCityFilter = false,
    hasAreaFilter = false,
    hasCompanyFilter = false,
    hasCategoryOneFilter = false,
    hasCategoryTwoFilter = false,
    hasCategoryThreeFilter = false,
    hasProductCategoryFilter = false,
    hasStoreFilter = false,
    hasProductFilter = false,
    hasBranchFilter = false,
    hasEmployeeFilter = false,
    hasMonthFilter = false,
    hasYearFilter = false,
    hasStatusFilter = false,
    states = [],
    cities = [],
    areas = [],
    companies = [],
    categoryOnes = [],
    categoryTwos = [],
    categoryThrees = [],
    productCategories = [],
    stores = [],
    products = [],
    branches = [],
    employees = [],
    monthOptions = [],
    yearOptions = [],
    statusOptions = [],
    customActions = null,
    onStateChange = null,
    onCityChange = null,
    title,
    hasToggle = true, // Enable toggle by default
    customRender = null,
}) {
    const { flash } = usePage().props;
    const records = data?.data || [];
    const pagination = data;

    const [filterText, setFilterText] = useState(filters.search || "");
    const [alert, setAlert] = useState({
        show: false,
        type: "",
        message: "",
    });
    const [confirmBox, setConfirmBox] = useState({
        show: false,
        title: "",
        message: "",
        action: null,
    });
    const [uploadModal, setUploadModal] = useState(false);
    const [uploadFile, setUploadFile] = useState(null);
    const [perPage, setPerPage] = useState(pagination?.per_page || 10);
    const [selectedState, setSelectedState] = useState(null);
    const [selectedCity, setSelectedCity] = useState(null);
    const [selectedArea, setSelectedArea] = useState(null);
    const [selectedCompany, setSelectedCompany] = useState(null);
    const [selectedCategoryOne, setSelectedCategoryOne] = useState(null);
    const [selectedCategoryTwo, setSelectedCategoryTwo] = useState(null);
    const [selectedCategoryThree, setSelectedCategoryThree] = useState(null);
    const [selectedProductCategory, setSelectedProductCategory] =
        useState(null);
    const [selectedStore, setSelectedStore] = useState(null);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [selectedBranch, setSelectedBranch] = useState(null);
    const [selectedEmployee, setSelectedEmployee] = useState(null);
    const [selectedMonth, setSelectedMonth] = useState(null);
    const [selectedYear, setSelectedYear] = useState(null);
    const [selectedStatus, setSelectedStatus] = useState(null);

    // Dynamic cities and areas based on selections
    const [availableCities, setAvailableCities] = useState(cities);
    const [availableAreas, setAvailableAreas] = useState(areas);
    const [pdfModal, setPdfModal] = useState({
        show: false,
        url: null,
    });

    // Show flash messages
    useEffect(() => {
        if (flash?.success) {
            setAlert({
                show: true,
                type: "success",
                message: flash.success,
            });
        }
        if (flash?.error) {
            setAlert({
                show: true,
                type: "error",
                message: flash.error,
            });
        }
    }, [flash]);

    const stateOptions = [
        { value: null, label: "All States" },
        ...states.map((s) => ({ value: s.id, label: s.name })),
    ];

    const cityOptions = [
        { value: null, label: "All Cities" },
        ...availableCities.map((c) => ({ value: c.id, label: c.name })),
    ];

    const areaOptions = [
        { value: null, label: "All Areas" },
        ...availableAreas.map((a) => ({ value: a.id, label: a.name })),
    ];

    const companyOptions = [
        { value: null, label: "All Companies" },
        ...companies.map((c) => ({ value: c.id, label: c.name })),
    ];

    const categoryOneOptions = [
        { value: null, label: "All Category One" },
        ...categoryOnes.map((c) => ({ value: c.id, label: c.name })),
    ];

    const categoryTwoOptions = [
        { value: null, label: "All Category Two" },
        ...categoryTwos.map((c) => ({ value: c.id, label: c.name })),
    ];

    const categoryThreeOptions = [
        { value: null, label: "All Category Three" },
        ...categoryThrees.map((c) => ({ value: c.id, label: c.name })),
    ];

    const productCategoryOptions = [
        { value: null, label: "All Product Categories" },
        ...productCategories.map((c) => ({ value: c.id, label: c.name })),
    ];

    const storeOptions = [
        { value: null, label: "All Stores" },
        ...stores.map((s) => ({ value: s.id, label: s.name })),
    ];

    const productOptions = [
        { value: null, label: "All Products" },
        ...products.map((p) => ({ value: p.id, label: p.name })),
    ];

    const branchOptions = [
        { value: null, label: "All Branches" },
        ...branches.map((b) => ({ value: b.id, label: b.name })),
    ];

    const employeeOptions = [
        { value: null, label: "All Employees" },
        ...employees.map((e) => ({ value: e.id, label: e.name })),
    ];

    // Helper function to get nested value
    const getNestedValue = (obj, path) => {
        return path.split(".").reduce((current, key) => current?.[key], obj);
    };

    const filteredItems = useMemo(() => {
        return records.filter((item) => {
            const searchStr = filterText.toLowerCase();
            return columns.some((col) => {
                const value = getNestedValue(item, col.key);
                return value?.toString().toLowerCase().includes(searchStr);
            });
        });
    }, [records, filterText, columns]);

    const handlePageChange = (page) => {
        router.get(
            viewBase,
            {
                page,
                per_page: perPage,
                search: filterText,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: selectedCompany?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handlePerRowsChange = (newPerPage, page) => {
        setPerPage(newPerPage);
        router.get(
            viewBase,
            {
                page,
                per_page: newPerPage,
                search: filterText,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: selectedCompany?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleSearch = (e) => {
        const value = e.target.value;
        setFilterText(value);

        const timer = setTimeout(() => {
            router.get(
                viewBase,
                {
                    search: value,
                    per_page: perPage,
                    state_id: selectedState?.value,
                    city_id: selectedCity?.value,
                    area_id: selectedArea?.value,
                    company_id: selectedCompany?.value,
                    category_one_id: selectedCategoryOne?.value,
                    category_two_id: selectedCategoryTwo?.value,
                    category_three_id: selectedCategoryThree?.value,
                    p_category_id: selectedProductCategory?.value,
                },
                { preserveState: true, preserveScroll: true },
            );
        }, 500);

        return () => clearTimeout(timer);
    };

    const handleStateFilter = (option) => {
        setSelectedState(option);
        setSelectedCity(null);
        setSelectedArea(null);
        setAvailableCities([]);
        setAvailableAreas([]);

        if (option?.value) {
            axios.get(`/cities/${option.value}`).then((res) => {
                setAvailableCities(res.data);
            });
        }

        if (onStateChange) onStateChange(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                state_id: option?.value,
                company_id: selectedCompany?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleCityFilter = (option) => {
        setSelectedCity(option);
        setSelectedArea(null);
        setAvailableAreas([]);

        if (option?.value) {
            axios.get(`/areas/${option.value}`).then((res) => {
                setAvailableAreas(res.data);
            });
        }

        if (onCityChange) onCityChange(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                state_id: selectedState?.value,
                city_id: option?.value,
                company_id: selectedCompany?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleAreaFilter = (option) => {
        setSelectedArea(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: option?.value,
                company_id: selectedCompany?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleCompanyFilter = (option) => {
        setSelectedCompany(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: option?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleCategoryOneFilter = (option) => {
        setSelectedCategoryOne(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: selectedCompany?.value,
                category_one_id: option?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleCategoryTwoFilter = (option) => {
        setSelectedCategoryTwo(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: selectedCompany?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: option?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleCategoryThreeFilter = (option) => {
        setSelectedCategoryThree(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: selectedCompany?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: option?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleProductCategoryFilter = (option) => {
        setSelectedProductCategory(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: selectedCompany?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: option?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleStoreFilter = (option) => {
        setSelectedStore(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                store_id: option?.value,
                product_id: selectedProduct?.value,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: selectedCompany?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleProductFilter = (option) => {
        setSelectedProduct(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                store_id: selectedStore?.value,
                product_id: option?.value,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: selectedCompany?.value,
                category_one_id: selectedCategoryOne?.value,
                category_two_id: selectedCategoryTwo?.value,
                category_three_id: selectedCategoryThree?.value,
                p_category_id: selectedProductCategory?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleBranchFilter = (option) => {
        setSelectedBranch(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                branch_id: option?.value,
                employee_id: selectedEmployee?.value,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: selectedCompany?.value,
                store_id: selectedStore?.value,
                product_id: selectedProduct?.value,
                month: selectedMonth?.value,
                year: selectedYear?.value,
                status: selectedStatus?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleEmployeeFilter = (option) => {
        setSelectedEmployee(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                employee_id: option?.value,
                branch_id: selectedBranch?.value,
                state_id: selectedState?.value,
                city_id: selectedCity?.value,
                area_id: selectedArea?.value,
                company_id: selectedCompany?.value,
                store_id: selectedStore?.value,
                product_id: selectedProduct?.value,
                month: selectedMonth?.value,
                year: selectedYear?.value,
                status: selectedStatus?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleMonthFilter = (option) => {
        setSelectedMonth(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                month: option?.value,
                year: selectedYear?.value,
                employee_id: selectedEmployee?.value,
                status: selectedStatus?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleYearFilter = (option) => {
        setSelectedYear(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                year: option?.value,
                month: selectedMonth?.value,
                employee_id: selectedEmployee?.value,
                status: selectedStatus?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleStatusFilter = (option) => {
        setSelectedStatus(option);

        router.get(
            viewBase,
            {
                search: filterText,
                per_page: perPage,
                status: option?.value,
                month: selectedMonth?.value,
                year: selectedYear?.value,
                employee_id: selectedEmployee?.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const onToggle = (row) => {
        setConfirmBox({
            show: true,
            title: `${row.is_active ? "Deactivate" : "Activate"} ${masterName}`,
            message: `Are you sure you want to ${row.is_active ? "deactivate" : "activate"} "${row.name}"?`,
            action: () => {
                router.post(
                    `${viewBase}/${row.id}/toggle`,
                    {},
                    {
                        preserveScroll: true,
                        onSuccess: (page) => {
                            if (page.props.flash?.success) {
                                setAlert({
                                    show: true,
                                    type: "success",
                                    message: page.props.flash.success,
                                });
                            }
                        },
                        onError: (errors) => {
                            setAlert({
                                show: true,
                                type: "error",
                                message: `Failed to toggle ${masterName} status`,
                            });
                        },
                    },
                );
            },
        });
    };

    const onDelete = (row) => {
        setConfirmBox({
            show: true,
            title: `Delete ${masterName}`,
            message: `Are you sure you want to delete "${row.name}"?`,
            action: () => {
                router.delete(`${viewBase}/${row.id}`, {
                    preserveScroll: true,
                    onSuccess: (page) => {
                        if (page.props.flash?.success) {
                            setAlert({
                                show: true,
                                type: "success",
                                message: page.props.flash.success,
                            });
                        }
                    },
                    onError: (errors) => {
                        setAlert({
                            show: true,
                            type: "error",
                            message: `Failed to delete ${masterName}`,
                        });
                    },
                });
            },
        });
    };

    const tableColumns = useMemo(
        () => [
            {
                name: <div className="fw-bold">#</div>,
                selector: (row, index) =>
                    (pagination.current_page - 1) * pagination.per_page +
                    index +
                    1,
                width: "70px",
                sortable: false,
            },
            ...columns.map((col) => ({
                name: <div className="fw-bold">{col.label}</div>,
                sortable: col.sortable !== false,
                wrap: true,
                width: col.width,

                cell: (row) => {
                    if (col.type === "custom" && customRender) {
                        const customContent = customRender(row, col);
                        if (customContent) {
                            return customContent;
                        }
                    }
                    // 👁 Catalogue PDF column
                    if (col.type === "pdf") {
                        return row.catalogue_pdf ? (
                            <button
                                className="btn btn-sm btn-dark text-white"
                                title="View Catalogue"
                                onClick={() =>
                                    setPdfModal({
                                        show: true,
                                        url: `/storage/${row.catalogue_pdf}`,
                                    })
                                }
                            >
                                <i className="fas fa-eye"></i>
                            </button>
                        ) : (
                            <span className="text-muted">—</span>
                        );
                    }

                    if (col.type === "badge") {
                        const value = getNestedValue(row, col.key);
                        const colorClass = col.color
                            ? `bg-${col.color}`
                            : value > 0
                              ? "bg-success"
                              : "bg-secondary";

                        return (
                            <span className={`badge ${colorClass}`}>
                                {value || 0}
                            </span>
                        );
                    }

                    // default behavior
                    return getNestedValue(row, col.key);
                },
            })),
            ...(hasToggle
                ? [
                      {
                          name: <div className="fw-bold">Status</div>,
                          cell: (row) => (
                              <span
                                  className={`badge ${row.is_active ? "bg-success" : "bg-secondary"}`}
                              >
                                  {row.is_active ? "Active" : "Inactive"}
                              </span>
                          ),
                          sortable: true,
                          width: "100px",
                      },
                  ]
                : []),
            {
                name: <div className="fw-bold">Created At</div>,
                selector: (row) =>
                    new Date(row.created_at).toLocaleDateString(),
                sortable: true,
                width: "120px",
            },
            {
                name: <div className="fw-bold">Action</div>,
                cell: (row) => (
                    <div className="d-flex gap-2">
                        {/* Custom Actions (if provided) */}
                        {customActions && customActions(row)}

                        {/* Default Actions */}
                        {hasToggle && (
                            <button
                                className={`btn btn-sm ${row.is_active ? "btn-dark" : "btn-dark"} text-white`}
                                onClick={() => onToggle(row)}
                                title={
                                    row.is_active ? "Deactivate" : "Activate"
                                }
                            >
                                <i
                                    className={`fas fa-${row.is_active ? "toggle-off" : "toggle-on"}`}
                                ></i>
                            </button>
                        )}
                        <Link
                            href={`${viewBase}/${row.id}/edit`}
                            className="btn btn-sm btn-dark text-white"
                        >
                            <i className="fas fa-edit"></i>
                        </Link>
                        <button
                            className="btn btn-sm btn-dark text-white"
                            onClick={() => onDelete(row)}
                        >
                            <i className="fas fa-trash"></i>
                        </button>
                    </div>
                ),
                ignoreRowClick: true,
                width: customActions ? "250px" : hasToggle ? "180px" : "150px",
            },
        ],
        [pagination, columns, hasToggle],
    );

    // Custom table styles for modern look
    const customStyles = {
        headRow: {
            style: {
                backgroundColor: "#f8f9fa",
                borderBottom: "2px solid #dee2e6",
                minHeight: "56px",
            },
        },
        headCells: {
            style: {
                fontSize: "14px",
                fontWeight: "600",
                color: "#212529",
                paddingLeft: "16px",
                paddingRight: "16px",
            },
        },
        rows: {
            style: {
                fontSize: "14px",
                color: "#495057",
                minHeight: "56px",
                "&:hover": {
                    backgroundColor: "#f8f9fa",
                    cursor: "pointer",
                },
            },
        },
        cells: {
            style: {
                paddingLeft: "16px",
                paddingRight: "16px",
            },
        },
        pagination: {
            style: {
                borderTop: "1px solid #dee2e6",
                minHeight: "56px",
            },
        },
    };

    // Calculate how many filters are active
    const activeFilters = [
        true, // search is always present
        hasCompanyFilter,
        hasStateFilter,
        hasCityFilter,
        hasAreaFilter,
        hasCategoryOneFilter,
        hasCategoryTwoFilter,
        hasCategoryThreeFilter,
        hasProductCategoryFilter,
        hasStoreFilter,
        hasProductFilter,
        hasBranchFilter,
        hasEmployeeFilter,
        hasMonthFilter,
        hasYearFilter,
        hasStatusFilter,
    ].filter(Boolean).length;

    // Determine column class based on number of filters
    const getFilterColumnClass = () => {
        if (activeFilters <= 3) return "col-md-4";
        return "col-md-3";
    };

    return (
        <MainLayout title={title}>
            <div
                className="container-fluid py-4"
                style={{ backgroundColor: "#ffffff", minHeight: "100%" }}
            >
                {/* MODERN HEADER */}
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 className="mb-1 fw-bold">{masterName}</h2>
                        <p className="text-muted mb-0">
                            Manage your {masterName.toLowerCase()} records
                        </p>
                    </div>
                    <div className="d-flex gap-2">
                        {excelTemplateRoute && (
                            <a
                                href={route(excelTemplateRoute)}
                                className="btn btn-dark text-white d-flex align-items-center"
                                download
                            >
                                <i className="fas fa-download me-2"></i>
                                Download Template
                            </a>
                        )}
                        {excelImportRoute && (
                            <button
                                className="btn btn-dark text-white d-flex align-items-center"
                                onClick={() => setUploadModal(true)}
                            >
                                <i className="fas fa-upload me-2"></i>
                                Upload Excel
                            </button>
                        )}
                        <Link
                            href={`${viewBase}/create`}
                            className="btn btn-dark text-white d-flex align-items-center"
                        >
                            <i className="fas fa-plus me-2"></i>
                            Add New
                        </Link>
                    </div>
                </div>

                {/* MODERN SEARCH & FILTER CARD */}
                <div className="card border-0 shadow-sm mb-4">
                    <div className="card-body p-4">
                        <div className="row g-2">
                            {/* Search */}
                            <div className={getFilterColumnClass()}>
                                <div
                                    className="input-group"
                                    style={{ height: "38px" }}
                                >
                                    <span className="input-group-text bg-white border-end-0">
                                        <i className="fas fa-search text-muted"></i>
                                    </span>
                                    <input
                                        type="text"
                                        className="form-control border-start-0 ps-0"
                                        placeholder={`Search ${masterName}...`}
                                        value={filterText}
                                        onChange={handleSearch}
                                        style={{ height: "38px" }}
                                    />
                                </div>
                            </div>

                            {/* Company Filter */}
                            {hasCompanyFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={companyOptions}
                                        value={selectedCompany}
                                        onChange={handleCompanyFilter}
                                        placeholder="Filter by Company"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* State Filter */}
                            {hasStateFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={stateOptions}
                                        value={selectedState}
                                        onChange={handleStateFilter}
                                        placeholder="Filter by State"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* City Filter */}
                            {hasCityFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={cityOptions}
                                        value={selectedCity}
                                        onChange={handleCityFilter}
                                        placeholder="Filter by City"
                                        isClearable
                                        isDisabled={!selectedState}
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* Area Filter */}
                            {hasAreaFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={areaOptions}
                                        value={selectedArea}
                                        onChange={handleAreaFilter}
                                        placeholder="Filter by Area"
                                        isClearable
                                        isDisabled={!selectedCity}
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* Category One Filter */}
                            {hasCategoryOneFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={categoryOneOptions}
                                        value={selectedCategoryOne}
                                        onChange={handleCategoryOneFilter}
                                        placeholder="Category One"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* Category Two Filter */}
                            {hasCategoryTwoFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={categoryTwoOptions}
                                        value={selectedCategoryTwo}
                                        onChange={handleCategoryTwoFilter}
                                        placeholder="Category Two"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* Category Three Filter */}
                            {hasCategoryThreeFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={categoryThreeOptions}
                                        value={selectedCategoryThree}
                                        onChange={handleCategoryThreeFilter}
                                        placeholder="Category Three"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* Product Category Filter */}
                            {hasProductCategoryFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={productCategoryOptions}
                                        value={selectedProductCategory}
                                        onChange={handleProductCategoryFilter}
                                        placeholder="Product Category"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {hasStoreFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={storeOptions}
                                        value={selectedStore}
                                        onChange={handleStoreFilter}
                                        placeholder="Filter by Store"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* Product Filter */}
                            {hasProductFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={productOptions}
                                        value={selectedProduct}
                                        onChange={handleProductFilter}
                                        placeholder="Filter by Product"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}
                            {hasBranchFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={branchOptions}
                                        value={selectedBranch}
                                        onChange={handleBranchFilter}
                                        placeholder="Filter by Branch"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* Employee Filter */}
                            {hasEmployeeFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={employeeOptions}
                                        value={selectedEmployee}
                                        onChange={handleEmployeeFilter}
                                        placeholder="Filter by Employee"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* Month Filter */}
                            {hasMonthFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={[
                                            {
                                                value: null,
                                                label: "All Months",
                                            },
                                            ...monthOptions,
                                        ]}
                                        value={selectedMonth}
                                        onChange={handleMonthFilter}
                                        placeholder="Filter by Month"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* Year Filter */}
                            {hasYearFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={[
                                            { value: null, label: "All Years" },
                                            ...yearOptions,
                                        ]}
                                        value={selectedYear}
                                        onChange={handleYearFilter}
                                        placeholder="Filter by Year"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}

                            {/* Status Filter */}
                            {hasStatusFilter && (
                                <div className={getFilterColumnClass()}>
                                    <Select
                                        options={[
                                            {
                                                value: null,
                                                label: "All Status",
                                            },
                                            ...statusOptions,
                                        ]}
                                        value={selectedStatus}
                                        onChange={handleStatusFilter}
                                        placeholder="Filter by Status"
                                        isClearable
                                        styles={{
                                            control: (base) => ({
                                                ...base,
                                                borderColor: "#dee2e6",
                                                minHeight: "38px",
                                                height: "38px",
                                            }),
                                            valueContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                                padding: "0 8px",
                                            }),
                                            input: (base) => ({
                                                ...base,
                                                margin: "0",
                                                padding: "0",
                                            }),
                                            indicatorsContainer: (base) => ({
                                                ...base,
                                                height: "38px",
                                            }),
                                        }}
                                    />
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* MODERN DATA TABLE */}
                <div className="card border-0 shadow-sm">
                    <div className="card-body p-0">
                        <DataTable
                            columns={tableColumns}
                            data={filteredItems}
                            pagination
                            paginationServer
                            paginationTotalRows={pagination?.total || 0}
                            paginationDefaultPage={
                                pagination?.current_page || 1
                            }
                            onChangePage={handlePageChange}
                            onChangeRowsPerPage={handlePerRowsChange}
                            paginationPerPage={perPage}
                            paginationRowsPerPageOptions={[10, 15, 20, 25, 50]}
                            highlightOnHover
                            responsive
                            customStyles={customStyles}
                            noDataComponent={
                                <div className="text-center py-5">
                                    <i className="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p className="text-muted">
                                        No {masterName} found
                                    </p>
                                </div>
                            }
                        />
                    </div>
                </div>

                {/* MODERN CONFIRM DELETE/TOGGLE MODAL */}
                {confirmBox.show && (
                    <div
                        className="modal show d-block"
                        style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
                    >
                        <div className="modal-dialog modal-dialog-centered">
                            <div className="modal-content border-0 shadow-lg">
                                <div className="modal-header border-0 pb-0">
                                    <h5 className="modal-title fw-bold">
                                        {confirmBox.title}
                                    </h5>
                                    <button
                                        type="button"
                                        className="btn-close"
                                        onClick={() =>
                                            setConfirmBox({ show: false })
                                        }
                                    />
                                </div>
                                <div className="modal-body pt-2">
                                    <p className="text-muted mb-0">
                                        {confirmBox.message}
                                    </p>
                                </div>
                                <div className="modal-footer border-0">
                                    <button
                                        className="btn btn-light"
                                        onClick={() =>
                                            setConfirmBox({ show: false })
                                        }
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        className="btn btn-dark text-white"
                                        onClick={() => {
                                            confirmBox.action();
                                            setConfirmBox({ show: false });
                                        }}
                                    >
                                        Confirm
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* MODERN UPLOAD EXCEL MODAL */}
                {uploadModal && (
                    <div
                        className="modal show d-block"
                        style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
                    >
                        <div className="modal-dialog modal-dialog-centered">
                            <div className="modal-content border-0 shadow-lg">
                                <div className="modal-header border-0 pb-0">
                                    <h5 className="modal-title fw-bold">
                                        Upload {masterName} Excel
                                    </h5>
                                    <button
                                        type="button"
                                        className="btn-close"
                                        onClick={() => setUploadModal(false)}
                                    />
                                </div>
                                <div className="modal-body">
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">
                                            Excel File{" "}
                                            <span className="text-danger">
                                                *
                                            </span>
                                        </label>
                                        <input
                                            type="file"
                                            className="form-control"
                                            accept=".xlsx,.xls,.csv"
                                            onChange={(e) =>
                                                setUploadFile(e.target.files[0])
                                            }
                                        />
                                        <small className="text-muted">
                                            Supported formats: .xlsx, .xls, .csv
                                        </small>
                                    </div>
                                </div>
                                <div className="modal-footer border-0">
                                    <button
                                        className="btn btn-light"
                                        onClick={() => setUploadModal(false)}
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        className="btn btn-dark text-white"
                                        disabled={!uploadFile}
                                        onClick={() => {
                                            const fd = new FormData();
                                            fd.append("excel_file", uploadFile);

                                            router.post(excelImportRoute, fd, {
                                                forceFormData: true,
                                                onSuccess: (page) => {
                                                    if (
                                                        page.props.flash
                                                            ?.success
                                                    ) {
                                                        setAlert({
                                                            show: true,
                                                            type: "success",
                                                            message:
                                                                page.props.flash
                                                                    .success,
                                                        });
                                                    }
                                                    setUploadModal(false);
                                                    setUploadFile(null);
                                                },
                                                onError: (errors) => {
                                                    setAlert({
                                                        show: true,
                                                        type: "error",
                                                        message:
                                                            errors.excel_file ||
                                                            "Upload failed",
                                                    });
                                                },
                                            });
                                        }}
                                    >
                                        <i className="fas fa-upload me-2"></i>
                                        Upload
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {pdfModal.show && (
                    <div
                        className="modal show d-block"
                        style={{ backgroundColor: "rgba(0,0,0,0.5)" }}
                    >
                        <div className="modal-dialog modal-xl modal-dialog-centered">
                            <div className="modal-content border-0 shadow-lg">
                                <div className="modal-header">
                                    <h5 className="modal-title fw-bold">
                                        Product Catalogue
                                    </h5>
                                    <button
                                        type="button"
                                        className="btn-close"
                                        onClick={() =>
                                            setPdfModal({
                                                show: false,
                                                url: null,
                                            })
                                        }
                                    />
                                </div>

                                <div
                                    className="modal-body p-0"
                                    style={{ height: "80vh" }}
                                >
                                    <iframe
                                        src={pdfModal.url}
                                        title="Catalogue PDF"
                                        width="100%"
                                        height="100%"
                                        style={{ border: "none" }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* ALERT MODAL */}
                <AlertModal
                    show={alert.show}
                    type={alert.type}
                    message={alert.message}
                    onClose={() => setAlert({ ...alert, show: false })}
                />
            </div>
        </MainLayout>
    );
}
