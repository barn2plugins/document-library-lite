import {
	test as base,
	expect,
} from '@wordpress/e2e-test-utils-playwright';

import {
	PluginUtil,
	matchers
} from '@barn2plugins/playwright-utils';

const test = base.extend({
	/** @type {PluginUtil} */
	pluginUtil: async ({
		page,
		admin
	}, use) => {
		await use(new PluginUtil(page, admin, 'document-library-lite'));
	},
});

expect.extend(matchers);

export {
	test,
	expect,
};
