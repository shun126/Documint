# Documint
MarkdownからスッキリとしたWebページをサクッと発行



## テンプレート内で使用できるタグ
`{{title}}`
`{{body}}`
`{{sidebar}}`

## Markdown内で使用できる機能
`{{{ filename }}}` と記述すると`filename`で指定したファイルをマージします。
拡張子が`.pu`の場合はPlantUMLとして処理
拡張子が`.html`の場合はHTMLとして処理
それ以外の拡張子ではMarkdownとしてマージします。

"```source"
"```mermaid"
"```plantuml"
`{{page_list}}`
"@startuml"

# テスト

```mermaid
graph TB
  Start([Start])-->B{if a > b}
  B-->|True| End
  B-->|False| IFS[/while\]
  IFS-->C[a++]
  C-->IFB[\  /]
  IFB-->End([End])
```

```plantuml
Interface InterfaceA {
}

class ClassA {
}

InterfaceA <|.. ClassA
```

```cpp
int main(int argc, char* argv[])
{
  return 0;
}
```

# 謝辞
PHPのMarkdownパーサーに[parsedown](https://github.com/erusev/parsedown)を利用しています。
