import { goto } from '$app/navigation';
import { get } from 'svelte/store';
import { userStore } from '$lib/auth';
import type { PageLoad } from './$types';

export const load: PageLoad = () => {
    const user = get(userStore);
    if (user.isAuthenticated) {
        goto('/order');
    }
    return {};
};