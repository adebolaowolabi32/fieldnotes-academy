import { freshState, readState, persist, enroll, grade } from "./model.mjs";
const main = document.querySelector("main");
document.querySelector(".skip").addEventListener("click", (event) => {
    event.preventDefault();
    main.focus();
});
const source = "https://github.com/adebolaowolabi32/fieldnotes-academy";
const escape = (value) =>
    String(value).replace(
        /[&<>"']/g,
        (char) =>
            ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;",
            })[char],
    );
let storage;
try {
    storage = window.localStorage;
} catch {}
let state = readState(storage);
const save = () => {
    document.querySelector("#storage-note").textContent = persist(
        storage,
        state,
    )
        ? "Progress saved in this browser"
        : "Progress kept for this visit only";
};
save();
let courses;
const passed = (course) =>
    course.lessons.filter((lesson) =>
        (state.passed[course.id] || []).includes(lesson.id),
    ).length;
const button = (text, path) =>
    `<a class="button" href="#/${path}">${text} <span>↗</span></a>`;
const card = (course) =>
    `<article class="course-card"><a href="#/course/${course.id}" class="course-art art-${course.id % 3}" aria-label="Explore ${escape(course.title)}"><span class="art-caption">FIELDNOTES / 00${course.id}</span><span class="course-symbol" aria-hidden="true">${["✳", "↗", "◎"][course.id % 3]}</span></a><div class="course-info"><div class="course-meta"><span>${escape(course.category)}</span><span>Beginner</span></div><h3><a href="#/course/${course.id}">${escape(course.title)}</a></h3><p>${escape(course.description)}</p><div class="course-meta"><span>${course.lessons.length} lessons · ${course.lessons.reduce((n, l) => n + l.minutes, 0)} min</span><a href="#/course/${course.id}">Explore course ↗</a></div></div></article>`;
function render() {
    const [page = "", id, lessonId] = location.hash
        .replace(/^#\/?/, "")
        .split("/");
    const course = courses.find((item) => item.id === Number(id));
    document
        .querySelectorAll("nav a")
        .forEach((a) => a.classList.toggle("active", a.hash === location.hash));
    if (page === "" || page === "catalog") {
        main.innerHTML =
            (page === "" ? document.querySelector("#hero").innerHTML : "") +
            `<section class="section"><div class="section-heading"><div><div class="eyebrow">FOLLOW YOUR CURIOSITY</div><h2>Find your next good idea.</h2></div><label class="search-label">Find a course<input id="search" type="search" placeholder="Search courses or topics"></label></div><div class="course-grid" id="results">${courses.map(card).join("")}</div><p id="result-count" aria-live="polite"></p></section>`;
        document.querySelector("#search").addEventListener("input", (event) => {
            const matches = courses.filter((c) =>
                `${c.title} ${c.description} ${c.category}`
                    .toLowerCase()
                    .includes(event.target.value.toLowerCase()),
            );
            document.querySelector("#results").innerHTML = matches
                .map(card)
                .join("");
            document.querySelector("#result-count").textContent = matches.length
                ? `${matches.length} courses found`
                : "No courses found. Try another topic.";
        });
    } else if (page === "course" && course) {
        main.innerHTML = `<section class="section narrow"><a href="#/catalog">← All courses</a><div class="eyebrow">${escape(course.category)} · BEGINNER</div><h1>${escape(course.title)}</h1><p class="lead">${escape(course.description)}</p><p>With ${escape(course.instructor)} · ${course.lessons.length} thoughtful lessons</p>${state.enrolled.includes(course.id) ? button("Continue learning", `lesson/${course.id}/${course.lessons.find((l) => !(state.passed[course.id] || []).includes(l.id))?.id || course.lessons[0].id}`) : `<button class="button" id="enroll">Start this free course ↗</button>`}<h2>Your field guide</h2><ol class="lesson-list">${course.lessons.map((l) => `<li><strong>${escape(l.title)}</strong><span>${l.minutes} min · reading + knowledge check</span></li>`).join("")}</ol><p class="muted">You are trying the course as Alex Morgan, a fictional learner. No account or password needed.</p></section>`;
        document.querySelector("#enroll")?.addEventListener("click", () => {
            enroll(state, course);
            save();
            location.hash = `/lesson/${course.id}/${course.lessons[0].id}`;
        });
    } else if (page === "learning") {
        main.innerHTML = `<section class="section"><div class="eyebrow">YOUR PERSONAL FIELDNOTES</div><h1>A little progress, every day.</h1><p>Welcome back, Alex. Pick up where your curiosity left off.</p><div class="learning-grid">${courses
            .filter((c) => state.enrolled.includes(c.id))
            .map(
                (c) =>
                    `<article class="panel"><div class="eyebrow">${escape(c.category)}</div><h2>${escape(c.title)}</h2><p>${passed(c)} of ${c.lessons.length} lessons complete</p><progress value="${passed(c)}" max="${c.lessons.length}" aria-label="${escape(c.title)} progress"></progress>${button("Continue learning", `lesson/${c.id}/${c.lessons.find((l) => !(state.passed[c.id] || []).includes(l.id))?.id || c.lessons[0].id}`)}${state.completed[c.id] ? `<p><a href="#/certificate/${c.id}">View your certificate ↗</a></p>` : ""}</article>`,
            )
            .join(
                "",
            )}</div><p><a href="#/catalog">Find another course ↗</a></p></section>`;
    } else if (
        page === "lesson" &&
        course &&
        state.enrolled.includes(course.id) &&
        course.lessons.some((l) => l.id === Number(lessonId))
    ) {
        const lesson = course.lessons.find((l) => l.id === Number(lessonId));
        main.innerHTML = `<section class="reader"><aside class="reader-nav"><a href="#/learning">← My learning</a><h2>${escape(course.title)}</h2><p>${passed(course)} / ${course.lessons.length} complete</p>${course.lessons.map((l, i) => `<a ${l.id === lesson.id ? 'aria-current="page"' : ""} href="#/lesson/${course.id}/${l.id}">${(state.passed[course.id] || []).includes(l.id) ? "✓" : i + 1} · ${escape(l.title)}</a>`).join("")}</aside><article class="lesson-content"><div class="eyebrow">${lesson.minutes} MIN READ · LESSON ${course.lessons.indexOf(lesson) + 1}</div><h1>${escape(lesson.title)}</h1><div class="prose">${lesson.html}</div><form id="quiz" class="quiz"><div class="eyebrow">A MOMENT TO REFLECT</div><h2>Check your understanding</h2><fieldset><legend>${escape(lesson.quiz.question)}</legend>${lesson.quiz.options.map((option, i) => `<label class="option"><input required type="radio" name="answer" value="${i}"><span>${escape(option)}</span></label>`).join("")}</fieldset><button class="button" type="submit">Check my answer ↗</button><div id="feedback" role="status"></div></form><div id="next-step"></div></article></section>`;
        document.querySelector("#quiz").addEventListener("submit", (event) => {
            event.preventDefault();
            const correct = grade(
                state,
                course,
                lesson,
                Number(new FormData(event.target).get("answer")),
            );
            save();
            document.querySelector("#feedback").innerHTML =
                `<p class="notice ${correct ? "" : "amber"}">${correct ? "That’s right. Lesson complete!" : "Not quite. Revisit the lesson and try again."}</p>`;
            if (correct) {
                const next = course.lessons.find(
                    (l) => !(state.passed[course.id] || []).includes(l.id),
                );
                document.querySelector("#next-step").innerHTML = next
                    ? button("Next lesson", `lesson/${course.id}/${next.id}`)
                    : button(
                          "View your certificate",
                          `certificate/${course.id}`,
                      );
                document.querySelector(".reader-nav p").textContent =
                    `${passed(course)} / ${course.lessons.length} complete`;
                document.querySelector(
                    ".reader-nav a[aria-current]",
                ).textContent = `✓ · ${lesson.title}`;
            }
        });
    } else if (
        page === "certificate" &&
        course &&
        state.completed[course.id] &&
        passed(course) === course.lessons.length
    ) {
        main.innerHTML = `<section class="section"><div class="certificate"><div class="eyebrow">FIELDNOTES ACADEMY · DEMO CERTIFICATE</div><h1>A small milestone.<br>A lasting skill.</h1><p>This celebrates</p><h2>Alex Morgan</h2><p>for completing all lessons and knowledge checks in</p><h2>${escape(course.title)}</h2><p>${new Date(state.completed[course.id]).toLocaleDateString("en-GB", { year: "numeric", month: "long", day: "numeric" })}</p><p>Cynthia Owolabi · Fieldnotes Academy</p><small>Fictional portfolio demonstration. Not an accredited qualification.</small></div><div class="certificate-actions"><button class="button" id="print">Print certificate</button><a href="#/learning">Back to my learning</a></div></section>`;
        document
            .querySelector("#print")
            .addEventListener("click", () => window.print());
    } else if (page === "about") {
        main.innerHTML = `<section class="section narrow"><div class="eyebrow">BEHIND THE BUILD</div><h1>A thoughtful interface.<br>A working Laravel backend.</h1><p class="lead">Fieldnotes Academy is a PHP / Laravel portfolio project by Cynthia Owolabi.</p><h2>Try it here</h2><p>This GitHub Pages preview runs entirely in your browser with fictional data. Enroll, read nine complete lessons, answer quizzes and earn a demo certificate. Progress stays on this device when browser storage is available. Reset demo starts a fresh visit.</p><h2>Explore the PHP implementation</h2><p>The repository contains the real Laravel application: server-side grading, learner and instructor authorization, course publishing, private certificates, queued notifications and resumable cohort imports with safe undo.</p><p>The public preview does not run PHP, authenticate users, send email or process background jobs. Its answers and progress are visible in browser code. Those boundaries are enforced on the server in the Laravel application.</p><h2>A standalone CSV package</h2><p><code>cynthiaowolabi/csv-kit</code> is a streaming PHP CSV reader with strict validation, row numbers and resume cursors. It has independent Composer metadata and PHPUnit tests. The Laravel application owns queues, enrollment transactions and undo.</p><p><a class="button" href="${source}">Read the code & setup guide ↗</a></p><p><a href="${source}/tree/main/packages/csv-kit">Explore the CSV package ↗</a> · <a href="https://adebolaowolabi32.github.io/">Meet Cynthia ↗</a></p></section>`;
    } else
        main.innerHTML = `<section class="section narrow"><h1>Let’s find your next lesson.</h1><p>This page is unavailable, or you haven’t unlocked it yet.</p>${button("Explore courses", "catalog")}</section>`;
    document.title = `${course?.title || (page === "learning" ? "My learning" : page === "about" ? "Behind the build" : "Learn something worth knowing")} · Fieldnotes Academy`;
    window.scrollTo(0, 0);
}
document.querySelector("#reset").addEventListener("click", () => {
    if (window.confirm("Reset all demo progress on this browser?")) {
        state = freshState();
        save();
        if (location.hash === "#/learning") render();
        else location.hash = "/learning";
    }
});
try {
    const response = await fetch(new URL("courses.json", import.meta.url));
    if (!response.ok) throw new Error("Course data unavailable");
    courses = await response.json();
    render();
    window.addEventListener("hashchange", () => {
        render();
        main.focus({ preventScroll: true });
    });
} catch {
    main.innerHTML =
        '<section class="section"><h1>The field guide couldn’t load.</h1><p>Please reload the page and try again.</p></section>';
}
