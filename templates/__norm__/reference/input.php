<select name="<?php echo isset($name) ? $name : $self['name'] ?>" data-value="<?php echo @$value ?>">
    <option value="">---</option>
    <?php foreach ($self['foreign']() as $foreignValue => $foreignLabel): ?>
        <option value="<?php echo $foreignValue ?>" <?php echo ($foreignValue == $value ? 'selected' : '') ?>>
            <?php echo $foreignLabel ?>
        </option>
    <?php endforeach ?>
</select>