// The stack under test: the dev stack in .docker/, or the one civikitchen's
// playwright job names in CK_COMPOSE_FILE.
import { execFileSync } from "node:child_process";
import path from "node:path";
import process from "node:process";
import { test as base } from "@playwright/test";

const ROOT = path.join(import.meta.dirname, "..", "..");
const COMPOSE = process.env.CK_COMPOSE_FILE
  ? ["-f", path.resolve(ROOT, process.env.CK_COMPOSE_FILE)]
  : ["-f", path.join(ROOT, ".docker", "docker-compose.yml")];

// Arguments reach cv as an argument vector, never as shell text.
export function cv(...args) {
  return execFileSync(
    "docker",
    [
      "compose",
      ...COMPOSE,
      "exec",
      "-T",
      "-u",
      "www-data",
      "-w",
      "/var/www/html",
      "app",
      "cv",
      ...args,
    ],
    { encoding: "utf8", stdio: ["ignore", "pipe", "inherit"] },
  ).trim();
}

export const getSetting = (name) =>
  JSON.parse(cv("ev", `echo json_encode(\\Civi::settings()->get(${JSON.stringify(name)}));`));
export const setSetting = (name, value) =>
  cv(
    "ev",
    `\\Civi::settings()->set(${JSON.stringify(name)}, json_decode(${JSON.stringify(JSON.stringify(value))}, TRUE));`,
  );

// One-time authx login link for the admin user; its host is the site URL.
export const loginUrl = (route) => JSON.parse(cv("url", "-L", "--user=admin", route));

// The browser reaches the site only: an off-site URL in a fixture (an image on
// example.org) would hold the `load` event for as long as that host stalls.
let site;
export const test = base.extend({
  context: async ({ context }, use) => {
    site ??= new URL(JSON.parse(cv("url", "civicrm"))).origin;
    await context.route(
      (url) => url.origin !== site,
      (route) => route.abort("blockedbyclient"),
    );
    await use(context);
  },
});
