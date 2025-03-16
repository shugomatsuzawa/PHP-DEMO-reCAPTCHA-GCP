<?php
require 'vendor/autoload.php';
require 'config.php';

// Composer を使用して Google Cloud の依存関係を組み込む
// use Google\Cloud\RecaptchaEnterprise\V1\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient; // NOTE: Client 付きが正解 https://github.com/googleapis/google-cloud-php-recaptcha-enterprise/blob/main/samples/V1/RecaptchaEnterpriseServiceClient/create_assessment.php
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;
use PHPMailer\PHPMailer\PHPMailer;

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  header('Location: /');
  exit;
}

$recaptcha_sitekey = RECAPTCHA_SITEKEY;
$recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
$recaptcha_project = RECAPTCHA_PROJECT;

$name = htmlspecialchars($_POST['name'] ?? '');
$email = htmlspecialchars($_POST['email'] ?? '');
$subject = htmlspecialchars($_POST['subject'] ?? '');
$comment = htmlspecialchars($_POST['comment'] ?? '');

// 入力チェック
if (empty($name) || empty($email) || empty($subject) || empty($comment)) {
  header('Location: /');
  exit;
}

// reCAPTCHA 評価作成
$recaptcha_result = create_assessment(
  $recaptcha_sitekey,
  $recaptcha_token,
  $recaptcha_project,
  'submit'
);

if ($recaptcha_result['status'] === 'success') {
  if ($recaptcha_result['score'] >= 0.5) {
    // 合格（評価あり スコア 0.5 以上）
    $mail_result = send_mail($name, $email, $subject, $comment);
    $result = [
      'status' => 'success',
      'score' => $recaptcha_result['score']
    ];
  } else {
    // 失格（評価あり スコア 0.5 未満）
    $result = [
      'status' => 'error',
      'message' => 'reCAPTCHA score is too low.',
      'score' => $recaptcha_result['score']
    ];
  }
} else {
  // 評価エラー
  $result = $recaptcha_result;
}

/**
 * 評価を作成して UI アクションのリスクを分析する。
 * @param string $recaptchaKey サイト / アプリに関連付けられた reCAPTCHA キー
 * @param string $token クライアントから取得した生成トークン。
 * @param string $project Google Cloud プロジェクト ID
 * @param string $action トークンに対応するアクション名。
 * @return array
 */
function create_assessment(
  string $recaptchaKey,
  string $token,
  string $project,
  string $action
): array {
  // reCAPTCHA クライアントを作成する。
  // TODO: クライアント生成コードをキャッシュに保存するか（推奨）、メソッドを終了する前に client.close() を呼び出す。
  $client = new RecaptchaEnterpriseServiceClient();
  $projectName = $client->projectName($project);

  // 追跡するイベントのプロパティを設定する。
  $event = (new Event())
    ->setSiteKey($recaptchaKey)
    ->setToken($token);

  // 評価リクエストを作成する。
  $assessment = (new Assessment())
    ->setEvent($event);

  // NOTE: CreateAssessmentRequest が必要
  // https://github.com/googleapis/google-cloud-php-recaptcha-enterprise/blob/main/samples/V1/RecaptchaEnterpriseServiceClient/create_assessment.php
  $request = (new CreateAssessmentRequest())
    ->setParent($projectName)
    ->setAssessment($assessment);

  try {
    $response = $client->createAssessment($request);

    // トークンが有効かどうかを確認する。
    if ($response->getTokenProperties()->getValid() == false) {
      return [
        'status' => 'error',
        'message' => 'The CreateAssessment() call failed because the token was invalid for the following reason: ' . InvalidReason::name($response->getTokenProperties()->getInvalidReason()),
      ];
    }

    // 想定どおりのアクションが実行されたかどうかを確認する。
    if ($response->getTokenProperties()->getAction() == $action) {
      // リスクスコアと理由を取得する。
      // 評価の解釈の詳細については、以下を参照:
      // https://cloud.google.com/recaptcha-enterprise/docs/interpret-assessment
      return [
        'status' => 'success',
        'score' => $response->getRiskAnalysis()->getScore(),
      ];
    } else {
      return [
        'status' => 'error',
        'message' => 'The action attribute in your reCAPTCHA tag does not match the action you are expecting to score',
      ];
    }
  } catch (exception $e) {
    return [
      'status' => 'error',
      'message' => 'CreateAssessment() call failed with the following error: ' . $e->getMessage(),
    ];
  }
}

/**
 * メールを送信する。
 * @param string $name 名前
 * @param string $email メールアドレス
 * @param string $subject 件名
 * @param string $comment コメント
 * @return array
 */
function send_mail($name, $email, $subject, $comment): array
{
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = SMTP_AUTH;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = SMTP_TLS ? PHPMailer::ENCRYPTION_SMTPS : false;
    $mail->Port = SMTP_PORT;

    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress($email, $name . ' - reCAPTCHA DEMO');

    $mail->Subject = $subject;
    $mail->Body    = <<<EOF
    Namr: $name
    Email: $email
    Comment:
    $comment
    EOF;

    $mail->send();
    return ['status' => 'success'];
  } catch (Exception $e) {
    return ['status' => 'error', 'message' => 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo];
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>

<body>
  <h1><?php print $result['status'] === 'success' ? 'Successfully sent email' : 'Failure to send email' ?></h1>
  <?php isset($result['message']) ? print "<p>{$result['message']}</p>" : '' ?>
  <?php isset($result['score']) ? print "<p>The score for the protection action is: {$result['score']}</p>" : '' ?>
  <a href="/">Back</a>
</body>

</html>