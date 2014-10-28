<select name="<?php echo $self['name'] ?>" data-value="<?php echo @$value ?>">
    <option value="">---</option>
    <?php foreach ($self->optionData() as $entry): ?>
        <option value="<?php echo $self->optionValue($entry) ?>" <?php echo ($self->optionValue($entry) == $value ? 'selected' : '') ?>>
            <?php echo $self->optionLabel($entry) ?>
        </option>
    <?php endforeach ?>
</select>
