
<div>
    <?php if (!empty($value)): ?>
        <?php foreach($value as $k => $v): ?>
            <div>
                <?php echo $k ?> = <?php echo $v ?>
            </div>
        <?php endforeach ?>
    <?php endif ?>
</div>
