Markdown viewer for web
====
This is sub header
----

## Description

markdown を良い感じに表示する phar です。
ざっくり言えば要するに docsify の php 版のようなものです。

デザインは Read The Docs のものを使用しています。

- [Demo](https://arima-ryunosuke.github.io/php-htmarkdown/)

## Usage

下記の3つの使い方があります。
いずれも `.md` `.php.md` の拡張子を対象にナビゲーションリンクが生成されます。
さらに `.php.md` の場合は php が実行されます。

### Rewrite Mode

下記のように web サーバを設定します。

```plaintext:apacheの場合
RewriteRule \.md$ htmarkdown.phar [QSA,L]
```

```plaintext:nginxの場合
rewrite \.md$ htmarkdown.phar;
```

説明はほぼ不要ですが、拡張子 md を rewrite して phar に委譲されるようにします。
`-d` オプションを加えれば Directory Index としても機能します。

※ 環境によっては `security.limit_extensions` や `SetHandler` 等の設定が必要です

### Built in Server Mode

下記のようにして built in server で起動します。

```bash
php -S 0.0.0.0:8000 htmarkdown.phar
```

これも説明は不要でしょう。通常の built in server です。
phar がルーターの役割も持っているので、起動したカレントディレクトリ（もちろん `-t` も有効です）をドキュメントルートとして web サーバが起動します。

docsify の使い方に近いです。

### Command Line Mode

下記のように引数を渡すと引数が html に変換されます。

```bash
php htmarkdown.phar /path/to/markdown.md > /path/to/markdown.html
```

出力は標準出力です。なので上記のようにリダイレクトは必須です。
また、ディレクトリを渡すと zip のバイナリが出力されます。

ただし、この機能は現時点ではおまけです。
この機能は後述の semver の互換性保持には含まれません。

## Feature

基本的には機能決め打ちのただの markdown viewer です。

- parent, sibling, children のような周辺リンクの生成機能
- 自身以下からの検索機能
- ダウンロード機能
- 一部の設定の画面操作

などがありますが、おまけです。
ドキュメントルートに配置して、 markdown を踏んだときにそれらしく見やすくするのが主目的です。

### 拡張構文

標準の markdown に加えて下記の拡張構文があります。

#### マーカー

`==マーカー文字==` とするとマーカーが引かれます。

```markdown:マーカー
これは==マーカー==文字です。
```

上記のようにすると下記のようになります。

これは==マーカー==文字です。

#### バッジ

`{タイプ:タイトル|テキスト}` とするとバッジになります。

```markdown:バッジの例
- 単純なバッジ {this is badge}
- success バッジ {success:テキスト}
- タイトル付きalert バッジ {alert:タイトル|テキスト}
```

上記のようにすると下記のようになります。

- 単純なバッジ {this is badge}
- success バッジ {success:テキスト}
- タイトル付きalert バッジ {alert:タイトル|テキスト}

上記のようにタイプ,タイトル,テキストはある程度省略できます。
タイプは `info` `success` `notice` `alert` が使えます。

#### キャプション

ヘッダーのように `####### Caption` と `#` を7個並べるとキャプションになります。

```markdown:キャプション
####### This Is Caption

内容
```

上記のようにすると下記のようになります。

####### This Is Caption

内容

キャプションはブロック要素となり `p` で囲われません。さらに見出しとして検出されません。
実態は `em` ですが、変更される可能性があります。
また、意図的に下部パディングを小さめにしています。

セクション内のちょっとした区切りやアクセントとして使用します。

#### ノート

コードブロックと同じように `"""` で囲むと注意書きブロックが生成されます。

```markdown:注意書きの例
"""note:title
これは注意書きです。
"""
```

上記のようにすると下記のようになります。

"""note:title
これは注意書きです。
"""

`note` の部分は Read The Docs の他のタイプが使えます。頻出するのは `hint` `info` `warning` `important` などでしょう。

title の部分はただのラベルになります。

#### サイドバー

コードブロックと同じように `///` で囲むとサイドバーブロックが生成されます。

```markdown:サイドバーの例
///note:title
これはサイドバーです。
///
```

上記のようにすると下記のようになります。

///note:title
これはサイドバーです。
///

note の部分はほぼ飾りです。 Read The Docs 本家にスタイルがなかったため、埋め込まれはしますがスタイルは変わりません。
title の部分はただのラベルになります。

ただし `right` を指定した場合のみ無装飾で右上に配置されます。

```markdown:right
///right
これは右側に配置されます。
///
```

///right
これは右側に配置されます。
///

#### 定義リスト

`-` や `*` などのリスト記法に続けて、`: `（コロンスペース）があると定義リストが生成されます。

```markdown:横並び定義リストの例
- りんご: 赤いです
- みかん: 黄色いです
```

上記のようにすると下記のようになります。

- りんご: 赤いです
- みかん: 黄色いです

コロンの直後に改行すると横並びではなくスタンダードな定義リストになります。

```markdown:縦並び定義リストの例
- りんご:
赤いです
美味しいです
- みかん:
黄色いです
酸っぱいです
```

上記のようにすると下記のようになります。

- りんご:
赤いです
美味しいです
- みかん:
黄色いです
酸っぱいです

コロンの直後にスペースを含めて改行すると横並びで詳細を記載する機会が与えられます。
つまり、定義リストのネストや他の記法が使えることになります。

````markdown:横並びネスト定義リストの例
- 果物: 
    - りんご: これはネストされた箇条書きリストです
        - 赤いです
        - 美味しいです
    - みかん: これはネストされた定義リストです
        - 色: 黄色
        - 季節: 冬
    - いちご: 
    ```php
    // これはネスト中のコードブロックです
    echo 1;
    echo 2;
    ```
````

上記のようにすると下記のようになります。

- 果物: 
    - りんご: これはネストされた箇条書きリストです
        - 赤いです
        - 美味しいです
    - みかん: これはネストされた定義リストです
        - 色: 黄色
        - 季節: 冬
    - いちご: 
    ```php
    // これはネスト中のコードブロックです
    echo 1;
    echo 2;
    ```

標準 markdown にはなぜか定義リストがなく、結構不便なので独自に定義しています。
上記の通り、他のあらゆる記法とは全く異なる独自ルールです。

そもそも定義リストは**リスト**ですし、もともと markdown は「そのままのテキストでもそれっぽく見える」ような記法だったはずです。
ので見た目（md）とマークアップ（html）があまり剥離しないこのルールは割と好きです。

"""warning:注意
コロンスペースのスペースは必須です。でないと下記のような「URL のリスト」が定義リストと認識されてしまいます。

- http://example.com/hoge
- http://example.com/fuga

※ スペースを必須にしないとただのリストのつもりが、下記のようになってしまう

- http: //example.com/hoge
- http: //example.com/fuga
"""

#### 折りたたみ

コードブロックと同じように `...` で囲むと折りたたみとなります。

```
...ここは summary になります
ここが折りたたみの中身になります
...
```

上記のようにすると下記のようになります。

...ここは summary になります
ここが折りたたみの中身になります
...

summary 部分を省略すると summary タグは生成されません。
その場合ブラウザのデフォルトになります（「省略」など）。

#### ヒアドキュメント

コードブロックと同じように `<<<` で囲むとヒアドキュメントとなります。

```markdown:ヒアドキュメントの例
<<<
これはヒアドキュメントです。
この中では**一切の markdown 記法や html は無効**になります。

- A
- B
- C

<strong>strong</strong>
<<<
```

上記のようにすると下記のようになります。

<<<
これはヒアドキュメントです。
この中では**一切の markdown 記法や html は無効**になります。

- A
- B
- C

<strong>strong</strong>
<<<

コードブロックと似ていますが、シンタックスハイライトはされません。
あくまで、「マークダウンのエスケープが不要なヒアドキュメント」となります。
使う機会はそう多くないでしょう。

#### mermaid

コードブロックで mermaid を明示すると mermaid 記法になります。

````markdown:mermaid の例
```mermaid
sequenceDiagram
    participant Alice
    participant Bob
    Alice->>John: Hello John, how are you?
    loop Healthcheck
        John->>John: Fight against hypochondria
    end
    Note right of John: Rational thoughts <br/>prevail!
    John-->>Alice: Great!
    John->>Bob: How about you?
    Bob-->>John: Jolly good!
```
````

上記のようにすると下記のようになります。

```mermaid
sequenceDiagram
    participant Alice
    participant Bob
    Alice->>John: Hello John, how are you?
    loop Healthcheck
        John->>John: Fight against hypochondria
    end
    Note right of John: Rational thoughts <br/>prevail!
    John-->>Alice: Great!
    John->>Bob: How about you?
    Bob-->>John: Jolly good!
```

単に mermaid.js を呼んでいるだけであり、特別なことはしていません。

## License

MIT

## Release

バージョニングはセマンティックバージョンに準拠します。
が、内部的な php のコードは準拠しません。
semver 準拠はあくまで下記だけです。

- phar としてのインターフェース
- 拡張構文の互換性

つまり、内部設計・デザイン・レンダリング結果は互換性を維持しません。
例えば「コントールパネルから設定できる項目」「親兄弟のナビゲーション」「テキスト検索」などの機能面は予告なく追加・廃止されることがあります。
また、 Read The Docs のテーマではなく、他のものにガラリと変わることもありえます。

逆に言えば「phar の引数が変更になった」「markdown の拡張構文を廃止した」などは互換性の破壊となり、 semver 準拠（メジャーアップ）となります。

今のところ phar に引数はありませんが、「POST で markdown を編集できる」のようなオプションが増えることが想定されます。

### x.y.z

- POST で更新する？
- 静的ファイルは全部 CDN に逃したい
- markdown に include 機能を持たせたい

### 1.1.3

- [change] デザイン調整
- [feature] 見出しの表示切替機能
- 対応バージョンを 7.4 に格上げ

### 1.1.2

- [fixbug] エクスポート時に出ていたエラーを修正
- [change] main,sub ヘッダーはセクションとみなさない
- [change] デザイン調整
- [feature] フォントの指定機能
- [feature] mermaid.js 対応

### 1.1.1

- [feature] カスタム js/css 機能
- [feature] エクスポート機能
- [refactor] 仕様のシンプル化と軽微変更
- [feature] ディレクトリインデックスファイル名を指定できるように修正
- [feature] 拡張子なしファイルで .md が存在する場合は読み替える
- [feature] 単一ファイルで検索されたときは親ディレクトリを対象とする
- [fixbug] クエリ文字のエスケープが行われていなかったので修正
- [fixbug] Parsedown のオプションが効いていなかった不具合を修正
- [fixbug] 検索結果が大文字小文字の違いでハイライトされない不具合を修正
- [refactor] 気になる箇所を修正
- [change] 自動開閉は単純に表示領域の前後とする
- [change] ブロックインデントを中身だけに変更
- [feature] 開閉ボタンを実装

### 1.1.0

- [feature] 二重拡張子が php の場合は include する機能
- [feature] スクロール同期の遅延を解消
- [feature] アクティブな見出しは隠していても表示される機能
- [feature] right sidebar を実装
- [feature] キャプション記法を実装
- [feature] バッジ記法を実装
- [feature] マーカー記法を実装
- [feature] ToC のサイズ変更機能
- [fixbug] 言語設定が効いていない不具合を修正
- [fixbug] スペルミスでデフォルト値が効いていない不具合を修正

### 1.0.0

- 公開
