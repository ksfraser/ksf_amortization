---
name: get-shit-done
description: Meta-prompting, context engineering, and spec-driven development system for Claude Code. Manages projects through phases with discussion, planning, execution, and verification workflows.
author: TÂCHES (glittercowboy)
version: 1.30.0
url: https://github.com/gsd-build/get-shit-done
---

# Get Shit Done (GSD) - Spec-Driven Development

## When to Use This Skill

- Building new projects or features from scratch
- You have a clear idea of what you want but need GSD to manage the workflow
- Need context management for large codebases (prevents context rot)
- Want atomic commits, clear phase tracking, and automatic verification
- Multi-step projects that need division into manageable phases
- Existing codebase where you need to add new features systematically

## Core Workflow

### 1. Initialize Project
```
/gsd:new-project
```
- Questions phase to understand goals, constraints, tech preferences
- Research phase spawns parallel agents to investigate domain
- Requirements extraction (v1, v2, out of scope)
- Roadmap creation with phases mapped to requirements

### 2. Discuss Phase
```
/gsd:discuss-phase [N]
```
Captures implementation decisions BEFORE planning:
- Visual features → Layout, density, interactions, empty states
- APIs/CLIs → Response format, flags, error handling
- Content → Structure, tone, depth, flow
- Organization → Grouping criteria, naming, exceptions

### 3. Plan Phase
```
/gsd:plan-phase [N]
```
- Researches implementation approach
- Creates 2-3 atomic task plans with XML structure
- Verifies plans achieve phase goals

### 4. Execute Phase
```
/gsd:execute-phase [N]
```
- Runs plans in parallel waves (dependency-aware)
- Fresh 200k context per plan (no degradation)
- Atomic git commits per task
- Auto-verification against goals

### 5. Verify Work
```
/gsd:verify-work [N]
```
- Manual user acceptance testing
- Auto-diagnosis of failures
- Creates fix plans if issues found

### 6. Ship & Next Milestone
```
/gsd:ship [N]
/gsd:complete-milestone
/gsd:new-milestone
```

## Quick Mode (Ad-hoc Tasks)
```
/gsd:quick [--full] [--discuss] [--research]
```
- Skips optional steps (no research, plan-check by default)
- `--discuss`: Lightweight discussion
- `--research`: Focused research before planning
- `--full`: Enables plan-checking and verification

## Key Concepts

### Context Engineering
GSD maintains organized context across sessions:
- `PROJECT.md` - Project vision, always loaded
- `REQUIREMENTS.md` - Scoped v1/v2 requirements
- `ROADMAP.md` - Phase roadmap and progress
- `STATE.md` - Decisions, blockers, position
- `PLAN.md` - Atomic tasks with XML structure
- `SUMMARY.md` - Commit history of what changed

### XML Prompt Formatting
Every plan structured for Claude clarity:
```xml
<task type="auto">
  <name>Create login endpoint</name>
  <files>src/api/auth/route.ts</files>
  <action>Implement JWT auth with httpOnly cookies</action>
  <verify>curl returns 200 + Set-Cookie header</verify>
  <done>Valid credentials return cookie, invalid return 401</done>
</task>
```

### Wave Execution
Plans grouped by dependencies:
- WAVE 1 (parallel): Independent plans
- WAVE 2 (parallel): Plans dependent on Wave 1
- WAVE 3: Sequential if dependencies require

### Atomic Commits
Each task produces its own commit:
```
abc123f feat(01-01): user model
def456g feat(01-01): password hashing
hij789k feat(01-01): login endpoint
```

## Configuration

### Model Profiles
- `quality`: Opus for all stages (highest quality, highest cost)
- `balanced` (default): Opus planning, Sonnet execution
- `budget`: Sonnet for all stages
- `inherit`: Use current runtime model

Switch: `/gsd:set-profile budget`

### Workflow Agents
Toggle during `/gsd:settings`:
- `workflow.research`: Research before planning (default: true)
- `workflow.plan_check`: Verify plans before execution (default: true)
- `workflow.verifier`: Verify deliverables after execution (default: true)
- `workflow.auto_advance`: Auto-chain discuss → plan → execute (default: false)
- `workflow.discuss_mode`: 'discuss' or 'assumptions' (codebase-first)

## Advanced Commands

### Workstreams (Parallel Work)
```
/gsd:workstreams create <name>    # Create parallel workstream
/gsd:workstreams list             # Show all workstreams
/gsd:workstreams switch <name>    # Switch active workstream
```

### Management
```
/gsd:progress              # Current status and next step
/gsd:next                  # Auto-detect and run next step
/gsd:pause-work            # Handoff to next session
/gsd:resume-work           # Restore from last session
/gsd:session-report        # Generate session summary
/gsd:health                # Validate .planning/ integrity
/gsd:stats                 # Project statistics
```

### Code Quality
```
/gsd:review                # Cross-AI peer review of phase
/gsd:pr-branch             # Create clean PR branch (filters .planning/)
/gsd:audit-uat             # Find phases missing verification
```

### Backlog & Ideas
```
/gsd:add-backlog <desc>    # Parking lot for future ideas
/gsd:plant-seed <idea>     # Forward-looking ideas with triggers
/gsd:add-todo [desc]       # Quick todo capture
```

## Best Practices

1. **Use `/gsd:map-codebase` first** if adding to existing project
2. **Always run `/gsd:discuss-phase`** before planning (shapes implementation)
3. **Review **REQUIREMENTS.md before planning** (alignment check)
4. **Use `--full` flag in quick mode** for critical UI work
5. **Check `/gsd:progress`** when returning to project
6. **Run `/gsd:audit-milestone`** before shipping milestones
7. **Prefer vertical slices** (feature end-to-end) over horizontal layers (all models, then all APIs)

## Security

- **Path traversal prevention**: File paths validated within project directory
- **Prompt injection detection**: User text scanned before entering artifacts
- **Shell sanitization**: Arguments validated before shell interpolation
- **CI-ready scanner**: `prompt-injection-scan.test.cjs` validates all agent files

## Examples

### New Full-Stack Project
```
/gsd:new-project
# Answer questions about goals, tech stack, constraints
/gsd:discuss-phase 1
# Shape the first phase implementation
/gsd:plan-phase 1
# Research and plan the work
/gsd:execute-phase 1
# Implement everything atomically
/gsd:verify-work 1
# Test and confirm it works
/gsd:ship 1
# Create PR from verified work
```

### Add Feature to Existing Project
```
/gsd:map-codebase
# Analyze your current stack first
/gsd:new-milestone "Add dark mode"
# Start fresh milestone for new feature
/gsd:discuss-phase 1
# Define the implement approach
/gsd:plan-phase 1
# Research and create plans
/gsd:execute-phase 1
# Build it
```

### Quick Ad-hoc Task
```
/gsd:quick --full
# Add dark mode toggle to settings
```

## Tips

- **Faster intake**: Use `/gsd:discuss-phase <n> --batch` for grouped questions
- **Brownfield codebases**: Run `/gsd:map-codebase [area]` before new-project
- **Assumption mode**: Set `workflow.discuss_mode` to `assumptions` to analyze code instead of asking questions
- **Multi-project**: Use `/gsd:new-workspace` to work on multiple projects in isolation
- **Remote sessions**: Set `workflow.text_mode` to true for headless/text-only environments

## Links

- Repository: https://github.com/gsd-build/get-shit-done
- User Guide: https://github.com/gsd-build/get-shit-done/blob/main/docs/USER-GUIDE.md
- Discord: https://discord.gg/gsd
- Install: `npx get-shit-done-cc@latest`
