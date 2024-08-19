/**
 * External dependencies
 */
import {
	test,
	expect
} from '../fixtures';

import {
	pluginCanBeActivated,
	isWCDependencyNoticeVisible,
	canAccessSetupWizard
} from '@barn2plugins/playwright-utils';

// Annotate the test suite as serial to ensure that the tests run in order.
test.describe.configure({
	mode: 'serial'
});

test.describe('initial loading', (props) => {
	test( 'should be able to activate the plugin', async ({
		page,
		admin,
		pluginUtil
	}, testInfo) => {
		const canActivate = await pluginCanBeActivated(page, admin, pluginUtil, true, expect);
		expect(canActivate).toBe(true);
	});

	test( 'should display WooCommerce dependency notice', async ({
		page,
		admin,
		pluginUtil
	}, testInfo) => {
		const displayedNotice = await isWCDependencyNoticeVisible(page, admin, pluginUtil, expect);

		expect(displayedNotice).toBe(true);
	});

	test( 'can access Setup wizard page', async ({
		page,
		admin,
		pluginUtil
	}, testInfo) => {
		const setupWizardIsAccessible = await canAccessSetupWizard(page, admin, pluginUtil, expect);

		expect(setupWizardIsAccessible).toBe(true);
	});
});
