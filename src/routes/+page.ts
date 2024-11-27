import { goto } from '$app/navigation';
import { get } from 'svelte/store';
import { userStore } from '$lib/auth';
import type { PageLoad } from './$types';
import { browser } from '$app/environment';

export const load: PageLoad = () => {
    if (!browser) return {};

    const user = get(userStore);
    
    // Check localStorage if user is not authenticated
    if (!user.isAuthenticated) {
        const stored = localStorage.getItem('auth');
        if (stored) {
            try {
                const storedUser = JSON.parse(stored);
                if (storedUser.userId && storedUser.username && storedUser.isAuthenticated) {
                    userStore.set(storedUser);
                    goto('/order');
                    return {};
                }
            } catch (error) {
                console.error('Error parsing auth data:', error);
                localStorage.removeItem('auth');
            }
        }
    } else {
        goto('/order');
    }
    
    return {};
};