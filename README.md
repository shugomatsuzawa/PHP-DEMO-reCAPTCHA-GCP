# reCAPTCHA (Google Cloud) PHP DEMO
reCAPTCHAを使用したメールフォームのデモンストレーションです。  
[デモを見る](https://demo.shugomatsuzawa.com/php-demo-recaptcha-gcp)
## ローカルで実行
1. ```docker compose up -d```で起動します。
2. ```docker compose exec php bash```でコンテナに入ります。
3. ```composer install```を実行します。
4. ```gcloud init```を実行します。
5. ```gcloud auth application-default login```を実行します。
## デプロイ
1. ```php/html```ディレクトリを公開します。
2. ```composer install```を実行します。
3. ```config-sample.php```を```config.php```にコピーし、必要に応じて内容を修正します。
4. Google CloudコンソールでサービスアカウントとJSONキーを作成します。
5. サーバーにキーを保存し、保存先のパスを```GOOGLE_APPLICATION_CREDENTIALS```環境変数に追加します。
## 詳しい情報
詳しい情報については、[ブログ](https://shugomatsuzawa.com/techblog/2025/03/19/433/)をご覧ください。