<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Source[] $sources */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap5\ActiveForm;

$this->title = Yii::t('app', 'import.title');
?>
<div class="import-index">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>

            <div class="mb-3">
                <label class="form-label"><?= Yii::t('app', 'import.source_type') ?></label>
                <select name="source_id" class="form-select" required>
                    <option value=""><?= Yii::t('app', 'import.select_source') ?></option>
                    <?php foreach ($sources as $source): ?>
                        <option value="<?= $source->id ?>"><?= Html::encode($source->name) ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label"><?= Yii::t('app', 'import.upload') ?></label>
                <input type="file" name="file" class="form-control" accept=".csv,.json" required>
                <div class="form-text">CSV (.csv) or JSON (.json)</div>
            </div>

            <?= Html::submitButton(Yii::t('app', 'import.start'), ['class' => 'btn btn-primary']) ?>

            <?php ActiveForm::end() ?>
        </div>
    </div>

    <div class="mt-3">
        <?= Html::a(Yii::t('app', 'import.view_batches'), ['/import/batches'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>
</div>
