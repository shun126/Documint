{{title Category Workflow}}

# Category Workflow

![Category Workflow](images/category-workflow.png)

Documint categories can be used as lightweight metadata. A page can have several categories, so the same document can appear in multiple generated category pages.

## Example

Use one category for importance, one for audience, and one for the author or owner:

```text
{{category Required, Engineer, Moriya}}
```

This creates three ways to find the same page.

| Category | Meaning |
| --- | --- |
| `Required` | Importance or reading priority |
| `Engineer` | Intended audience |
| `Moriya` | Author, owner, maintainer, or signature |

## Why This Helps

Folders force a page into one place. Categories let a page belong to several useful views at once.

For example:

| Page | Categories |
| --- | --- |
| Release checklist | `Required`, `Engineer`, `Operations`, `Moriya` |
| New member guide | `Required`, `Onboarding`, `Everyone` |
| API troubleshooting | `Engineer`, `Troubleshooting`, `Moriya` |

## Category Lists

This page shows selected category groups below:

{{category_list size=3, Required, Engineer, Moriya}}

{{category Required, Engineer, Moriya}}
