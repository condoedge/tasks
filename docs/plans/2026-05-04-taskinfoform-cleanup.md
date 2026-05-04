# TaskInfoForm Cleanup — Extract Assignables Complexity

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Slim down `TaskInfoForm` (currently 545 lines) to its real responsibility — composing the task drawer form — by extracting the polymorphic assignment logic introduced in `f61df43` into a dedicated trait, a small registry, and a few Task model methods.

**Architecture:** Three collaborating pieces.
1. **`TaskAssignableRegistry`** — pure logic; normalizes the `kompo-tasks.assignables` config and answers stateless questions about classes/types/keys. Replaces ~10 helper methods on the form *and* dedupes the parallel logic already in `Task::taskAssignableClasses()`.
2. **Task model persistence methods** — `Task::applyAssignment()` and `Task::takeOwnership()` own the writes that the form currently orchestrates.
3. **`HandlesTaskAssignables` trait** — form-side concerns only: the assignment UI sub-tree, request-reading, validation rules, and the `beforeSave/afterSave` hooks for assignments.

After the refactor `TaskInfoForm` keeps only: form lifecycle, the base inputs (title/status/visibility/tags/team-search), element layout (`taskInfoElements`, `panelWrapper`), kompo endpoints (`assignItToMyself`, `searchTeamChildren`, `retrieveTeamChildren`), `taskDeleteLink`/`taskLinksCard`, and `rules()` (which merges the trait's assignable rules).

**Tech Stack:** PHP 8 / Laravel / Kompo. No new dependencies.

---

## Current responsibility map (for reference)

`TaskInfoForm` currently mixes five concerns. Method classification:

| Concern | Methods | Destination |
|---|---|---|
| Form layout / base inputs | `taskInfoElements`, `panelWrapper`, `titleInput`, `statusInput`, `visibilityAndOptions`, `taskLinksCard`, `taskDeleteLink`, `submitsRefresh`, `taskRelatedLists` | **stays** in form |
| Kompo endpoints | `assignItToMyself`, `searchTeamChildren`, `retrieveTeamChildren` | **stays** in form (delegate body) |
| Assignable UI | `assignmentPanel`, `teamInput`, `assignmentTypeInput`, `assignableInputs`, `assignableInput`, `takeAssignationCard`, `authUserCanTakeAssignation`, `hasNonUserAssignation` | → trait |
| Request/state read | `selectedTeamId`, `selectedAssignmentType`, `selectedAssignableIdsForType`, `selectedAssignableIdsFromRequest`, `taskAssignableOptions`, `shouldSyncAssignmentFromRequest`, `assignableValidationRules` | → trait |
| Persistence | `fillAssignmentBeforeSave`, `syncTaskAssignationsFromRequest`, `assignableModels` | → trait calls Task model |
| Pure config / class logic | `taskAssignableConfigs`, `normalizeAssignableConfig`, `taskAssignableConfig`, `assignmentTypeForClass`, `assignationMatchesClass`, `assignationClass`, `classesMatch`, `assignableKeyFromClass`, `isUserAssignableClass`, `taskAssignableKeyName` | → `TaskAssignableRegistry` |

`Task::taskAssignableClasses()` (`src/Models/Task.php:142-151`) already duplicates the "unwrap `model`/`class` key from config" logic — the registry replaces that too.

---

## Task 1: `TaskAssignableRegistry`

**Files:**
- Create: `src/Models/Tasks/TaskAssignableRegistry.php` (or `src/Tasks/TaskAssignableRegistry.php` — match existing namespacing)
- Modify: `src/Models/Task.php:142-151` (replace `taskAssignableClasses`)

**Step 1: Build the registry class**

Public surface:

```php
namespace Kompo\Tasks\Models\Tasks;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Kompo\Auth\Facades\UserModel;
use Kompo\Tasks\Models\Contracts\TaskAssignable;
use Kompo\Tasks\Models\TaskAssignation;

class TaskAssignableRegistry
{
    /** @return Collection<string, array> keyed by type slug ('person', 'position', ...) */
    public static function configs(): Collection { /* current taskAssignableConfigs() */ }

    public static function config(string $type): array { /* current taskAssignableConfig() */ }

    public static function classes(): Collection
    {
        return static::configs()
            ->pluck('model')
            ->filter(fn ($c) => is_subclass_of($c, TaskAssignable::class))
            ->values();
    }

    public static function typeForClass(string $class): ?string { /* assignmentTypeForClass() */ }

    public static function classFromAssignation($assignation): ?string
    {
        return Relation::getMorphedModel($assignation->assignable_type) ?: $assignation->assignable_type;
    }

    public static function assignationMatchesClass($assignation, string $class): bool { /* assignationMatchesClass() */ }

    public static function classesMatch(string $a, string $b): bool { /* classesMatch() */ }

    public static function isUserClass(?string $class): bool { /* isUserAssignableClass() */ }

    public static function keyNameFor(string $class): string
    {
        return TaskAssignation::taskAssignableKeyName(new $class());
    }

    protected static function normalize($config, $key): ?array { /* normalizeAssignableConfig() */ }

    protected static function keyFromClass(string $class): string { /* assignableKeyFromClass() */ }
}
```

Notes:
- Keep methods static and stateless. Cache `configs()` per request via a memoized static property if needed (current call sites recompute on every invocation).
- Default fallback config (the `'person' => [..UserModel..]` block) lives here, not on the form.

**Step 2: Replace `Task::taskAssignableClasses`**

In `src/Models/Task.php:142-151`, replace the body with:

```php
protected static function taskAssignableClasses()
{
    return TaskAssignableRegistry::classes();
}
```

…or inline the call at `Task.php:121` and remove the helper. Keep whichever change is smaller.

**Step 3: No tests** — there is no test suite in this package. Verify by running the form in the drawer once Task 4 is done.

---

## Task 2: Task model persistence methods

**Files:**
- Modify: `src/Models/Task.php` (add two methods near the existing `/* ACTIONS */` block, ~line 83)

**Step 1: Add `applyAssignment`**

```php
public function applyAssignment(string $class, \Illuminate\Support\Collection $ids): void
{
    $isUser = TaskAssignableRegistry::isUserClass($class);

    $this->assigned_to = $isUser && $ids->count() === 1 ? $ids->first() : null;

    if (!$this->id) {
        // beforeSave path — the caller will save and then re-call after persisting.
        return;
    }

    $this->taskAssignations()->delete();
    $this->unsetRelation('taskAssignations');

    if ($isUser && $ids->count() === 1) {
        return;
    }

    $keyName = TaskAssignableRegistry::keyNameFor($class);
    $models = $class::query()->whereIn($keyName, $ids->all())->get()->all();

    TaskAssignation::createForMany($this->id, $models);
    $this->unsetRelation('taskAssignations');
}
```

This consolidates `fillAssignmentBeforeSave` + `syncTaskAssignationsFromRequest` + `assignableModels` from the form. The trait will call it twice (before/after save) — once to set `assigned_to` pre-insert, once to sync the morph rows post-insert. The early `if (!$this->id)` return mirrors the form's current behaviour.

**Step 2: Add `takeOwnership`**

```php
public function takeOwnership(int $userId): void
{
    $this->assigned_to = $userId;
    $this->save();
    $this->taskAssignations()->delete();
    $this->unsetRelation('taskAssignations');
}
```

This is the body of `assignItToMyself` minus the auth check, which stays on the form.

---

## Task 3: `HandlesTaskAssignables` trait

**Files:**
- Create: `src/Components/Tasks/Concerns/HandlesTaskAssignables.php`

**Step 1: Move methods into the trait**

Trait holds (cut & paste from `TaskInfoForm`, then rewrite to use the registry / model):

- UI: `assignmentPanel`, `teamInput`, `assignmentTypeInput`, `assignableInputs`, `assignableInput`, `takeAssignationCard`, `authUserCanTakeAssignation`, `hasNonUserAssignation`
- State: `selectedTeamId`, `selectedAssignmentType`, `selectedAssignableIdsForType`, `selectedAssignableIdsFromRequest`, `taskAssignableOptions`, `shouldSyncAssignmentFromRequest`
- Lifecycle: `fillAssignmentBeforeSave`, `syncTaskAssignationsFromRequest` — bodies become single calls into `TaskAssignableRegistry` + `$this->model->applyAssignment(...)`
- Validation: `assignableValidationRules`

Each method that previously called `$this->taskAssignableConfigs()`, `$this->isUserAssignableClass(...)`, etc. now calls `TaskAssignableRegistry::configs()` / `::isUserClass(...)`. Drop the helpers `taskAssignableConfigs`, `taskAssignableConfig`, `assignmentTypeForClass`, `assignationMatchesClass`, `assignationClass`, `classesMatch`, `assignableKeyFromClass`, `isUserAssignableClass`, `taskAssignableKeyName`, `normalizeAssignableConfig`, `assignableModels` — the registry/model own them now.

**Step 2: Trait expects a `$this->model` Task** — document this in a one-line PHPDoc on the trait so it's obvious.

---

## Task 4: Slim `TaskInfoForm`

**Files:**
- Modify: `src/Components/Tasks/TaskInfoForm.php`

**Step 1: Add `use HandlesTaskAssignables;`** at the top of the class.

**Step 2: Rewrite the lifecycle hooks**

```php
public function beforeSave()
{
    if (request()->has('status')) {
        $this->model->handleStatusChange(request('status'));
    }
    $this->fillAssignmentBeforeSave();
}

public function afterSave()
{
    $this->syncTaskAssignationsFromRequest();
}
```

(Same shape as today — the bodies of those two methods now live in the trait.)

**Step 3: Rewrite `assignItToMyself`**

```php
public function assignItToMyself()
{
    if (!$this->authUserCanTakeAssignation()) {
        abort(403, __('kompo.unauthorized-action'));
    }
    $this->model->takeOwnership(auth()->id());
}
```

**Step 4: Rewrite `rules()`**

```php
public function rules()
{
    return [
        'title' => 'required|max:255',
        'status' => 'required',
        'team_id' => 'required|exists:teams,id',
        'visibility' => 'required|in:' . collect(TaskVisibilityEnum::cases())->pluck('value')->join(','),
        'assignment_type' => 'required|in:' . TaskAssignableRegistry::configs()->keys()->join(','),
        'task_assignable_ids' => 'required',
        'urgent' => 'boolean',
    ] + $this->assignableValidationRules();
}
```

**Step 5: Delete migrated methods** from the form. After deletion the file should be ~150–180 lines containing only the responsibilities listed in the table at the top of this plan.

**Step 6: Verify imports** — drop the `Relation`, `UserModel`, `TaskAssignation` imports from the form (only the trait/registry/model need them).

---

## Task 5: Smoke test in the browser

There is no automated test suite. Manually verify in the drawer:

1. Open a task whose only assignations are roles (no `assigned_to`) and where the auth user holds one of those roles → the "take ownership" card appears, button works, page refreshes with `assigned_to = auth()->id()`.
2. Create a new task, pick a single user → `assigned_to` set, no `task_assignations` rows.
3. Create a new task, pick multiple users → `assigned_to` null, N `task_assignations` rows of type `User`.
4. Edit a task and switch from `person` to `position` → assignations swap morph types correctly, `assigned_to` cleared.
5. Validation: submit without picking an assignable → form rejects with the existing required-message.

If any step regresses, compare against `git show f61df43` before declaring done.

---

## Out of scope (do NOT do in this PR)

- Renaming columns / changing the DB schema.
- Touching `TaskForm.php`, `TasksKanban.php`, or other consumers — the public method names (`taskInfoElements`, `assignItToMyself`, `taskRelatedLists`, `submitsRefresh`) stay the same.
- Adding a config publish file or new translation keys.
- Adding tests retroactively for behaviour that didn't have tests before.

---

## Remember
- DRY — `Task::taskAssignableClasses` and the form's `taskAssignableConfigs` collapse into one registry.
- YAGNI — no abstract `TaskAssignmentManager` interface, no events, no value objects beyond the registry.
- Do not commit. Hand the diff to the user for review when Task 5 is green.
