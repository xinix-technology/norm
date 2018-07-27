<select name="<?php echo $name ?>">
    <option value=""></option>
    <?php foreach ($self->fetch() as $key => $row): ?>
    <option value="<?php echo $key ?>" <?php echo $key == $value ? 'selected' : '' ?>>
        <?php echo $self['to$label'] ? $row['to$label'] : (
            $self['to$key']
                ? $row->format()
                : $row
        ) ?>
    </option>
    <?php endforeach ?>
</select>
