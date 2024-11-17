import { writable } from 'svelte/store';

interface User {
    userId: number | null;
    username: string | null;
    isAuthenticated: boolean;
}

export const userStore = writable<User>({
    userId: null,
    username: null,
    isAuthenticated: false
});

// Add helper functions
export function setUser(userData: { User_id: number; username: string }) {
    userStore.set({
        userId: userData.User_id,
        username: userData.username,
        isAuthenticated: true
    });
}

export function clearUser() {
    userStore.set({
        userId: null,
        username: null,
        isAuthenticated: false
    });
}
