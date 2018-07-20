<?php
use noam148\imagemanager\helpers\ImageHelper;
?>

<div class="thumbnail">
    <?php if ($link = ImageHelper::getThumbUrl($model, 300,300)): ?>
        <img src="<?= $link ?>" alt="<?= $model->fileName ?>">
    <?php else: ?>
        <div class="file-extension"><?= \noam148\imagemanager\helpers\ImageHelper::getFileExtension($model) ?></div>
    <?php endif; ?>
    <div class="filename"><?= $model->fileName ?></div>
</div>
