<?php
/* @var \CController $this */
/* @var \gateways\models\Request $requestModel */
?>

<?php echo CHtml::beginForm($requestModel->url, $requestModel->method, array(
	'name' => 'redirectForm',
)); ?>
<?php foreach ($requestModel->params as $key => $value): ?>
	<?php echo CHtml::hiddenField($key, $value); ?>
<?php endforeach; ?>

<noscript><?php echo CHtml::submitButton('Нажмите, чтобы перейти на страницу оплаты.'); ?></noscript>
<script>document.redirectForm.submit();</script>