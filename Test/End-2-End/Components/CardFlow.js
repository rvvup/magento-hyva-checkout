export const INLINE = "INLINE";

let cachedFlow;

/**
 * The card method is either the inline form or the hosted modal, never both, so the specs for each
 * one can only run against a matching merchant. Rvvup publishes the configured flow on every
 * storefront page, which lets a spec select itself out instead of being skipped by hand.
 */
export default async function getCardFlow(page) {
  if (cachedFlow === undefined) {
    await page.goto("./");

    const flow = await page.evaluate(() =>
      typeof rvvup_parameters === "undefined"
        ? null
        : (rvvup_parameters?.settings?.card?.flow ?? null),
    );

    // Not knowing the flow must fail rather than skip. A store that briefly fails to serve
    // rvvup_parameters would otherwise silently skip every card test and report a green run.
    if (flow === null) {
      throw new Error(
        "Could not read the card flow from rvvup_parameters, so the card tests cannot select " +
          "themselves. The store did not serve the Rvvup payment settings.",
      );
    }

    cachedFlow = flow;
  }

  return cachedFlow;
}
