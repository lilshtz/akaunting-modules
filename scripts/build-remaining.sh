#!/bin/bash
set -uo pipefail
cd /home/valleybird/projects/akaunting-setup
LOG=logs/forge-build-codex.log
mkdir -p logs

TASKS=(
  "13-stripe-gateway"
  "14-paypal-sync"
  "15-projects-milestones"
  "16-projects-time-budget"
  "17-expense-claims"
  "18-payroll-calendars"
  "19-payroll-payslips"
  "20-crm-contacts-companies"
  "21-crm-deals-pipeline"
  "22-inventory-warehouses"
  "23-inventory-variants-barcodes"
  "24-budgets"
  "25-roles-permissions"
  "26-pos"
  "27-appointments-leave"
  "28-auto-schedule-reports"
)

for task in "${TASKS[@]}"; do
  echo "[$(date)] Starting $task" | tee -a "$LOG"
  
  TASK_CONTENT=$(cat "tasks/${task}.md")
  COMMIT_MSG=$(grep -A1 "^## Commit Message" "tasks/${task}.md" | tail -1 | sed 's/`//g')
  
  codex exec --full-auto "${TASK_CONTENT}

Build ALL files described above. Write complete production PHP code — every migration, model, controller, view, route, listener. Reference modules/_reference_OfflinePayments/ for module structure and modules/_reference_models/ for Akaunting core model patterns. When done: git add -A && git commit -m '${COMMIT_MSG}'" 2>&1 | tee -a "$LOG"
  
  EXIT=$?
  echo "[$(date)] Task $task exit: $EXIT" | tee -a "$LOG"
  
  git push origin master 2>&1 | tee -a "$LOG"
  
  if [ $EXIT -ne 0 ]; then
    echo "[$(date)] Task $task FAILED — retrying once" | tee -a "$LOG"
    codex exec --full-auto "${TASK_CONTENT}

Build ALL files. Write complete production PHP code. git add -A && git commit when done." 2>&1 | tee -a "$LOG"
    git push origin master 2>&1 | tee -a "$LOG"
  fi
done

echo "[$(date)] All remaining tasks complete" | tee -a "$LOG"
openclaw system event --text "Akaunting modules build: all 16 remaining tasks complete via Codex" --mode now
