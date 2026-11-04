export default class GoTo {
  constructor(page) {
    this.page = page;
    this.product = new GoToProduct(page);
  }

  async checkout() {
    await this.page.goto("./checkout");
  }

  async cart() {
    await this.page.goto("./checkout/cart");
  }
}

class GoToProduct {
  constructor(page) {
    this.page = page;
    this.standardProducts = {
      cheap: "./affirm-water-bottle.html",
      "medium-priced": "./fusion-backpack.html",
    };
  }

  async standard(productType = "cheap") {
    await this.page.goto(this.standardProducts[productType]);
  }

  async bundle() {
    await this.page.goto("./sprite-yoga-companion-kit.html");
  }

  async configurable() {
    await this.page.goto("./hero-hoodie.html");
  }
}
