import { goto } from '$app/navigation';
import { userStore } from './auth';
import { get } from 'svelte/store';

export function requireAuth(requiredRole: number = 0) {
    const user = get(userStore);
    
    if (!user.isAuthenticated) {
        goto('/');
        return false;
    }

    if ((user.role ?? 0) < requiredRole) {
        goto('/order');
        return false;
    }

    return true;
}