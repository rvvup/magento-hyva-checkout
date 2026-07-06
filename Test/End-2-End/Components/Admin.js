import { expect } from "@playwright/test";

const ADMIN_USERNAME = process.env.MAGENTO_ADMIN_USERNAME || "admin";
const ADMIN_PASSWORD = process.env.MAGENTO_ADMIN_PASSWORD || "password1";

export default class Admin {
  constructor(page) {
    this.page = page;
    this.token = null;
  }

  origin() {
    return new URL(process.env.TEST_BASE_URL).origin;
  }

  async login() {
    if (this.token) {
      return this.token;
    }

    const response = await this.page.request.post(
      this.origin() + "/rest/V1/integration/admin/token",
      { data: { username: ADMIN_USERNAME, password: ADMIN_PASSWORD } },
    );

    expect(response.ok(), "Admin login failed").toBeTruthy();

    this.token = await response.json();

    return this.token;
  }

  async getOrderByIncrementId(incrementId) {
    await this.login();

    const response = await this.page.request.get(this.origin() + "/rest/V1/orders", {
      headers: { Authorization: `Bearer ${this.token}` },
      params: {
        "searchCriteria[filterGroups][0][filters][0][field]": "increment_id",
        "searchCriteria[filterGroups][0][filters][0][value]": incrementId,
      },
    });

    expect(response.ok(), "Fetching order failed").toBeTruthy();

    const body = await response.json();

    expect(
      body.items.length,
      `Order ${incrementId} was not found in the backend`,
    ).toBeGreaterThan(0);

    return body.items[0];
  }

  async expectOrderState(incrementId, expectedState) {
    const order = await this.getOrderByIncrementId(incrementId);

    expect(
      order.state,
      `Order ${incrementId} has state "${order.state}"/"${order.status}", expected state "${expectedState}"`,
    ).toBe(expectedState);

    return order;
  }
}
