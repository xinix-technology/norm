<select class="<?php echo $self->inputClass() ?>" <?php echo $self->inputAttributes() ?> name="<?php echo isset($name) ? $name : $self['name'] ?>" data-value="<?php echo @$value ?>">
    <option value="">---</option>
    <?php foreach ($self->optionData() as $key => $entry): ?>
        <option value="<?php echo $self->optionValue($key,$entry) ?>" <?php echo ($self->optionValue($key,$entry) == $value ? 'selected' : '') ?>>
            <?php echo $self->optionLabel($key,$entry) ?>
        </option>
    <?php endforeach ?>
</select>