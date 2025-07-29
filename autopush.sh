#!/bin/bash

# === Auto Git Push Script (Cursor/VSCode) ===
# Uses folder name as repo name

# Get current folder name as repo name
REPO_NAME=$(basename "$PWD")
BRANCH_NAME="main"
GITHUB_USERNAME="tonycondone"  # << CHANGE THIS
REPO_VISIBILITY="private" # << CHANGE THIS to "public" or "private"

# Initial Git setup if not already a Git repo
if [ ! -d ".git" ]; then
  echo "Initializing Git for repo: $REPO_NAME
  git init
  git branch -M $BRANCH_NAME
  git config user.name "$GITHUB_USERNAME"
  git config user.email "touyboateng339@gmail.com" # << CHANGE THIS
  git add .
  git commit -m "Initial commit"

  # Check if gh is installed
  if ! command -v gh &> /dev/null
  then
      echo "GitHub CLI (gh) could not be found. Please install it to create repositories automatically."
      echo "Or create the repo '$REPO_NAME' manually on GitHub and connect it, then run this script again."
      exit 1
  fi

  echo "➤ Creating GitHub repo '$REPO_NAME' with visibility '$REPO_VISIBILITY' and pushing."
  gh repo create "$REPO_NAME" --source=. --remote=origin --push --$REPO_VISIBILITY
fi

# === Auto-push loop ===
echo "➤ Starting auto-push for repo: $REPO_NAME"
while true; do
  git add .
  git diff --cached --quiet && sleep 2 && continue
  git commit -m "auto: $(date)"
  git push origin $BRANCH_NAME
  sleep 2
done
