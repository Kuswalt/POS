import { requireAuth } from '$lib/authGuard.js';
import type { PageLoad } from './$types.js';

export const load: PageLoad = () => {
    requireAuth(1);
    return {};
};