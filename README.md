# s3email
Script to forward encrypted messages stored in S3 buckets by SES as attachments via SES.

### Purpose:
I build this because I have a bunch of domains where I don't actively use email, but also don't want to bounce everything.
This script allows me to schedule a cron job that runs once a day and sends me all emails that arrived on catchall accounts for these domains as attachments for simple review.

### Requirements:
`# composer require aws/aws-sdk-php phpmailer/phpmailer`

#### AWS actions:<br>
ses:SendRawEmail<br>
s3:DeleteObject<br>
s3:GetObject<br>
s3:ListBucket<br>
s3:ListAllMyBuckets<br>
kms:Decrypt

### Usage:
Modify the following variables to your own:

$kmsKeyId // This is ID of KMS key SES uses to encrypt messages stored in S3<br>
$mail->setFrom("noreply@daqfx.com", "S3 Mailer") // From address and subject for summary emails<br>
$mail->addAddress("alex@daqfx.com") // Address where you want to receive summary emails<br>


You will also need to set region for various AWS clients to match your own

I use EC2 roles for access to all required AWS services. You can also configure authentication inside PHP if you're not running this on EC2 instance.
