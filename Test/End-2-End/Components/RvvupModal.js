/**
 * Returns the frame locator for the Rvvup checkout modal.
 *
 * The hosted checkout inside the modal can show a OneTrust cookie banner whose
 * overlay intercepts pointer events, so accept it whenever it appears.
 */
export function rvvupModalFrame(page) {
  const frame = page.frameLocator("iframe.rvvup-modal");

  page.addLocatorHandler(
    frame.getByRole("button", { name: "Accept all cookies" }),
    async (button) => {
      await button.click();
    },
  );

  return frame;
}
