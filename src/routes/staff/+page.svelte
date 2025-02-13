<script lang="ts">
    import { onMount } from 'svelte';
    import Header from '$lib/header.svelte';
    import { ApiService } from '$lib/services/api';
    import { userStore } from '$lib/auth';
    import Alert from '$lib/components/Alert.svelte';
    import { browser } from '$app/environment';
    import { requireAuth } from '$lib/authGuard';

    interface StaffMember {
        User_id: number;
        username: string;
        role: number;
    }

    let y = 0;
    let innerHeight = 0;
    let staff: StaffMember[] = [];
    let showAlert = false;
    let alertMessage = '';
    let alertType: 'success' | 'error' | 'warning' = 'success';
    let isLoading = true;

    function getRoleDisplay(role: number): string {
        switch (role) {
            case 1:
                return 'Admin';
            case 0:
                return 'Staff';
            default:
                return 'Pending Role';
        }
    }

    async function fetchStaff() {
        try {
            const result = await ApiService.get<StaffMember[]>('get-staff');
            if (result) {
                staff = result;
            }
        } catch (error) {
            console.error('Error fetching staff:', error);
            showAlert = true;
            alertType = 'error';
            alertMessage = 'Failed to fetch staff members';
            setTimeout(() => showAlert = false, 3000);
        }
    }

    async function updateRole(userId: number, newRole: number) {
        try {
            const result = await ApiService.put('update-staff-role', {
                user_id: userId,
                role: newRole
            });

            if (result.status) {
                showAlert = true;
                alertType = 'success';
                alertMessage = 'Role updated successfully';
                await fetchStaff();
            } else {
                showAlert = true;
                alertType = 'error';
                alertMessage = result.message || 'Failed to update role';
            }
            setTimeout(() => showAlert = false, 3000);
        } catch (error) {
            console.error('Error updating role:', error);
            showAlert = true;
            alertType = 'error';
            alertMessage = 'Failed to update role';
            setTimeout(() => showAlert = false, 3000);
        }
    }

    function isCurrentUser(userId: number): boolean {
        return userId === $userStore.userId;
    }

    async function checkAuth() {
        if (browser) {
            const isAuthorized = await requireAuth(1);
            if (!isAuthorized) {
                return false;
            }
            return true;
        }
        return false;
    }

    async function deleteStaffAccount(userId: number) {
        if (!confirm('Are you sure you want to delete this account?')) {
            return;
        }

        try {
            const result = await ApiService.delete('delete-staff-account', {
                user_id: userId
            });

            if (result.status) {
                showAlert = true;
                alertType = 'success';
                alertMessage = 'Account deleted successfully';
                await fetchStaff(); // Refresh the staff list
            } else {
                showAlert = true;
                alertType = 'error';
                alertMessage = result.message || 'Failed to delete account';
            }
            setTimeout(() => showAlert = false, 3000);
        } catch (error) {
            console.error('Error deleting account:', error);
            showAlert = true;
            alertType = 'error';
            alertMessage = 'Failed to delete account';
            setTimeout(() => showAlert = false, 3000);
        }
    }

    onMount(async () => {
        isLoading = true;
        const authorized = await checkAuth();
        if (authorized) {
            await fetchStaff();
        }
        isLoading = false;
    });
</script>

<Header {y} {innerHeight} />

{#if showAlert}
    <div class="fixed top-20 right-4 z-50">
        <Alert type={alertType} message={alertMessage} />
    </div>
{/if}

<div class="container mx-auto px-4 pt-24">
    {#if isLoading}
        <div class="flex justify-center items-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#d4a373]"></div>
        </div>
    {:else}
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Staff Management</h1>
            
            <div class="overflow-x-auto">
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Current Role</th>
                            <th>Change Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each staff as member}
                            <tr class={isCurrentUser(member.User_id) ? 'bg-[#faedcd] bg-opacity-50' : ''}>
                                <td data-label="User ID">{member.User_id}</td>
                                <td data-label="Username" class="flex items-center gap-2">
                                    {member.username}
                                    {#if isCurrentUser(member.User_id)}
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-[#d4a373] text-white">
                                            Current User
                                        </span>
                                    {/if}
                                </td>
                                <td data-label="Current Role">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {member.role === 1 ? 'bg-green-100 text-green-800' : 
                                         member.role === 0 ? 'bg-blue-100 text-blue-800' : 
                                         'bg-yellow-100 text-yellow-800'}">
                                        {getRoleDisplay(member.role)}
                                    </span>
                                </td>
                                <td data-label="Change Role">
                                    <select 
                                        class="block w-full px-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md
                                            {isCurrentUser(member.User_id) ? 'bg-gray-100' : ''}"
                                        value={member.role}
                                        on:change={(e) => updateRole(member.User_id, parseInt(e.currentTarget.value))}
                                        disabled={isCurrentUser(member.User_id)}
                                    >
                                        <option value={1}>Admin-access</option>
                                        <option value={0}>Restricted-access</option>
                                        <option value={2}>No-access</option>
                                    </select>
                                </td>
                                <td data-label="Actions">
                                    {#if member.role !== 1 && !isCurrentUser(member.User_id)}
                                        <button
                                            class="px-3 py-1 text-sm font-semibold text-white bg-red-500 rounded hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                            on:click={() => deleteStaffAccount(member.User_id)}
                                        >
                                            Delete
                                        </button>
                                    {/if}
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>
    {/if}
</div>

<style>
    .container {
        background-color: #fefae0;
        min-height: calc(100vh - 4rem);
    }

    .bg-white {
        background: #faedcd;
        padding: 1.5rem;
    }

    /* Add smooth transition for row highlight */
    tr {
        transition: background-color 0.2s ease;
    }

    /* Style for current user row */
    tr.current-user {
        background-color: rgba(212, 163, 115, 0.1);
    }

    @media (max-width: 768px) {
        .container {
            padding: 3rem 0.5rem;
        }

        .bg-white {
            padding: 1rem;
        }
    }
</style>