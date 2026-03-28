#!/usr/bin/env bash
# Run Akaunting module tasks sequentially via Codex
# Each task runs in a fresh Codex context window
set -euo pipefail

PROJECT_DIR="/home/valleybird/projects/akaunting-setup"
TASKS_DIR="$PROJECT_DIR/tasks-v2"
LOG_DIR="$PROJECT_DIR/logs"
mkdir -p "$LOG_DIR"

# Tasks to run (Task 01 already done)
TASKS=(
  "02-accounts-crud.md"
  "03-journals-crud.md"
  "04-reports-defaults.md"
  "05-bankfeeds-scaffold-csv.md"
  "06-bankfeeds-ofx-rules.md"
  "07-bankfeeds-matching-reconciliation.md"
)

echo "$(date '+%Y-%m-%d %H:%M:%S') Starting Akaunting module build — ${#TASKS[@]} tasks" | tee "$LOG_DIR/build.log"

for task_file in "${TASKS[@]}"; do
  task_name="${task_file%.md}"
  echo ""
  echo "========================================" | tee -a "$LOG_DIR/build.log"
  echo "$(date '+%Y-%m-%d %H:%M:%S') Starting: $task_name" | tee -a "$LOG_DIR/build.log"
  echo "========================================" | tee -a "$LOG_DIR/build.log"

  # Run Codex with the task
  codex --yolo exec "You are building Akaunting modules. Read tasks-v2/$task_file for the FULL specification. Also reference PRD-double-entry-bankfeeds.md for architecture rules.

CRITICAL RULES:
- company_id must be unsignedInteger (NOT bigInteger)
- NO foreign key constraints to core Akaunting tables
- Follow OfflinePayments module pattern for providers
- DB table prefix is 'nif_' (set in Laravel config, bare names in migrations)
- Test URLs must use http://100.83.12.126:8085 (NOT localhost — Host header matters)
- Use Akaunting Blade components (x-form, x-table, etc.)
- Controllers extend App\Abstracts\Http\Controller
- All queries scoped by company_id using company_id() helper

Build ALL files listed in the task. After building, deploy to Docker and verify using the deploy steps in the task file. Commit with the message specified in the task.

When completely finished with this task, run: openclaw system event --text 'Done: $task_name complete' --mode now" \
    2>&1 | tee "$LOG_DIR/$task_name.log"

  exit_code=${PIPESTATUS[0]}
  
  if [ $exit_code -ne 0 ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') FAILED: $task_name (exit $exit_code)" | tee -a "$LOG_DIR/build.log"
    openclaw system event --text "Akaunting build FAILED on $task_name — check logs" --mode now 2>/dev/null || true
    exit 1
  fi

  echo "$(date '+%Y-%m-%d %H:%M:%S') Completed: $task_name" | tee -a "$LOG_DIR/build.log"
  
  # Git commit after each task
  cd "$PROJECT_DIR"
  git add -A
  git commit -m "$(grep '^## Commit' "tasks-v2/$task_file" -A1 | tail -1 | sed 's/^`//;s/`$//')" 2>/dev/null || true
  
  sleep 5  # Brief pause between tasks
done

echo ""
echo "========================================" | tee -a "$LOG_DIR/build.log"
echo "$(date '+%Y-%m-%d %H:%M:%S') ALL TASKS COMPLETE" | tee -a "$LOG_DIR/build.log"
echo "========================================" | tee -a "$LOG_DIR/build.log"

# Push to GitHub
cd "$PROJECT_DIR"
git push origin main 2>/dev/null || git push 2>/dev/null || true

openclaw system event --text "Akaunting build COMPLETE — all 7 tasks done. Double-Entry + Bank Feeds ready for browser testing." --mode now 2>/dev/null || true
