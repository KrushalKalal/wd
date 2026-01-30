import { usePage } from "@inertiajs/react";
import MainLayout from "@/Layouts/MainLayout";

export default function Dashboard() {
    const { auth, role } = usePage().props;

    return (
        <MainLayout title="Dashboard" auth={auth}>
            <h2>Welcome, {auth.user.name}</h2>
            <p>
                <strong>Role:</strong> {role}
            </p>
        </MainLayout>
    );
}
