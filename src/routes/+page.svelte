<script lang="ts">
    import { goto } from '$app/navigation';
    import { setUser } from '$lib/auth';
    import { onMount } from 'svelte';

    let username = '';
    let password = '';

    async function login() {
        try {
            const response = await fetch('http://localhost/POS/api/routes.php?request=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username,
                    password
                })
            });

            const result = await response.json();
            
            if (result.status) {
                setUser({
                    User_id: result.userId,
                    username: username,
                    role: result.role
                });
                goto('/order');
            } else {
                alert(result.message || 'Login failed');
            }
        } catch (error) {
            console.error('Login error:', error);
            alert('Failed to connect to the server');
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
                    <button type="button" class="btn w-full rounded-full bg-[#47cb50] text-white py-2" on:click={login}>Login</button>
                </form>
                <div class="account-section mt-4 text-right">
                    <a href="/register" class="new-account-link text-[#025464] font-bold text-sm">Create a new account</a>
                </div>
            </div>
        </div>
    </div>