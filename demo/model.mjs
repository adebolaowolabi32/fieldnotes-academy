export function freshState() {
    return {
        version: 1,
        enrolled: [1],
        passed: {},
        attempts: {},
        completed: {},
    };
}
export function readState(storage) {
    try {
        const state = JSON.parse(storage.getItem("fieldnotes-preview-v1"));
        if (
            state?.version === 1 &&
            Array.isArray(state.enrolled) &&
            state.enrolled.every(Number.isInteger) &&
            ["passed", "attempts", "completed"].every(
                (key) =>
                    state[key] &&
                    typeof state[key] === "object" &&
                    !Array.isArray(state[key]),
            ) &&
            Object.values(state.passed).every(
                (value) =>
                    Array.isArray(value) && value.every(Number.isInteger),
            ) &&
            Object.values(state.attempts).every(
                (value) => Number.isInteger(value) && value >= 0,
            ) &&
            Object.values(state.completed).every(
                (value) =>
                    typeof value === "string" &&
                    !Number.isNaN(Date.parse(value)),
            )
        )
            return state;
    } catch {}
    return freshState();
}
export function persist(storage, state) {
    try {
        storage.setItem("fieldnotes-preview-v1", JSON.stringify(state));
        return true;
    } catch {
        return false;
    }
}
export function enroll(state, course) {
    if (!state.enrolled.includes(course.id)) state.enrolled.push(course.id);
}
export function grade(
    state,
    course,
    lesson,
    answer,
    now = new Date().toISOString(),
) {
    if (
        !state.enrolled.includes(course.id) ||
        !course.lessons.some((item) => item.id === lesson.id) ||
        !Number.isInteger(answer) ||
        answer < 0 ||
        answer >= lesson.quiz.options.length
    )
        throw new Error("Choose an answer from an enrolled course.");
    state.attempts[lesson.id] = (state.attempts[lesson.id] || 0) + 1;
    const correct = answer === lesson.quiz.correct;
    if (correct) {
        state.passed[course.id] ||= [];
        if (!state.passed[course.id].includes(lesson.id))
            state.passed[course.id].push(lesson.id);
        if (
            course.lessons.every((item) =>
                state.passed[course.id].includes(item.id),
            )
        )
            state.completed[course.id] ||= now;
    }
    return correct;
}
