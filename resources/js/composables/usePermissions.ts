import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    const can = (permission: string): boolean => {
        const permissions = page.props.auth?.permissions || [];
        return permissions.includes(permission);
    };

    const hasRole = (role: string): boolean => {
        const roles = page.props.auth?.roles || [];
        return roles.includes(role);
    };

    const isSuperAdmin = (): boolean => {
        return hasRole('super-admin');
    };

    return { can, hasRole, isSuperAdmin };
}
