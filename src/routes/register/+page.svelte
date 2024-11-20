<script>
    import { goto } from '$app/navigation';

    let username = '';
    let password = '';

    async function register() {
        try {
            const response = await fetch('http://localhost/POS/api/routes.php?request=add-account', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'add-account',
                    username,
                    password
                })
            });

            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                // Log the response text for debugging
                const text = await response.text();
                console.log('Response text:', text);

                const result = JSON.parse(text); // Parse the text as JSON
                if (result.status) {
                    alert('Registration successful');
                    goto('/'); // Redirect to login page or another page
                } else {
                    alert(result.message);
                }
            } else {
                // Log the response text for debugging
                const text = await response.text();
                console.error('Unexpected response:', text);
                throw new Error('Invalid JSON response');
            }
        } catch (error) {
            console.error('Error during registration:', error);
            alert('An error occurred. Please try again.');
        }
    }
</script>

<div class="flex flex-col items-center w-full px-4 pt-[20vh]">
    <div class="logo-img mx-auto mb-4 mt-8">
        <img src="/images/logo.png" class="logo w-[150px] h-[150px] md:w-[200px] md:h-[200px]" alt="Logo">
    </div>
    <div class="card bg-white/60 outline-none mb-6 border-none rounded-[20px] mt-4 pt-4 shadow-2xl w-full max-w-md mx-auto">
        <div class="card-body overflow-hidden p-5">
            <form>
                <div class="form-group mb-3">
                    <input type="text" class="form-control form-control-sm w-full rounded-full" id="username" bind:value={username} placeholder="Create Username">
                </div>
                <div class="form-group mb-3">
                    <input type="password" class="form-control form-control-sm w-full rounded-full" id="password" bind:value={password} placeholder=" Put Password">
                </div>
                <button type="button" class="btn w-full rounded-full bg-[#41b745] text-white py-2" on:click={register}>Create</button>
            </form>
            <div class="account-section mt-4 text-right">
                <a href="/" class="new-account-link text-[#025464] font-bold text-sm">Back to Login</a>
            </div>
        </div>
    </div>
</div>