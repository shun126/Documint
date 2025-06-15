

parsedown
https://github.com/erusev/parsedown


## 予約タグ

{{title}}

{{body}}

{{sidebar}}

{{{ filename }}} と記述するとfilenameで指定したファイルをマージします。
拡張子がpuの場合はPlantUMLとして処理
拡張子がhtmlの場合はHTMLとして処理
それ以外の拡張子ではMarkdownとしてマージします。

"```source"
"```mermaid"
"```plantuml"
"@startuml"
