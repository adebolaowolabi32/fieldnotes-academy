import { readFile, writeFile, mkdir, cp, rm } from "node:fs/promises";
const root = new URL("../", import.meta.url);
const dist = new URL("dist/", root);
await rm(dist, { recursive: true, force: true });
await mkdir(dist, { recursive: true });
for (const name of ["app.mjs", "model.mjs", "demo.css", "courses.json"])
    await cp(new URL(`demo/${name}`, root), new URL(name, dist));
for (const name of ["app.css", "favicon.svg"])
    await cp(new URL(`public/${name}`, root), new URL(name, dist));
const catalog = await readFile(
    new URL("resources/views/catalog.blade.php", root),
    "utf8",
);
const hero = catalog
    .slice(
        catalog.indexOf('<section class="hero">'),
        catalog.indexOf('<section class="section" id="courses">'),
    )
    .replace('href="#courses"', 'href="#/catalog"');
const html = (await readFile(new URL("demo/index.html", root), "utf8")).replace(
    "<!-- HERO -->",
    hero,
);
await writeFile(new URL("index.html", dist), html);
await writeFile(new URL(".nojekyll", dist), "");
console.log("Built only public demo assets in dist/");
