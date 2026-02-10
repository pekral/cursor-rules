For the given URL addresses of problems, run .cursor/skills/auto-fix-bug/SKILL.md (if the problem is closed or not ready for processing, do nothing!) and then proceed according to the defined points:

1. After writing the code, refactor according to the rules in .cursor/skills/class-refactoring/SKILL.md for all new PHP classes, fix DRY, apply solid principles (if appropriate), and SRP!

2. Run the fixes (analyze composer.json and run the fixers, choose the most suitable script(s)), if available.

3. Perform the code review defined in .cursor/skills/code-review/SKILL.md. If any critical issues are found, ask the user and let them choose what to fix. If they choose something, modify the code and start iterating again from step 1.

4. Test the functionality according to the assignment. If there are any example files, analyze them and modify the existing ones for the current changes or create new example files. If none exist, skip this point.
