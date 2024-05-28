export default class PaypalPopup {
  constructor(popup) {
    this.popup = popup;
  }

  async acceptPayment() {
    await this.popup
      .getByPlaceholder("Email")
      .fill("sb-uqeqf29136249@personal.example.com");
    if (await this.popup.getByRole("button", { name: "Next" }).isVisible()) {
      await this.popup.getByRole("button", { name: "Next" }).click();
    }
    await this.popup.getByPlaceholder("Password").fill("h5Hc/b8M");
    await this.popup.getByRole("button", { name: "Log In" }).click();

    await this.popup.getByTestId("submit-button-initial").click();
  }
}
