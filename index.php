<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Crypto\S3EncryptionClientV2;
use Aws\Kms\KmsClient;
use Aws\Crypto\KmsMaterialsProviderV2;

$kmsKeyId = 'abac9277-3c48-4824-a3b7-3ecac73b8b09';
$cipherOptions = [
  'Cipher' => 'gcm',
  'KeySize' => 256,
];

$S3Client = new S3Client([
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
	if (strpos($bucket['Name'], 'catchall') !== false) {
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
			die(var_dump(utf8_decode($email['Body'])));
		}
		//$meta = $s3Client->getObject([
		//	'Bucket' => $bucket['Name'],
		//	'Key' => 
	}
}

#var_dump($result);

?>
