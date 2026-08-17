<?php

return [
    'page_title' => [
        'dashboard' => 'ダッシュボード',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => '%entity_label_singular%を編集',
        'index' => '%entity_label_plural%',
        'new' => '%entity_label_singular%を作成',
        'exception' => 'エラー',
    ],

    'datagrid' => [
        'hidden_results' => '権限がないため、一部の結果は表示されません。',
        'no_results' => '結果が見つかりません。',
    ],

    'paginator' => [
        'first' => '最初',
        'previous' => '前へ',
        'next' => '次へ',
        'last' => '最後',
        'counter' => '<strong>%results%</strong>件中 <strong>%start%</strong>～<strong>%end%</strong>件',
        'results' => '{0} 結果なし|]0,Inf] <strong>%count%</strong>件',
        'label' => 'ページ送り',
        'more_pages' => 'その他のページ',
    ],

    'label' => [
        'true' => 'はい',
        'false' => 'いいえ',
        'empty' => '空',
        'null' => 'なし',
        'object' => 'PHPオブジェクト',
        'inaccessible' => 'アクセス不可',
        'inaccessible.explanation' => 'このフィールドのゲッターメソッドが存在しないか、フィールドが公開されていません。',
        'form.empty_value' => 'なし',
    ],

    'field' => [
        'code_editor.view_code' => 'コードを表示',
        'text_editor.view_content' => '内容を表示',
    ],

    'action' => [
        'entity_actions' => '操作',
        'new' => '%entity_label_singular%を追加',
        'search' => '検索',
        'detail' => '詳細',
        'edit' => '編集',
        'delete' => '削除',
        'cancel' => 'キャンセル',
        'index' => '一覧に戻る',
        'deselect' => '選択解除',
        'add_new_item' => '項目を追加',
        'remove_item' => '項目を削除',
        'choose_file' => 'ファイルを選択',
        'close' => '閉じる',
        'download' => 'ダウンロード',
        'create' => '作成',
        'create_and_add_another' => '作成して続けて追加',
        'create_and_continue' => '作成して編集を続ける',
        'save' => '変更を保存',
        'save_and_continue' => '保存して編集を続ける',
        'toggle_dropdown' => 'メニューを開閉',
    ],

    'batch_action_modal' => [
        'title' => '%num_items%件の項目に「%action_name%」を実行します。',
        'content' => 'この操作は取り消せません。',
        'action' => '実行',
    ],

    'delete_modal' => [
        'title' => 'この項目を削除しますか？',
        'content' => 'この操作は取り消せません。',
    ],

    'action_confirmation_modal' => [
        'title' => '「%action_name%」を実行しますか？',
        'action' => '確認',
    ],

    'filter' => [
        'title' => 'フィルター',
        'button.clear' => 'クリア',
        'button.apply' => '適用',
        'label.is_equal_to' => '次と等しい',
        'label.is_not_equal_to' => '次と等しくない',
        'label.is_greater_than' => '次より大きい',
        'label.is_greater_than_or_equal_to' => '次以上',
        'label.is_less_than' => '次より小さい',
        'label.is_less_than_or_equal_to' => '次以下',
        'label.is_between' => '範囲内',
        'label.contains' => '次を含む',
        'label.contains_all' => 'すべて含む',
        'label.not_contains' => '次を含まない',
        'label.starts_with' => '次で始まる',
        'label.ends_with' => '次で終わる',
        'label.exactly' => '完全一致',
        'label.not_exactly' => '完全一致しない',
        'label.is_same' => '同じ',
        'label.is_not_same' => '異なる',
        'label.is_after' => '次より後',
        'label.is_after_or_same' => '次以降',
        'label.is_before' => '次より前',
        'label.is_before_or_same' => '次以前',
    ],

    'form' => [
        'are_you_sure' => 'フォームの変更が保存されていません。',
        'tab.error_badge_title' => '%count%件の入力エラー',
        'slug.confirm_text' => 'スラッグを変更すると、他のページのリンクが無効になる場合があります。',
    ],

    'user' => [
        'logged_in_as' => 'ログイン中：',
        'unnamed' => '名前未設定のユーザー',
        'anonymous' => '匿名ユーザー',
        'sign_out' => 'ログアウト',
        'exit_impersonation' => '代理ログインを終了',
    ],

    'settings' => [
        'appearance' => [
            'label' => '表示モード',
            'light' => 'ライト',
            'dark' => 'ダーク',
            'auto' => '自動',
        ],
        'locale' => '言語',
    ],

    'login_page' => [
        'username' => 'ユーザー名',
        'password' => 'パスワード',
        'sign_in' => 'ログイン',
        'forgot_password' => 'パスワードをお忘れですか？',
        'remember_me' => 'ログイン状態を保持する',
    ],

    'exception' => [
        'entity_not_found' => 'この項目は利用できなくなりました。',
        'entity_remove' => '他の項目が依存しているため、この項目は削除できません。',
        'forbidden_action' => 'この項目に対して要求された操作を実行できません。',
        'insufficient_entity_permission' => 'この項目にアクセスする権限がありません。',
        'general' => 'リクエストの処理中にエラーが発生しました。',
        'general_403' => 'この操作を実行する権限がありません。',
        'general_404' => '要求されたページが見つかりません。',
        'general_500' => 'リクエストの処理中に内部エラーが発生しました。',
    ],

    'file_upload' => [
        'add_file' => 'ファイルを追加',
        'add_files' => 'ファイルを追加',
        'clear_all' => 'すべてクリア',
    ],

    'autocomplete' => [
        'no-results-found' => '結果が見つかりません',
        'no-more-results' => 'これ以上結果はありません',
        'loading-more-results' => '追加の結果を読み込み中…',
    ],
];
