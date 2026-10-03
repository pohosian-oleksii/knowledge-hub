---
name: spec-loop
description: Use when a coding task should run end-to-end through requirements analysis, interactive planning, implementation, requirements verification, and standards review — looping back on failure until both pass. Use when the user says "spec loop", "run the loop", or gives a task (Jira ticket, pasted requirements, free text) and wants it driven start to finish.
---

# Spec Loop

## Overview

Drive a task through four checkpoints instead of one-shot implementation: understand it, plan it with the user, build it, then verify it twice — once against the business requirement, once against code standards. Loop back to implementation on either failure. Stop only when both pass or a round cap is hit.

Core rule: **do not report done because code changed; report done because the spec says it's satisfied and `code-reviewer` has no findings.**

## Start Context

The invocation arguments are the seed for `requirements-analyst` — whatever the user passes after `/spec-loop`. Handle it by shape:

- **Pasted requirements / free text** — pass through as-is.
- **A file path or PR/diff reference** — read it first, pass the content.
- **A reference to an external ticket/doc with no available tool to fetch it** — ask the user to paste the content rather than guessing what it says.
- **Nothing given** — ask the user what the task is before spawning anything. Don't guess a task.

## Agents Used

- `requirements-analyst` — spec + open questions
- `code-implementer` — writes the code
- `requirements-verifier` — checks business-logic correctness against the spec
- `code-reviewer` — checks standards/quality

All four run in the **foreground** (`run_in_background: false`) — each step's input depends on the previous step's output, so nothing here parallelizes.

Never pass `model` on Agent calls — each agent's frontmatter decides. Agents with no `model:` field inherit the session model on their own; don't pass it for them either. The tool argument outranks frontmatter, so passing it silently discards the agent's configured model.

## Spec File

`requirements-analyst` writes the spec to `.spec-loop.md` in the top-level directory Claude was invoked from. This is the shared handoff artifact for the whole run — later steps read the path instead of the spec being re-pasted into every agent prompt:

- Step 2 resolves open questions in conversation; step 3 (main thread, not delegated) appends the approved plan to this same file before implementation starts.
- Steps 4 and 5 pass the path to `code-implementer`/`requirements-verifier`, which `Read` it themselves.
- Step "Cleanup" below deletes it once the run reaches a terminal state. Don't let it survive past one run — a stale `.spec-loop.md` sitting around will get read as current by the next invocation.

## Workflow

1. **Intake**
   - Resolve the start context per above.
   - Spawn `requirements-analyst` with the task content. It writes `.spec-loop.md` and returns: goal, numbered functional requirements, non-functional constraints, affected files, assumptions, open questions.

2. **Resolve open questions**
   - If `requirements-analyst` returned open questions, put them to the user directly (`AskUserQuestion` for concrete either/or choices, plain conversation for open-ended ones). Do not proceed on assumptions for anything flagged as an open question.

3. **Interactive plan**
   - Draft an implementation plan from the resolved spec. Use `EnterPlanMode`/`ExitPlanMode` so the user approves the approach before code gets written. This step is not delegated to a subagent — it needs real back-and-forth.
   - Once approved, append the plan (and the resolved answers from step 2) to `.spec-loop.md` yourself — `requirements-analyst` already returned, so this write happens on the main thread.

4. **Implement**
   - Spawn `code-implementer`, pointing it at `.spec-loop.md` for the spec + approved plan. It returns changed files, plan deviations (if any), and what was deliberately left out of scope.
   - If it reports the plan was infeasible, stop and resolve that with the user before continuing — don't let it silently improvise.

5. **Verify requirements**
   - Spawn `requirements-verifier`, pointing it at `.spec-loop.md` plus the implementer's changed-files list. It returns PASS or FAIL with per-requirement evidence.
   - **FAIL** → go to step 6.
   - **PASS** → go to step 7.

6. **Fix loop (requirements)**
   - Respawn `code-implementer` with the verifier's exact failure list (file/requirement/expected-vs-actual), not a paraphrase.
   - Return to step 5.
   - Count this as one round (see Round Cap below).

7. **Standards review**
   - Spawn `code-reviewer` on the changed files.
   - **Findings** → respawn `code-implementer` with the findings, then return to step 5 (re-verify requirements too — style fixes can break behavior). Count as one round.
   - **Clean** → done.

## Round Cap

Max **3** rounds through steps 5–7 combined. On round 3 still failing:

- Stop. Report exactly what's failing (verifier or reviewer output, whichever is still red), what's already been tried across the prior rounds, and the smallest decision needed from the user to unblock — don't keep spinning silently.
- Go to **Cleanup** — the escalation report already carries everything from `.spec-loop.md` that the user needs; don't leave the file around waiting on their decision.

## Cleanup

On either terminal state — completion (step 7 clean) or round-cap escalation — delete `.spec-loop.md` before ending the run. Do this last, after the completion report or escalation report has already been written to the conversation, since that report is the only place the spec/plan needs to survive once the file is gone.

## Human Gates

Stop and ask before:
- Committing, pushing, or opening a PR — this skill covers work-in-progress, not shipping.
- Any destructive operation surfaced mid-loop (migration rollback, force push, deleting data).
- Resolving an open question by guessing instead of asking, when the guess would be expensive to undo.

## Output Contract

When reporting completion, include:
- **Requirement checklist** — from `requirements-verifier`'s final PASS pass, one line per requirement.
- **Standards** — confirmation `code-reviewer` came back clean.
- **Files changed** — from `code-implementer`'s report.
- **Rounds used** — e.g. "2 of 3".
- **Scope notes** — anything deliberately left out, from step 4.

Don't report "done" unless both `requirements-verifier` and `code-reviewer` are clean in the same round.

## Limitations

- This is for implementation work with a checkable spec, not exploratory/research questions — those don't need the loop.
- `requirements-analyst`'s Agents Knowledge Hub reads can be stale; if `code-implementer` or `requirements-verifier` find the actual code disagrees with the spec's stated context, trust the code and flag the mismatch rather than forcing the spec.
- Round cap exists to prevent silent thrashing — don't raise it ad hoc without telling the user why.
