#!/bin/bash
set -uo pipefail
cd /home/valleybird/projects/akaunting-setup
LOG=logs/forge-build-codex-v2.log
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
  
  codex exec --full-auto "Read the file tasks/${task}.md in this project directory. It contains a complete specification for an Akaunting Laravel module to build. Build every file it describes. Use modules/_reference_OfflinePayments/ as the structural reference. Write production PHP. When finished, run: git add -A && git commit -m 'feat(modules): ${task}'" 2>&1 | tee -a "$LOG"
  
  EXIT=$?
  echo "[$(date)] Task $task exit: $EXIT" | tee -a "$LOG"
  git push origin master 2>&1 | tee -a "$LOG"
done

echo "[$(date)] All remaining tasks complete" | tee -a "$LOG"
openclaw system event --text "Akaunting modules: all 16 remaining tasks complete via Codex" --mode now
