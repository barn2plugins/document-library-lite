/**
 * External dependencies
 */
import { test, expect } from "../fixtures";

import {
  pluginCanBeActivated,
  isWCDependencyNoticeVisible,
  canAccessSetupWizard,
} from "@barn2plugins/playwright-utils";

// Annotate the test suite as serial to ensure that the tests run in order.
test.describe.configure({
  mode: "serial",
});

test.describe("initial loading", (props) => {
  test("table columns work fine", async ({
    page,
    admin,
    pluginUtil,
  }, testInfo) => {
    await admin.visitAdminPage(
      "admin.php?page=document_library&tab=document_libraries"
    );
    await page.getByLabel("Columns").fill("title");
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto("/document-library");
    await expect(page.locator("table thead tr th")).toHaveCount(1);
    await expect(page.locator("table thead tr th")).toHaveText("Title");

    // Assign all the possible columns
    await admin.visitAdminPage(
      "admin.php?page=document_library&tab=document_libraries"
    );
    await page
      .getByLabel("Columns")
      .fill("id, title, content, image, date, doc_categories, link");
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto("/document-library");
    await expect(page.locator("table thead tr th")).toHaveCount(7);
  });

  test("download button works fine", async ({
    page,
    admin,
    pluginUtil,
  }, testInfo) => {
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Link style").selectOption("button");
    await page.getByLabel("Link text").fill("Get file");
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto("/document-library");
    await expect(
      page.locator(
        ".document-library-table tbody tr:nth-child(1) .col-link a"
      )
    ).toHaveText("Get file");

    // Test the button with text and icon
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Link style").selectOption("button_icon_text");
    await page.getByLabel("Link text").fill("Download");
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto("/document-library");
    await expect(
      page.locator(
        ".document-library-table tbody tr:nth-child(1) .col-link a"
      )
    ).toHaveText("Download");
    await expect(
      page.locator(
        ".document-library-table tbody tr:nth-child(1) .col-link a .dll-icon"
      )
    ).toBeVisible();

    // Test with the button and icon only
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Link style").selectOption("button_icon");
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto("/document-library");
    await expect(
      page.locator(
        ".document-library-table tbody tr:nth-child(1) .col-link a"
      )
    ).toHaveText("");
    await expect(
      page.locator(
        ".document-library-table tbody tr:nth-child(1) .col-link a .dll-icon"
      )
    ).toBeVisible();

    // Test with icon only
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Link style").selectOption("icon_only");
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto("/document-library");
    await expect(
      page.locator(
        ".document-library-table tbody tr:nth-child(1) .col-link a.dll-download-button"
      )
    ).not.toBeVisible();
    await expect(
      page.locator(
        ".document-library-table tbody tr:nth-child(1) .col-link a"
      )
    ).toBeVisible();

    // Test the file type icon
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Link style").selectOption("icon");
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto("/document-library");
    await expect(
      page.locator(
        ".document-library-table tbody tr:nth-child(1) .col-link a .dll-icon.pdf"
      )
    ).toBeVisible();

    // Test the text link
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Link style").selectOption("text");
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto("/document-library");
    await expect(
      page.locator(
        ".document-library-table tbody tr:nth-child(1) .col-link a.dll-download-button"
      )
    ).not.toBeVisible();
    await expect(
      page.locator(
        ".document-library-table tbody tr:nth-child(1) .col-link a"
      )
    ).toHaveText("Download");

    // Reset the settings
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Link style").selectOption("button");
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");
  });

  test("showing images in a lightbox work fine", async ({
    page,
    admin,
    pluginUtil,
  }, testInfo) => {
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Image lightbox").check();
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto("/document-library");
    await page
      .locator(
        ".document-library-table tbody tr:nth-child(1) .col-image img"
      )
      .click();
    await expect(page.locator(".pswp--open")).toBeVisible();
    await expect(page.locator(".pswp--open img.pswp__img")).toBeVisible();

    await page.locator(".pswp button.pswp__button.pswp__button--close").click();
    await expect(page.locator(".pswp--open")).not.toBeVisible();
  });

  test("documents per page work fine", async ({
    page,
    admin,
    pluginUtil,
  }, testInfo) => {
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Documents per page").fill( '1' );
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto( '/document-library' );
    await expect( page.locator( '.document-library-table > tbody > tr' ) ).toHaveCount(1);

    // Reset the settings
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Documents per page").fill( '20' );
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");
  });

  test("sort documents work fine", async ({
    page,
    admin,
    pluginUtil,
  }, testInfo) => {
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Sort by").selectOption( 'title' );
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto( '/document-library' );
    await expect( page.locator( '.document-library-table > tbody > tr:nth-child(1) .col-title' ) ).toHaveText('Third document');

    // Reset the settings
    await admin.visitAdminPage("admin.php?page=document_library&tab=general");
    await page.getByLabel("Sort direction").selectOption( 'desc' );
    await page.getByRole("button", { name: "Save Changes" }).click();
    await page.waitForLoadState("domcontentloaded");

    await page.goto( '/document-library' );
    await expect( page.locator( '.document-library-table > tbody > tr:nth-child(1) .col-title' ) ).toHaveText('Second document');

  });


});
