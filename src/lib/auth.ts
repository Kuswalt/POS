import { writable } from 'svelte/store';
import { goto } from '$app/navigation';

interface User {
    userId: number | null;
    username: string | null;
    isAuthenticated: boolean;
    role: number | null;
}

export const userStore = writable<User>({
    userId: null,
    username: null,
    isAuthenticated: false,
    role: null
});

// Add helper functions
export function setUser(userData: { User_id: number; username: string; role: number }) {
    const user = {
        userId: userData.User_id,
        username: userData.username,
        isAuthenticated: true,
        role: userData.role
    };
    userStore.set(user);
    localStorage.setItem('user', JSON.stringify(user));
}

export function clearUser() {
    userStore.set({
        userId: null,
        username: null,
        isAuthenticated: false,
        role: null
    });
    localStorage.removeItem('user');
}

export function logout() {
    clearUser();
    goto('/');
}

function loadUserFromStorage() {
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem('user');
        if (stored) {
            try {
                const userData = JSON.parse(stored);
                userStore.set(userData);
            } catch (e) {
                clearUser();
            }
        }
    }
}

// Load user data on initialization
loadUserFromStorage();
