import { expect } from "@playwright/test";

/**
 * Clicks a control in the Hyva checkout.
 *
 * The checkout re-renders itself constantly while Magewire saves the quote, and a click aimed at a
 * control during one of those updates hangs: Playwright waits for the element to be actionable while
 * the element it resolved is replaced underneath it, and Hyva ignores clicks that do land mid update.
 * Scrolling the control into view first, then retrying the click with a short timeout, covers both.
 */
export default async function clickCheckoutControl(locator) {
  await expect(async () => {
    await locator.scrollIntoViewIfNeeded();
    await locator.click({ timeout: 5000 });
  }).toPass({ timeout: 30000 });
}
