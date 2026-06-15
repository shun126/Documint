# Documint
MarkdownからスッキリとしたWebページをサクッと発行



## テンプレート内で使用できるタグ
`{{title}}`
`{{body}}`
`{{sidebar}}`
`{{category カテゴリ名,...}}`
`{{category_list カテゴリ名}}`

### category / category_list
- `{{category カテゴリ名}}` でページにカテゴリを設定し、カテゴリ別ページへのリンクを本文に生成。複数カテゴリは `,` 区切りで指定。
- カテゴリ別ページは `_page_list/category-{hash}.html` に自動生成。`{hash}` はカテゴリ名から生成したハッシュ。
- `{{category_list}}` はカテゴリごとに `<h2>カテゴリ名</h2>` + ページリストを現在ページへ生成。
- `{{category_list カテゴリ名}}` は指定カテゴリのページだけを現在ページへ生成。
- `{{category_list カテゴリ名1,カテゴリ名2}}` は指定した複数カテゴリのページリストを現在ページへ生成。
- `page_list.html` は `_page_list/page_list.html` に自動生成。

## サイドバー
- サイドバーは `sidebar.md` を親ディレクトリに向かって探索して使用します。
- `sidebar.md` は Markdown として処理され、`template.html` 内の `{{sidebar}}` に埋め込まれます。

## Markdown内で使用できる機能
` {{{ filename }}} ` と記述すると`filename`で指定したファイルをマージします。
* 拡張子が`.pu`の場合はPlantUMLとして処理
* 拡張子が`.html`の場合はHTMLとして処理
* それ以外の拡張子ではMarkdownとしてマージします。

### htmlブロック
- 生HTMLを明示的に記述したい場合は `{{html}}` と `{{/html}}` で囲みます。
- `{{html}}` の次の行から `{{/html}}` の直前の行までは、そのままHTMLとして出力されます。
- htmlブロック内では Markdown、`{{page_list}}`、`{{category...}}`、`{{{ filename }}}` は解釈されません。
- `{{html ... }}` のような短縮記法、開始タグと終了タグの同一行記法、入れ子は未対応です。
- htmlブロックの外側に書いた生HTMLは現状も動作しますが、今後はhtmlブロックの使用を推奨します。

```text
{{html}}
<a href="a">a</a>
{{/html}}
```

### HTMLファイルの取り込みとの使い分け
- `{{{ part.html }}}` は再利用するHTML断片の取り込みに使います。
- `{{html}} ... {{/html}}` はページ内でその場限りのHTMLを直接書きたい場合に使います。

* ` ```source `
* ` ```mermaid `
* ` ```plantuml `
* ` {{page_list}} `
* ` @startuml `

# テスト

## mermaid
```mermaid
graph TB
  Start([Start])-->B{if a > b}
  B-->|True| End
  B-->|False| IFS[/while\]
  IFS-->C[a++]
  C-->IFB[\  /]
  IFB-->End([End])
```

## plantuml
```plantuml
Interface InterfaceA {
}

class ClassA {
}

InterfaceA <|.. ClassA
```

## Code
```cpp
int main(int argc, char* argv[])
{
  return 0;
}
```

# 謝辞
PHPのMarkdownパーサーに[parsedown](https://github.com/erusev/parsedown)を利用しています。
