import { expect } from "@playwright/test";

const EMAIL = "sb-uqeqf29136249@personal.example.com";
const PASSWORD = "h5Hc/b8M";

export default class PaypalPopup {
  constructor(popup) {
    this.popup = popup;
  }

  async acceptPayment() {
    await this.popup.waitForLoadState("domcontentloaded");
    await this.enterEmail();

    await this.popup.getByPlaceholder("Password").fill(PASSWORD);
    await this.popup.getByRole("button", { name: "Log In" }).click();

    // After logging in PayPal first shows a spinner on the button while it loads the funding options, which can take
    // longer than the default action timeout.
    await this.popup
      .getByTestId("submit-button-initial")
      .click({ timeout: 60000 });
  }

  /**
   * The PayPal sandbox shows its login page before its scripts are ready and clears an email that is filled in
   * during that moment ("Enter your email"), so the password field never appears. Fill the email again until it does.
   */
  async enterEmail() {
    const email = this.popup.getByPlaceholder("Email");
    const next = this.popup.getByRole("button", { name: "Next" });
    const password = this.popup.getByPlaceholder("Password");

    await expect(async () => {
      if (await email.isVisible()) {
        await email.fill(EMAIL);
        await expect(email).toHaveValue(EMAIL, { timeout: 5000 });
      }
      if (await next.isVisible()) {
        await next.click();
      }
      await expect(password).toBeVisible({ timeout: 10000 });
    }).toPass({ timeout: 60000 });
  }
}
