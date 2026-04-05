---
name: awesome-claude-skills
description: Curated collection of pre-built Claude Skills for enhancing productivity across document processing, development tools, data analysis, business, creative work, and 500+ app automation.
author: Composio
version: 1.0
url: https://github.com/ComposioHQ/awesome-claude-skills
---

# Awesome Claude Skills - Reference Library

## When to Use This Skill

- Need specialized functionality beyond general coding (document processing, data analysis, etc.)
- Want to integrate Claude with external apps (Gmail, Slack, GitHub, Notion, etc.)
- Looking for pre-built workflows for specific domains
- Need to enhance Claude with web scraping, image processing, or media tools
- Want to connect Claude to 500+ SaaS applications
- Looking for examples of well-structured Claude Skills to build from

## Skill Categories Available

### Document Processing
- **docx** - Create, edit, analyze Word docs with tracked changes, comments
- **pdf** - Extract text, tables, merge & annotate PDFs
- **pptx** - Read, generate, adjust slides, layouts, templates
- **xlsx** - Spreadsheet manipulation: formulas, charts, data transformations
- **Markdown to EPUB** - Convert markdown documents into professional ebook files

### Development & Code Tools
- **artifacts-builder** - Multi-component Claude.ai HTML artifacts using React/Tailwind/shadcn
- **Language-specific skills**: Move code quality, pypict testing, FFUF fuzzing
- **MCP Builder** - Guide for creating Model Context Protocol servers
- **Playwright Browser Automation** - Web application testing and validation
- **prompt-engineering** - Teach prompt engineering techniques and best practices
- **software-architecture** - Clean Architecture, SOLID principles, design patterns
- **test-driven-development** - TDD workflow skill with Red-Green-Refactor cycle
- **subagent-driven-development** - Dispatch independent subagents for rapid development
- **Connect** - Send emails, create issues, post to Slack, update databases

### Data & Analysis
- **CSV Data Summarizer** - Analyze CSV files and generate insights
- **deep-research** - Multi-step research using Gemini Deep Research Agent
- **postgres** - Execute safe read-only SQL queries against PostgreSQL
- **root-cause-tracing** - Trace errors deep in execution to find original trigger

### Business & Marketing
- **Brand Guidelines** - Apply Anthropic's official brand colors and typography
- **Competitive Ads Extractor** - Extract and analyze competitors' ads
- **Domain Name Brainstormer** - Generate and check domain name availability
- **Internal Comms** - Write company updates, newsletters, FAQs, status reports
- **Lead Research Assistant** - Identify and qualify high-quality leads

### Communication & Writing
- **article-extractor** - Extract full article text and metadata from web pages
- **brainstorming** - Transform rough ideas into fully-formed designs
- **Content Research Writer** - Conduct research, add citations, improve content
- **Meeting Insights Analyzer** - Analyze transcripts for behavioral patterns
- **NotebookLM Integration** - Chat directly with NotebookLM for source-grounded answers
- **Twitter Algorithm Optimizer** - Analyze and optimize tweets for maximum reach

### Creative & Media
- **Canvas Design** - Create visual art in PNG/PDF using design principles
- **imagen** - Generate images using Google Gemini image generation API
- **Image Enhancer** - Improve image quality: resolution, sharpness, clarity
- **Slack GIF Creator** - Create animated GIFs optimized for Slack
- **Theme Factory** - Apply professional font/color themes to artifacts
- **Video Downloader** - Download videos from YouTube and other platforms
- **youtube-transcript** - Fetch transcripts and prepare summaries

### Productivity & Organization
- **File Organizer** - Intelligently organize files by context and duplicates
- **Invoice Organizer** - Auto-organize invoices and receipts for tax prep
- **kaizen** - Apply continuous improvement methodology (Lean/Kaizen)
- **n8n-skills** - Understand and operate n8n workflows
- **Raffle Winner Picker** - Cryptographically secure random winner selection
- **Tailored Resume Generator** - Generate resumes tailored to job descriptions
- **tapestry** - Interlink and summarize related documents into knowledge networks

### App Automation via Composio (500+ Services)

#### CRM & Sales
- **Close, HubSpot, Pipedrive, Salesforce, Zoho CRM** Automation

#### Project Management
- **Asana, Basecamp, ClickUp, Jira, Linear, Monday, Notion, Todoist, Trello, Wrike**

#### Communication
- **Discord, Intercom, Microsoft Teams, Slack, Telegram, WhatsApp**

#### Email & Calendar
- **Gmail, Outlook, Postmark, SendGrid, Google Calendar, Calendly**

#### Code & DevOps
- **GitHub, GitLab, Bitbucket, CircleCI, Datadog, PagerDuty, Render, Sentry, Supabase, Vercel**

#### Storage & Files
- **Box, Dropbox, Google Drive, OneDrive**

#### Spreadsheets & Databases
- **Airtable, Coda, Google Sheets**

#### Social Media
- **Instagram, LinkedIn, Reddit, TikTok, Twitter/X, YouTube**

#### E-commerce & Payments
- **Shopify, Square, Stripe**

#### Design & Collaboration
- **Canva, Confluence, DocuSign, Figma, Miro, Webflow**

#### Analytics
- **Amplitude, Google Analytics, Mixpanel, PostHog, Segment**

## How Claude Skills Work

### Skill Structure
```
skill-name/
├── SKILL.md          # Skill instructions and metadata
├── scripts/          # Optional: Helper scripts
├── templates/        # Optional: Document templates
└── resources/        # Optional: Reference files
```

### Using Skills in Claude.ai
1. Click skill icon (🧩) in chat interface
2. Add skills from marketplace or upload custom skills
3. Claude automatically activates relevant skills

### Using Skills in Claude Code
```bash
# Place skill in ~/.config/claude-code/skills/
mkdir -p ~/.config/claude-code/skills/
cp -r skill-name ~/.config/claude-code/skills/

# Start Claude Code
claude
```

### Using Skills via API
```python
import anthropic

client = anthropic.Anthropic(api_key="your-api-key")

response = client.messages.create(
    model="claude-3-5-sonnet-20241022",
    skills=["skill-id-here"],
    messages=[{"role": "user", "content": "Your prompt"}]
)
```

## Creating Custom Skills

### Basic Template
```markdown
---
name: my-skill-name
description: Clear description of what this skill does and when to use it.
---

# My Skill Name

## When to Use This Skill
- Use case 1
- Use case 2
- Use case 3

## Instructions
[Detailed instructions for Claude on how to execute this skill]

## Examples
[Real-world examples showing the skill in action]
```

### Best Practices for Skills
- **Focus on specific, repeatable tasks**
- **Include clear examples and edge cases**
- **Write instructions for Claude, not end users**
- **Test across Claude.ai, Claude Code, and API**
- **Document prerequisites and dependencies**
- **Include error handling guidance**

## Popular Skill Combinations

### For Web Developers
- Connect (app automation) + artifacts-builder + Playwright + prompt-engineering

### For Product Teams
- Content Research Writer + Lead Research Assistant + Twitter Algorithm Optimizer + Brand Guidelines

### For Data Teams
- CSV Data Summarizer + postgres + deep-research + Mixpanel/Google Analytics Automation

### For Creators
- Canvas Design + Image Enhancer + Theme Factory + slack-gif-creator + youtube-transcript

### For Business Automation
- Gmail/Slack Automation + Notion Automation + Google Sheets + Tailored Resume Generator

## App Automation Quick Reference

### Sending Communications
- **Slack**: Post messages, threads, reactions, scheduled messages
- **Gmail**: Send, reply, search labels, create drafts
- **Teams**: Send messages, create channels, manage teams
- **Discord**: Post messages, manage servers, reactions

### Project Management
- **Jira**: Create/update issues, search with JQL, manage sprints
- **Linear**: Create issues, manage projects, update workflows
- **Asana**: Create tasks, organize projects, assign work
- **Notion**: Create pages, query databases, manage blocks

### Code & DevOps
- **GitHub**: Create issues/PRs, manage repos, trigger actions
- **GitLab**: Create MRs, manage projects, trigger pipelines
- **Vercel**: Deploy projects, manage domains, view logs
- **Supabase**: Execute SQL, manage functions, access storage

### Spreadsheets & Data
- **Google Sheets**: Read/write cells, manage sheets, create charts
- **Airtable**: Create/update records, manage bases, query databases
- **Coda**: Create docs, add tables, manage pages

### CRM & Sales
- **Salesforce**: Query SOQL, manage objects, bulk operations
- **HubSpot**: Create/update contacts, manage deals, email tracking
- **Pipedrive**: Manage deals, activities, pipelines

## Tips for Effective Skill Usage

1. **Combine skills for workflows**: Message + spreadsheet + CRM for lead management
2. **Use error handling**: ChatGPT can retry or fallback to alternatives
3. **Check prerequisites**: Some skills require API keys or setup
4. **Document integrations**: Record which skills work together
5. **Test before production**: Verify skill behavior with test data
6. **Monitor rate limits**: Be aware of API quotas on integrated services
7. **Security**: Protect sensitive files with deny lists in settings.json

## Finding & Contributing

- **Repository**: https://github.com/ComposioHQ/awesome-claude-skills
- **Contributing**: Follow CONTRIBUTING.md guidelines
- **Community**: Join Discord, follow on Twitter/X
- **Marketplace**: Access skills in Claude.ai marketplace

## Integration Examples

### Automated Report Generation
Connect: CSV Data Summarizer + Google Sheets + Gmail
- Read data from Sheet → Summarize → Email report

### Lead Research Pipeline
Connect: Lead Research Assistant + Notion + Slack
- Research leads → Save to Notion database → Alert on Slack

### Content Publishing Automation
Connect: Content Research Writer + Twitter Optimizer + Brand Guidelines + Slack
- Write content → Optimize for Twitter → Apply branding → Post update to Slack

## Resources

- Official Skills: https://github.com/anthropics/skills
- Community Skills: Awesome Claude Skills repository
- Documentation: https://support.claude.com/en/articles/12512198-creating-custom-skills
- Examples: Available in each skill's folder

---

**Note**: Skills work across Claude.ai, Claude Code, and the Claude API. Once created, skills are portable across all platforms.
