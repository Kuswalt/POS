import { requireAuth } from '$lib/authGuard';
import type { PageLoad } from './$types';

export const load: PageLoad = () => {
    requireAuth(0);
    return {};
};