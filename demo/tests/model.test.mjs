import { test } from "node:test";
import assert from "node:assert/strict";
import { freshState, readState, persist, enroll, grade } from "../model.mjs";
const course = {
    id: 1,
    lessons: [
        { id: 1, quiz: { options: ["no", "yes"], correct: 1 } },
        { id: 2, quiz: { options: ["yes", "no"], correct: 0 } },
    ],
};
test("wrong answers count as attempts but never complete lessons", () => {
    const state = freshState();
    assert.equal(grade(state, course, course.lessons[0], 0), false);
    assert.equal(state.attempts[1], 1);
    assert.deepEqual(state.passed, {});
});
test("repeated passes are idempotent; certificate date is stable", () => {
    const state = freshState();
    grade(state, course, course.lessons[0], 1);
    grade(state, course, course.lessons[0], 1);
    assert.equal(state.passed[1].length, 1);
    assert.equal(state.completed[1], undefined);
    grade(state, course, course.lessons[1], 0, "2026-10-09T00:00:00Z");
    grade(state, course, course.lessons[1], 0);
    assert.equal(state.completed[1], "2026-10-09T00:00:00Z");
});
test("requires enrollment and valid answer", () => {
    const state = freshState();
    state.enrolled = [];
    assert.throws(() => grade(state, course, course.lessons[0], 1));
    enroll(state, course);
    enroll(state, course);
    assert.deepEqual(state.enrolled, [1]);
    assert.throws(() => grade(state, course, course.lessons[0], 9));
});
test("blocked or malformed storage falls back safely", () => {
    assert.deepEqual(readState(undefined), freshState());
    assert.equal(persist(undefined, freshState()), false);
    for (const value of [
        "bad",
        "null",
        '{"version":1,"enrolled":[],"passed":{"1":null},"attempts":{},"completed":{}}',
    ])
        assert.deepEqual(readState({ getItem: () => value }), freshState());
});
test("saved progress survives reload", () => {
    let data;
    const storage = { setItem: (_, v) => (data = v), getItem: () => data };
    const state = freshState();
    grade(state, course, course.lessons[0], 1);
    assert.equal(persist(storage, state), true);
    assert.deepEqual(readState(storage), state);
});
