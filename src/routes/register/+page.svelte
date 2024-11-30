<script>
    import { goto } from '$app/navigation';

    let username = '';
    let password = '';

    async function register() {
        try {
            const response = await fetch('/api/add-account', {
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

<div class="flex flex-col items-center w-full px-4 pt-[5vh] bg-gradient-to-b from-[#faedcd] to-white min-h-screen">
    <div class="logo-img mx-auto mb-2 mt-8 relative">
        <div class="absolute inset-0 blur-xl bg-[#d4a373]/30 rounded-full"></div>
        <img 
            src="/images/last.png" 
            class="logo w-[250px] h-[250px] md:w-[1000px] md:h-[400px] relative z-10 filter drop-shadow-[0_10px_15px_rgba(212,163,115,0.3)]" 
            alt="Logo"
        >
    </div>
    
    <h2 class="text-2xl font-dynapuff text-[#d4a373] mb-6 text-center">Create Your Account</h2>
    
    <div class="card bg-white/80 backdrop-blur-sm outline-none mb-6 border-none rounded-[20px] mt-4 pt-4 shadow-2xl w-full max-w-md mx-auto">
        <div class="card-body overflow-hidden p-5">
            <form>
                <div class="form-group mb-4">
                    <input 
                        type="text" 
                        class="form-control form-control-sm w-full rounded-full px-4 py-2 border-2 border-[#faedcd] focus:border-[#d4a373] focus:outline-none transition-colors" 
                        id="username" 
                        bind:value={username} 
                        placeholder="Username"
                    >
                </div>
                <div class="form-group mb-4">
                    <input 
                        type="password" 
                        class="form-control form-control-sm w-full rounded-full px-4 py-2 border-2 border-[#faedcd] focus:border-[#d4a373] focus:outline-none transition-colors" 
                        id="password" 
                        bind:value={password} 
                        placeholder="Password"
                    >
                </div>
                <button 
                    type="button" 
                    class="btn w-full rounded-full bg-[#d4a373] hover:bg-[#c49363] text-white py-3 font-medium transition-colors shadow-md"
                    on:click={register}
                >
                    Create Account
                </button>
            </form>
            <div class="account-section mt-4 text-right">
                <a 
                    href="/" 
                    class="new-account-link text-[#d4a373] font-bold text-sm hover:text-[#c49363] transition-colors"
                >
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</div>