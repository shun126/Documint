{{title Advanced Features}}

# Advanced Features

![Advanced Features](images/advanced-features.png)

This page provides focused examples for Documint-specific syntax.

## Mermaid

```mermaid
sequenceDiagram
  participant Author
  participant Documint
  participant Browser
  Author->>Documint: Open index.php
  Documint->>Browser: Generate and link HTML files
```

## PlantUML

```plantuml
@startuml
class Page {
  title
  categories
}

class CategoryPage
Page --> CategoryPage
@enduml
```

## Source Block

```source
<p>This content is emitted directly from a source block.</p>
```

```cpp
void main()
{
  printf("Hellow world");
}
```

## Raw HTML Block

{{html}}
<div class="alert alert-warning">
  Raw HTML blocks are useful for one-off Bootstrap components.
</div>
{{/html}}

## Category Links
{{category_list size=3}}

----
{{category Reference, Guide, Moriya}}
