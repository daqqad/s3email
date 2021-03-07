<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Crypto\S3EncryptionClientV2;
use Aws\Kms\KmsClient;
use Aws\Crypto\KmsMaterialsProviderV2;
use Aws\Ses\SesClient;

$kmsKeyId = 'abac9277-3c48-4824-a3b7-3ecac73b8b09';
$cipherOptions = [
  'Cipher' => 'gcm',
  'KeySize' => 256,
];

$S3Client = new S3Client([
  'region' => 'us-east-1',
  'version' => 'latest',
]);

$SesClient = new SesClient([
	'region' => 'us-east-1',
  'version' => 'latest',
]);

$encryptionClient = new S3EncryptionClientV2($S3Client);
$materialsProvider = new KmsMaterialsProviderV2(
  new KmsClient([
    'region' => 'us-east-1',
    'version' => 'latest',
  ]),
  $kmsKeyId
);

$buckets = $S3Client->ListBuckets();
foreach ($buckets['Buckets'] as $bucket) {
  $bucket_result = $S3Client->ListObjectsV2(['Bucket' => $bucket['Name']]);
  if (strpos($bucket['Name'], 'catchall') !== false && $bucket_result['KeyCount'] > 0) {
		$mail = new \PHPMailer\PHPMailer\PHPMailer;
		$mail->setFrom("noreply@example.com", "S3 Mailer");
		$mail->addAddress("you@example.com");
		$mail->Subject = "Received emails from " . $bucket['Name'];
		$mail->Body = <<<EOS
Received emails are attached.
EOS;

    $bucket_files = $S3Client->getIterator('ListObjects', ['Bucket' => $bucket['Name']]);
    foreach ($bucket_files as $bucket_file) {
      $email = $encryptionClient->getObject([
        '@KmsAllowDecryptWithAnyCmk' => true,
        '@SecurityProfile' => 'V2_AND_LEGACY',
        '@MaterialsProvider' => $materialsProvider,
        '@CipherOptions' => $cipherOptions,
        'Bucket' => $bucket['Name'],
        'Key' => $bucket_file['Key'],
      ]);
      $attach = utf8_decode($email['Body']);
			preg_match('/Subject\: (.*)/', utf8_decode($email->get('Body')), $subject);
			$mail->addStringAttachment($attach, preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $subject[1] . '.eml'), 'base64', 'text/html');
			$S3Client->deleteObject([
				'Bucket' => $bucket['Name'],
				'Key'	=> $bucket_file['Key'],
			]);
    }

		$mail->preSend();
		$rawMessage = $mail->getSentMIMEMessage();
		$SesClient->sendRawEmail([
			'RawMessage' => [
				'Data' => $rawMessage,
			],
		]);
  }
}
?>
