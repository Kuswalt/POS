import { goto } from '$app/navigation';
import { userStore } from './auth.js';
import { get } from 'svelte/store';

export function requireAuth(requiredRole: number = 1) {
    // Add your authentication logic here
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