<div class="thumbnail">
    <?php if ($link = \Yii::$app->imagemanager->getImagePath($model->id, 300, 300)): ?>
        <img src="<?= $link ?>" alt="<?= $model->fileName ?>">
    <?php else: ?>
        <div class="file-extension"><?= \noam148\imagemanager\helpers\ImageHelper::getFileExtension($model) ?></div>
    <?php endif; ?>
    <div class="filename"><?= $model->fileName ?></div>
</div>
