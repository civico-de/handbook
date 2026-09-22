// The stack under test: the dev stack in .docker/, or the one civikitchen's
// playwright job names in CK_COMPOSE_FILE.
import { execFileSync } from "node:child_process";
import path from "node:path";
import process from "node:process";

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
