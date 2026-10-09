import http from "node:http";
import { readFile } from "node:fs/promises";
const types = {
    html: "text/html",
    css: "text/css",
    mjs: "text/javascript",
    json: "application/json",
    svg: "image/svg+xml",
};
http.createServer(async (req, res) => {
    const path = decodeURIComponent(
        new URL(req.url, "http://localhost").pathname,
    );
    if (path.includes("..")) {
        res.writeHead(403).end();
        return;
    }
    try {
        const body = await readFile(
            new URL(
                `../dist${path === "/" ? "/index.html" : path}`,
                import.meta.url,
            ),
        );
        res.writeHead(200, {
            "Content-Type":
                types[path === "/" ? "html" : path.split(".").pop()] ||
                "application/octet-stream",
        });
        res.end(body);
    } catch {
        res.writeHead(404).end();
    }
}).listen(4173, "127.0.0.1");
