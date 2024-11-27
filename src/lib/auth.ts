import { writable } from 'svelte/store';
import { goto } from '$app/navigation';
import { browser } from '$app/environment';

// Define the user store type
type User = {
    userId: number;
    username: string;
    isAuthenticated: boolean;
    role?: number;
};

// Initialize with a clearly unauthenticated state
const initialState: User = {
    userId: 0,
    username: '',
    isAuthenticated: false,
    role: 0
};

// Create the store with initial state and initialize from localStorage if available
function createUserStore() {
    const { subscribe, set } = writable<User>(initialState);

    // Initialize from localStorage if we're in the browser
    if (browser) {
        const stored = localStorage.getItem('auth');
        if (stored) {
            try {
                const user = JSON.parse(stored);
                if (user.userId && user.username && user.isAuthenticated) {
                    set(user);
                }
            } catch (error) {
                console.error('Error parsing auth data:', error);
                localStorage.removeItem('auth');
            }
        }
    }

    return {
        subscribe,
        set
    };
}

export const userStore = createUserStore();

export function clearUser() {
    userStore.set(initialState);
}

export async function logout() {
    try {
        const response = await fetch('http://localhost/POS/api/routes.php?request=logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.status) {
            clearUser();
            // Clear any stored authentication data
            localStorage.removeItem('auth');
            goto('/');
        }
    } catch (error) {
        console.error('Logout error:', error);
    }
}

export function setUser(userData: { userId: number; username: string; role?: number }) {
    const user = {
        userId: userData.userId,
        username: userData.username,
        isAuthenticated: true,
        role: userData.role
    };
    userStore.set(user);
    // Store authentication data
    localStorage.setItem('auth', JSON.stringify(user));
}

// Function to check authentication status
export function checkAuth() {
    const stored = localStorage.getItem('auth');
    if (stored) {
        try {
            const user = JSON.parse(stored);
            if (user.userId && user.username && user.isAuthenticated) {
                userStore.set(user);
                return true;
            }
        } catch (error) {
            console.error('Error parsing auth data:', error);
            localStorage.removeItem('auth');
        }
    }
    clearUser();
    return false;
}
