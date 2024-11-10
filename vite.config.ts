import { defineConfig } from 'vitest/config';
import { sveltekit } from '@sveltejs/kit/vite';

export default defineConfig({
	plugins: [sveltekit()],
	server: {
		fs: {
			allow: ['..', './uploads']  // Add uploads directory to allowed list
		}
	},

	test: {
		include: ['src/**/*.{test,spec}.{js,ts}']
	}
});
