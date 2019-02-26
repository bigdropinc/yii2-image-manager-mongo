<?php

namespace noam148\imagemanager\components;

use Aws\S3\S3Client;
use noam148\imagemanager\helpers\ImageHelper;
use yii\base\Component;

class S3Component extends Component
{
    public $endpoint;

    public $key;

    public $secret;

    public $defaultBucket;

    public $s3Url = '';

    /** @var S3Client */
    protected $client;

    public function init()
    {
        $configs = [
            'version' => 'latest',
            'region'  => 'us-east-1',
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret,
            ],
            'use_path_style_endpoint' => true,
        ];

        if($this->endpoint){
            $configs['endpoint'] = $this->endpoint;
        }

        $this->client = new S3Client($configs);
    }

    public function put($fileName, $filePath, $folder = null)
    {
        if($folder){
            $this->createFolderIfNotExists($folder);
        }

        $fileName = ImageHelper::getS3FileRelativePathByFolder($fileName, $folder);

        $this->client->putObject([
            'Bucket' => $this->defaultBucket,
            'Key'    => $fileName,
            'Body'   => fopen($filePath, 'r'),
            'ContentType' => mime_content_type($filePath) ?? 'image/png',
            'ACL'    => 'public-read',
        ]);
    }

    public function copy($oldName, $newName)
    {
        return $this->client->copyObject([
            'ACL' => 'public-read',
            'Bucket' => $this->defaultBucket,
            'Key' => $newName,
            'CopySource' => urlencode($this->defaultBucket . '/' . $oldName),
        ]);
    }

    public function createBucketIfNotExists($bucket)
    {
        if (!$this->client->doesBucketExist($bucket)) {
            $this->client->createBucket([
                'ACL' => 'public-read',
                'Bucket' => $bucket,
            ]);

            $this->client->putBucketPolicy([
                'Bucket' => $bucket,
                'Policy' => '{
                  "Version":"2012-10-17",
                  "Statement":[
                    {
                      "Sid":"AddPerm",
                      "Effect":"Allow",
                      "Principal": "*",
                      "Action":["s3:GetObject"],
                      "Resource":["arn:aws:s3:::' . $bucket . '/*"]
                    }
                  ]
                }',
            ]);
        }
    }

    public function createFolderIfNotExists($folder)
    {
        if (!$this->client->doesObjectExist($this->defaultBucket, $folder)) {
            $this->client->putObject(array(
                'Bucket' => $this->defaultBucket,
                'Key'    => $folder . '/',
                'Body'   => "",
                'ACL'    => 'public-read'
            ));
        }
    }

    public function getObject($fileName, $bucket = null)
    {
        $result = null;

        $bucket = $bucket ?? $this->defaultBucket;

        if ($this->client->doesBucketExist($bucket)) {
            if($this->client->doesObjectExist($bucket, $fileName)){
                $result = $this->client->getObject([
                    'Bucket' => $bucket,
                    'Key'    => $fileName,
                    'SaveAs' => ImageHelper::getTempFilePath($fileName)
                ]);
            }
        }

        return $result;
    }

    public function doesObjectExist($fileName, $bucket = null)
    {
        $result = false;

        $bucket = $bucket ?? $this->defaultBucket;

        if ($this->client->doesBucketExist($bucket)) {
            $result = $this->client->doesObjectExist($bucket, $fileName);
        }

        return $result;
    }

    public function delete($fileName, $folder = null)
    {
        $fileName = ImageHelper::getS3FileRelativePathByFolder($fileName, $folder);

        if($this->client->doesObjectExist($this->defaultBucket, $fileName)){
            $this->client->deleteObject([
                'Bucket' => $this->defaultBucket,
                'Key'    => $fileName,
                'ACL'    => 'public-read',
            ]);
        }
    }
}
