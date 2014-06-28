<?php
use \Bono\Helper\URL;
?>
<select name="<?php echo $self['name'] ?>" data-value="<?php echo @$value ?>">
    <option value="">---</option>
    <?php foreach ($self->findOptions() as $key => $entry): ?>
        <?php

        if (is_scalar($entry)):
            $label = $entry;
        elseif (is_callable($self['foreignLabel'])):
            $getLabel = $self['foreignLabel'];
            $label = $getLabel($entry);
        else:
            $label = $entry[$self['foreignLabel']];
        endif;

        if (is_scalar($entry)):
            $entryValue = $key;
        else:
            $entryValue = $entry[$self['foreignKey']];
        endif;
        ?>
        <option value="<?php echo $entryValue ?>" <?php echo ($entryValue == $value ? 'selected' : '') ?>>
            <?php echo $label ?>
        </option>
    <?php endforeach ?>
</select>
