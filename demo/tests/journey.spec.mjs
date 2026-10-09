import { test, expect } from "@playwright/test";
test("learner finishes a course, keeps progress, prints and resets", async ({
    page,
}) => {
    const errors = [];
    page.on("pageerror", (error) => errors.push(error.message));
    await page.goto("/");
    await expect(
        page.getByRole("heading", { name: "Small lessons. Lasting skills." }),
    ).toBeVisible();
    await page.getByRole("link", { name: "My learning", exact: true }).click();
    await page.getByRole("link", { name: "Continue learning" }).click();
    await page.getByLabel("The room feels neglected.").check();
    await page.getByRole("button", { name: "Check my answer" }).click();
    await expect(page.getByRole("status")).toContainText("Not quite");
    for (const index of [0, 1, 2]) {
        // The fixture is the same source used by the app; select the known correct index for each quiz.
        const correct = [1, 2, 0][index];
        await page.getByRole("radio").nth(correct).check();
        await page.getByRole("button", { name: "Check my answer" }).click();
        await expect(page.getByRole("status")).toContainText("Lesson complete");
        await page
            .getByRole("link", {
                name: index === 2 ? "View your certificate" : "Next lesson",
            })
            .click();
    }
    await expect(
        page.getByRole("heading", { name: "Alex Morgan" }),
    ).toBeVisible();
    await page.reload();
    await expect(
        page.getByRole("heading", { name: "Alex Morgan" }),
    ).toBeVisible();
    await page.emulateMedia({ media: "print" });
    await expect(page.locator(".demo-banner")).toBeHidden();
    await page.emulateMedia({ media: "screen" });
    page.on("dialog", (dialog) => dialog.accept());
    await page.getByRole("button", { name: "Reset demo" }).click();
    await expect(page.getByText("0 of 3 lessons complete")).toBeVisible();
    expect(errors).toEqual([]);
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= window.innerWidth,
        ),
    ).toBe(true);
});
test("enrollment, empty search and locked certificate", async ({ page }) => {
    await page.goto("/#/certificate/2");
    await expect(
        page.getByText("This page is unavailable", { exact: false }),
    ).toBeVisible();
    await page.goto("/#/catalog");
    await page.getByRole("searchbox").fill("nothing matches");
    await expect(
        page.getByText("No courses found.", { exact: false }),
    ).toBeVisible();
    await page.getByRole("searchbox").fill("Write");
    await page
        .getByRole("link", { name: "Write with clarity", exact: true })
        .click();
    await page.getByRole("button", { name: "Start this free course" }).click();
    await expect(
        page.getByRole("heading", { name: "Start with the reader" }),
    ).toBeVisible();
});
