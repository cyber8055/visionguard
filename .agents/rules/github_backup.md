---
name: GitHub Auto-Backup
description: Ensure the AI agent always commits and pushes code to GitHub after making modifications, and explains how the user can revert changes.
---

# GitHub Auto-Backup Rule

1. **Commit and Push After Changes**: Whenever you (the AI) make any modifications to the codebase (creating, updating, or deleting files), you MUST automatically run the following commands to back up the changes to GitHub before ending your turn:
   - `git add .`
   - `git commit -m "Auto-backup: [Brief description of what you changed]"`
   - `git push origin main`

2. **Revert Instructions**: In your final response to the user, you MUST include a short, standard message reminding them how they can revert the changes if something goes wrong. For example:
   > 💡 **Backup Complete**: Your code has been safely pushed to GitHub. If you experience any critical errors and need to instantly undo my changes, you can open your terminal and run: `git reset --hard HEAD~1` (to completely erase the last change) or use the GitHub Desktop app to revert the commit.
