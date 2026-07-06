import PaypalPopup from "./PaypalPopup";

export default class Paypal {
  constructor(page) {
    this.page = page;
  }

  async approveFromButton(containerSelector) {
    const popupPromise = this.page.waitForEvent("popup");
    const paypalFrame = this.page
      .frameLocator(`${containerSelector} iframe`)
      .first();

    await paypalFrame.getByRole("link", { name: "PayPal" }).click();

    await new PaypalPopup(await popupPromise).acceptPayment();
  }
}
