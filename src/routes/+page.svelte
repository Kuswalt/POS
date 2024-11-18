<script lang="ts">
    import { onMount } from 'svelte';
    import { goto } from '$app/navigation';
    import { setUser } from '$lib/auth';

    let username = '';
    let password = '';
    let errorMessage = '';

    onMount(() => {
        localStorage.removeItem('auth');
    });

    async function handleSubmit() {
        try {
            const response = await fetch('http://localhost/POS/api/routes.php?request=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();

            if (data.status) {
                setUser({
                    userId: data.userId,
                    username: username,
                    role: data.role
                });
                goto('/order');
            } else {
                errorMessage = data.message;
            }
        } catch (error) {
            console.error('Login error:', error);
            errorMessage = 'Failed to login. Please try again.';
        }
    }
</script>

    <div class="flex flex-col items-center w-full px-4">
        <div class="logo-img mx-auto mb-4 mt-8">
            <img src="/images/logo.png" class="logo w-[150px] h-[150px] md:w-[200px] md:h-[200px]" alt="Logo">
        </div>
        <div class="card bg-white/60 outline-none mb-6 border-none rounded-[20px] mt-4 pt-4 shadow-2xl w-full max-w-md mx-auto">
            <div class="card-body overflow-hidden p-5">
                <form>
                    <div class="form-group mb-3">
                        <input type="text" class="form-control form-control-sm w-full rounded-full" id="username" bind:value={username} placeholder="Username">
                    </div>
                    <div class="form-group mb-3">
                        <input type="password" class="form-control form-control-sm w-full rounded-full" id="password" bind:value={password} placeholder="Password">
                    </div>
                    <button type="button" class="btn w-full rounded-full bg-[#47cb50] text-white py-2" on:click={handleSubmit}>Login</button>
                </form>
                <div class="account-section mt-4 text-right">
                    <a href="/register" class="new-account-link text-[#025464] font-bold text-sm">Create a new account</a>
                </div>
            </div>
        </div>
    </div>