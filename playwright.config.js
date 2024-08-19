/**
 * External dependencies
 */
import {
	fileURLToPath
} from 'url';
import {
	defineConfig,
	devices
} from '@playwright/test';

/**
 * WordPress dependencies
 */
const baseConfig = require('@wordpress/scripts/config/playwright.config');

// Parse the .wp-env.json file to get the "barn2" property.
const wpEnv = require('./package.json');
const {
	barn2
} = wpEnv;

// Now that we have the barn2 property, we can use it to programmatically generate projects.
const barn2Projects = [];

for (const [index, themeConfig] of Object.entries(barn2.tests)) {
	barn2Projects.push({
		name: themeConfig.name,
		use: {
			...devices['Desktop Chrome'],
		},
		testDir: `./tests/e2e/${ themeConfig.name }`,
	});
}

// Merge the base config with the barn2 projects.
const config = defineConfig({
	...baseConfig,
	reporter: process.env.CI ? [
		['github']
	] : 'list',
	workers: 1,
	testDir: './tests/e2e',
	globalSetup: fileURLToPath(
		new URL('./tests/e2e/global-setup.js', 'file:' + __filename).href
	),
	projects: barn2Projects,
});

export default config;
