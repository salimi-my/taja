export type DashboardStats = {
    total_users: number;
    verified_users: number;
    admin_users: number;
    new_users: number;
};

export type SignupPoint = {
    date: string;
    label: string;
    signups: number;
};
