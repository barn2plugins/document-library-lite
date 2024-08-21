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
		const canActivate = await pluginCanBeActivated(page, admin, pluginUtil, false, expect);
		expect(canActivate).toBe(true);
	});

	test( 'can access Setup wizard page', async ({
		page,
		admin,
		pluginUtil
	}, testInfo) => {
		const setupWizardIsAccessible = await canAccessSetupWizard(page, admin, pluginUtil, expect);

		expect(setupWizardIsAccessible).toBe(true);
	});

	test( 'setup wizard works fine', async ({
		page,
		admin,
		pluginUtil
	}, testInfo) => {
		await admin.visitAdminPage( 'admin.php?page=document-library-lite-setup-wizard' );
		await page.waitForLoadState("networkidle");

		await expect(
			page.getByText("Welcome to Document Library Lite"),
		).toBeVisible();

		await page.getByRole("button", { name: "Next" }).click();
		await page.waitForLoadState("networkidle");

		await expect(
			page.locator(
				".barn2-stepper__step.is-active .barn2-stepper__step-label",
			),
		).toContainText("Layout and Content");
		await expect(page.locator('input[name="columns"]')).toBeVisible();

		await page.getByRole("button", { name: "Next" }).click();
		await page.waitForLoadState("networkidle");

		await expect(
			page.locator(
				".barn2-stepper__step.is-active .barn2-stepper__step-label",
			),
		).toContainText("Links");
		await expect(page.locator('input[name="link_text"]')).toBeVisible();

		await page.getByRole("button", { name: "Next" }).click();
		await page.waitForLoadState("networkidle");

		await expect(
			page.locator(
				".barn2-stepper__step.is-active .barn2-stepper__step-label",
			),
		).toContainText("Behavior");
		await expect(page.locator('select[name="sort_by"]')).toBeVisible();

		// Lst page and the settings page
		await page.getByRole("button", { name: "Next" }).click();
		await page.waitForLoadState("networkidle");
		await page.waitForTimeout(1000);

		if (
			await page
				.locator(
					'.barn2-stepper__step.is-active .barn2-stepper__step-label:has-text("More")',
				)
				.isVisible()
		) {
			await expect(
				page.locator(
					".barn2-stepper__step.is-active .barn2-stepper__step-label",
				),
			).toContainText("More");
			await page.locator('button:has-text( "Finish setup" )').click();
		}

		await page.locator('a:has-text( "Settings page" )').click();
		await page.waitForLoadState("domcontentloaded");

		await expect(page.locator(".barn2-promo-inner h1")).toHaveText(
			"Document Library Lite Settings",
		);
	});
});
