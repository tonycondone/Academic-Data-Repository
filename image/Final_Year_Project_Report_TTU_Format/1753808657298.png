#!/bin/bash

# === Auto Git Push Script (Cursor/VSCode) ===
# Uses folder name as repo name

# Get current folder name as repo name
REPO_NAME=$(basename "$PWD")
BRANCH_NAME="main"
GITHUB_USERNAME="tonycondone"  # << CHANGE THIS
# Check if a Git repo exists
if [ -d ".git" ]; then
  echo "➤ This is a Git repo, skipping auto-push."
  exit 0
fi

# Initial Git setup if not already a Git repo
if [ ! -d ".git" ]; then
  echo "Initializing Git for repo: $REPO_NAME"
  git init
  git config user.name "tonycondone"          # << CHANGE THIS
  git config user.email "touyboateng339@gmail.com"   # << CHANGE THIS
  git add .
  git commit -m "Initial commit"

  echo "➤ Now manually connect your GitHub repo:"
  echo "    1. Create a repo on GitHub named: $REPO_NAME"
  echo "    2. Run:"
  echo "       git remote add origin https://github.com/$GITHUB_USERNAME/$REPO_NAME.git"
  echo "       git branch -M $BRANCH_NAME"
  echo "       git push -u origin $BRANCH_NAME"
  echo "➤ Exiting to let you connect remote."
  exit 1
fi

# === Auto-push loop ===
while true; do
  git add .
  git diff --cached --quiet && sleep 2 && continue
  git commit -m "auto: $(date)"
  git push origin $BRANCH_NAME
  sleep 2
done
