import { goto } from '$app/navigation';
import { userStore } from './auth.js';
import { get } from 'svelte/store';
import { browser } from '$app/environment';

export function requireAuth(requiredRole: number = 1) {
    if (!browser) return false;
    
    const user = get(userStore);
    
    // Check localStorage for session data if user is not authenticated
    if (!user.isAuthenticated) {
        const stored = localStorage.getItem('auth');
        if (stored) {
            try {
                const storedUser = JSON.parse(stored);
                if (storedUser.userId && storedUser.username && storedUser.isAuthenticated) {
                    userStore.set(storedUser);
                    // Recheck authorization with updated user
                    return requireAuth(requiredRole);
                }
            } catch (error) {
                console.error('Error parsing auth data:', error);
                localStorage.removeItem('auth');
            }
        }
        goto('/');
        return false;
    }

    if ((user.role ?? 0) < requiredRole) {
        goto('/order');
        return false;
    }
    return true;
}